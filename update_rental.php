<?php
include 'config.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        mysqli_begin_transaction($conn);

        // Get rental details
        $rental_id = (int)$_POST['rental_id'];
        $new_start_date = $_POST['new_start_date'];
        $new_end_date = $_POST['new_end_date'];
        $manager_id = (int)$_SESSION['user_id'];

        // Get current rental details
        $stmt = mysqli_prepare($conn, "SELECT r.*, m.daily_rate, m.name as machine_name,
                                     u.user_id, u.full_name, p.razorpay_payment_id 
                                     FROM rentals r 
                                     JOIN machines m ON r.machine_id = m.machine_id 
                                     JOIN users u ON r.user_id = u.user_id
                                     LEFT JOIN payments p ON r.rental_id = p.rental_id
                                     WHERE r.rental_id = ?");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare rental query: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "i", $rental_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rental = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$rental) {
            throw new Exception("Rental not found");
        }

        // Calculate amounts
        $old_days = (strtotime($rental['end_date']) - strtotime($rental['start_date'])) / (60 * 60 * 24);
        $new_days = (strtotime($new_end_date) - strtotime($new_start_date)) / (60 * 60 * 24);
        
        $old_amount = floatval($rental['total_amount']);
        $new_amount = floatval(($rental['daily_rate'] * $new_days) * 1.18);
        $difference = $new_amount - $old_amount;
        $abs_difference = abs($difference);
        
        $update_type = $difference > 0 ? 'increase' : 'decrease';
        $payment_status = $difference > 0 ? 'pending' : 'refunded';

        // First insert basic info
        $insert_update = "INSERT INTO rental_updates 
            (rental_id, updated_by) VALUES (?, ?)";
        
        $stmt = mysqli_prepare($conn, $insert_update);
        mysqli_stmt_bind_param($stmt, "ii", $rental_id, $manager_id);
        mysqli_stmt_execute($stmt);
        $update_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Then update with dates
        $update_dates = "UPDATE rental_updates SET 
            old_start_date = ?,
            old_end_date = ?,
            new_start_date = ?,
            new_end_date = ?
            WHERE update_id = ?";
        
        $stmt = mysqli_prepare($conn, $update_dates);
        mysqli_stmt_bind_param($stmt, "ssssi", 
            $rental['start_date'],
            $rental['end_date'],
            $new_start_date,
            $new_end_date,
            $update_id
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Finally update amounts and status
        $update_amounts = "UPDATE rental_updates SET 
            old_total_amount = ?,
            new_total_amount = ?,
            difference_amount = ?,
            update_type = ?,
            payment_status = ?
            WHERE update_id = ?";
        
        $stmt = mysqli_prepare($conn, $update_amounts);
        mysqli_stmt_bind_param($stmt, "dddssi", 
            $old_amount,
            $new_amount,
            $abs_difference,
            $update_type,
            $payment_status,
            $update_id
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Handle refund if days are reduced
        if ($difference < 0) {
            $refund_amount = abs($difference);
            $refund_status = 'pending';
            $refund_notes = 'Rental days reduced';
            
            // Store all variables before binding
            $rental_id_ref = $rental_id;
            $update_id_ref = $update_id;
            $refund_amount_ref = $refund_amount;
            $refund_notes_ref = $refund_notes;
            $manager_id_ref = $manager_id;
            
            // Insert into refunds table
            $refund_query = "INSERT INTO refunds (
                rental_id, 
                update_id, 
                amount, 
                status,
                refund_notes, 
                processed_by
            ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $refund_query);
            mysqli_stmt_bind_param(
                $stmt, 
                "iidssi", 
                $rental_id_ref, 
                $update_id_ref, 
                $refund_amount_ref, 
                $refund_status,
                $refund_notes_ref, 
                $manager_id_ref
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to create refund record: " . mysqli_error($conn));
            }
            
            // Create notification for user
            $user_notification = "INSERT INTO user_notifications (user_id, message, type) 
                                 VALUES (?, ?, 'refund_pending')";
            $notification_message = "A refund of ₹" . number_format($refund_amount, 2) . " has been initiated for your rental #$rental_id";
            
            $stmt = mysqli_prepare($conn, $user_notification);
            mysqli_stmt_bind_param($stmt, "is", $rental['user_id'], $notification_message);
            mysqli_stmt_execute($stmt);
        }

        mysqli_commit($conn);
        $_SESSION['success'] = "Rental dates updated successfully." . 
                             ($difference < 0 ? " Refund has been initiated." : "");

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error: " . $e->getMessage();
        error_log("Update Rental Error: " . $e->getMessage());
    }

    header('Location: manager_approvals.php');
    exit();
}
?> 
} 
} 
} 
} 
} 
} 
} 
} 