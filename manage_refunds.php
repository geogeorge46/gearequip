<?php
session_start();
require_once 'config.php';

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Add this at the beginning of the file after session_start()
function logRefundStatus($conn, $refund_id, $status, $message) {
    $log_query = "INSERT INTO refund_logs (refund_id, status, message) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $log_query);
    mysqli_stmt_bind_param($stmt, "iss", $refund_id, $status, $message);
    mysqli_stmt_execute($stmt);
}

// Handle refund processing
if (isset($_POST['process_refund'])) {
    $refund_id = $_POST['refund_id'];
    $rental_id = $_POST['rental_id'];
    $user_id = $_POST['user_id'];
    $amount = $_POST['amount'];
    
    try {
        mysqli_begin_transaction($conn);
        
        // Get rental update details
        $get_update = "SELECT ru.* 
                      FROM refunds r
                      JOIN rental_updates ru ON r.update_id = ru.update_id
                      WHERE r.refund_id = ?";
        $stmt = mysqli_prepare($conn, $get_update);
        mysqli_stmt_bind_param($stmt, "i", $refund_id);
        mysqli_stmt_execute($stmt);
        $update_result = mysqli_stmt_get_result($stmt);
        $update_data = mysqli_fetch_assoc($update_result);

        if (!$update_data) {
            throw new Exception("Could not find rental update information");
        }

        // Update refund status
        $update_refund = "UPDATE refunds 
                         SET status = 'processed',
                             processed_by = ?,
                             refund_notes = 'Refund processed and rental dates updated'
                         WHERE refund_id = ? AND status = 'pending'";
        
        $stmt = mysqli_prepare($conn, $update_refund);
        mysqli_stmt_bind_param($stmt, "ii", $_SESSION['manager_id'], $refund_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to update refund status");
        }

        // Update rental dates and amount in rentals table
        $update_rental = "UPDATE rentals 
                         SET start_date = ?,
                             end_date = ?,
                             total_amount = total_amount - ?,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE rental_id = ?";
        
        $stmt = mysqli_prepare($conn, $update_rental);
        mysqli_stmt_bind_param($stmt, "ssdi", 
            $update_data['new_start_date'],
            $update_data['new_end_date'],
            $amount,
            $rental_id
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to update rental dates and amount");
        }

        // Update rental_updates status
        $update_status = "UPDATE rental_updates 
                         SET status = 'completed',
                             payment_status = 'refunded'
                         WHERE update_id = ?";
        
        $stmt = mysqli_prepare($conn, $update_status);
        mysqli_stmt_bind_param($stmt, "i", $update_data['update_id']);
        mysqli_stmt_execute($stmt);

        // Create notification for user
        $notification_query = "INSERT INTO user_notifications 
                             (user_id, message, type) 
                             VALUES (?, ?, 'refund_completed')";
        
        $message = "Your refund of ₹" . number_format($amount, 2) . " has been processed. " .
                  "Your rental dates have been updated to " . 
                  date('M d, Y', strtotime($update_data['new_start_date'])) . " - " .
                  date('M d, Y', strtotime($update_data['new_end_date']));
        
        $stmt = mysqli_prepare($conn, $notification_query);
        mysqli_stmt_bind_param($stmt, "is", $user_id, $message);
        mysqli_stmt_execute($stmt);

        mysqli_commit($conn);
        $_SESSION['success'] = "Refund processed and rental dates updated successfully!";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: manage_refunds.php');
    exit();
}

// Fetch refunds with the latest status first
$query = "SELECT DISTINCT r.rental_id, r.user_id,
          rf.refund_id, rf.amount as refund_amount, rf.status as refund_status,
          rf.created_at as refund_created_at, rf.updated_at,
          u.full_name, u.email, u.phone,
          m.name as machine_name
          FROM refunds rf
          JOIN rentals r ON rf.rental_id = r.rental_id
          JOIN users u ON r.user_id = u.user_id
          JOIN machines m ON r.machine_id = m.machine_id
          ORDER BY rf.created_at DESC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Refunds - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold">Manage Refunds</h1>
            <a href="manager_dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Back to Dashboard
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rental ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Machine</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Refund Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while ($refund = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td class="px-6 py-4">#<?php echo $refund['rental_id']; ?></td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($refund['full_name']); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($refund['email']); ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($refund['phone']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($refund['machine_name']); ?></td>
                        <td class="px-6 py-4">₹<?php echo number_format($refund['refund_amount'], 2); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php 
                                switch($refund['refund_status']) {
                                    case 'processed':
                                        echo 'bg-green-100 text-green-800';
                                        break;
                                    case 'pending':
                                        echo 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'failed':
                                        echo 'bg-red-100 text-red-800';
                                        break;
                                }
                                ?>">
                                <?php 
                                switch($refund['refund_status']) {
                                    case 'processed':
                                        echo 'Refund Completed';
                                        break;
                                    case 'pending':
                                        echo 'Awaiting Refund';
                                        break;
                                    case 'failed':
                                        echo 'Refund Failed';
                                        break;
                                }
                                ?>
                                (₹<?php echo number_format($refund['refund_amount'], 2); ?>)
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo date('M d, Y H:i', strtotime($refund['refund_created_at'])); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($refund['refund_status'] == 'pending'): ?>
                                <form method="POST" onsubmit="return confirm('Confirm: Have you processed the refund of ₹<?php echo number_format($refund['refund_amount'], 2); ?> to the customer?');">
                                    <input type="hidden" name="refund_id" value="<?php echo $refund['refund_id']; ?>">
                                    <input type="hidden" name="rental_id" value="<?php echo $refund['rental_id']; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $refund['user_id']; ?>">
                                    <input type="hidden" name="amount" value="<?php echo $refund['refund_amount']; ?>">
                                    <button type="submit" 
                                            name="process_refund" 
                                            class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                                        Process Refund
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="<?php echo $refund['refund_status'] == 'processed' ? 'text-green-500' : 'text-red-500'; ?>">
                                    <?php echo $refund['refund_status'] == 'processed' ? 'Refund Completed' : 'Refund Failed'; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 