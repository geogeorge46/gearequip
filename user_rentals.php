<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user's rental history
$user_id = $_SESSION['user_id'];
$rentals_query = "SELECT r.*, m.name as machine_name, m.image_url 
                  FROM rentals r 
                  JOIN machines m ON r.machine_id = m.machine_id 
                  WHERE r.user_id = ? 
                  ORDER BY r.created_at DESC";

$stmt = $conn->prepare($rentals_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$rentals = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Rentals - GEAR EQUIP</title>
    <link href="styles.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    

    <div class="content">
        <?php include 'nav.php'; ?>

        <div class="container mx-auto px-4 py-8 mt-32">
            <div class="flex justify-between items-center mb-8">
                <a href="dashboard.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition duration-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
                <h1 class="text-3xl font-bold">Your Rentals</h1>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Machine</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php while ($rental = $rentals->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($rental['machine_name']); ?></td>
                            <td class="px-6 py-4">
                                <img src="<?php echo htmlspecialchars($rental['image_url']); ?>" 
                                     class="w-20 h-20 object-cover rounded-lg" 
                                     alt="<?php echo htmlspecialchars($rental['machine_name']); ?>">
                            </td>
                            <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($rental['start_date'])); ?></td>
                            <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($rental['end_date'])); ?></td>
                            <td class="px-6 py-4">₹<?php echo number_format($rental['total_amount'], 2); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $rental['status'] == 'active' ? 'bg-green-100 text-green-800' : 
                                        ($rental['status'] == 'completed' ? 'bg-blue-100 text-blue-800' : 
                                        'bg-gray-100 text-gray-800'); ?>">
                                    <?php echo ucfirst($rental['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button onclick="generateInvoice(<?php echo $rental['rental_id']; ?>)" 
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Invoice
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add this modal for invoice -->
    <div id="invoiceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-4/5 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div id="invoiceContent" class="mt-3">
                <!-- Invoice content will be loaded here -->
            </div>
        </div>
    </div>

    <script>
    function generateInvoice(rentalId) {
        fetch(`generate_invoice.php?rental_id=${rentalId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('invoiceContent').innerHTML = data;
                document.getElementById('invoiceModal').classList.remove('hidden');
            });
    }

    // Close modal when clicking outside
    document.getElementById('invoiceModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });
    </script>
</body>
</html> 