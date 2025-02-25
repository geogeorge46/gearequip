<?php
include 'config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if machine_id is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

// Fetch machine details
$machine_id = $_GET['id'];
$sql = "SELECT m.*, c.category_name 
        FROM machines m 
        LEFT JOIN machine_categories mc ON m.machine_id = mc.machine_id 
        LEFT JOIN categories c ON mc.category_id = c.category_id 
        WHERE m.machine_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $machine_id);
$stmt->execute();
$result = $stmt->get_result();
$machine = $result->fetch_assoc();

// If machine doesn't exist or is not available
if (!$machine || $machine['status'] !== 'available') {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent Machine - GEAR EQUIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Fixed Header -->
    <header class="bg-white shadow-md fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="images/logo.png" alt="GEAR EQUIP Logo" class="h-10">
                    <span class="text-xl font-bold text-gray-800">GEAR EQUIP</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></span>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content with proper spacing from fixed header -->
    <div class="max-w-7xl mx-auto px-4 py-16 mt-20">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="md:flex">
                <!-- Machine Image -->
                <div class="md:flex-shrink-0 md:w-1/2">
                    <img class="h-96 w-full object-cover" 
                         src="<?php echo !empty($machine['image_url']) ? htmlspecialchars($machine['image_url']) : 'images/default-machine.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($machine['name']); ?>">
                </div>

                <!-- Machine Details -->
                <div class="p-8 md:w-1/2">
                    <div class="uppercase tracking-wide text-sm text-indigo-500 font-semibold">
                        <?php echo htmlspecialchars($machine['category_name'] ?? 'Uncategorized'); ?>
                    </div>
                    <h1 class="mt-2 text-3xl font-bold text-gray-900">
                        <?php echo htmlspecialchars($machine['name']); ?>
                    </h1>
                    <p class="mt-4 text-gray-600">
                        <?php echo htmlspecialchars($machine['description']); ?>
                    </p>
                    
                    <!-- Rental Details Form -->
                    <form action="process_rental.php" method="POST" class="mt-8">
                        <input type="hidden" name="machine_id" value="<?php echo $machine_id; ?>">
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="start_date">
                                Start Date
                            </label>
                            <input type="date" 
                                   name="start_date" 
                                   id="start_date" 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                   required
                                   onchange="calculateDays()">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="end_date">
                                End Date
                            </label>
                            <input type="date" 
                                   name="end_date" 
                                   id="end_date" 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                   required
                                   onchange="calculateDays()">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="rental_days">
                                Number of Days
                            </label>
                            <input type="number" 
                                   name="rental_days" 
                                   id="rental_days" 
                                   readonly
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-100">
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Daily Rate
                            </label>
                            <p class="text-2xl font-bold text-green-600">
                                ₹<?php echo number_format($machine['daily_rate'], 2); ?>
                            </p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Total Amount
                            </label>
                            <p class="text-3xl font-bold text-blue-600" id="totalAmount">
                                ₹<?php echo number_format($machine['daily_rate'], 2); ?>
                            </p>
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="button" 
                                    id="payButton"
                                    // onclick="makePayment()" 
                                    class="bg-gray-400 cursor-not-allowed text-white px-6 py-3 rounded-lg"
                                    disabled>
                                Pay with Razorpay
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
    function calculateDays() {
        const startDate = new Date(document.getElementById('start_date').value);
        const endDate = new Date(document.getElementById('end_date').value);
        const payButton = document.getElementById('payButton');
        
        if (startDate && endDate) {
            if (endDate < startDate) {
                alert('End date cannot be before start date');
                document.getElementById('end_date').value = '';
                payButton.disabled = true;
                payButton.classList.add('bg-gray-400', 'cursor-not-allowed');
                payButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                return;
            }
            
            // Calculate the difference in days
            const diffTime = Math.abs(endDate - startDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // Add 1 to include both start and end dates
            
            // Update rental days
            document.getElementById('rental_days').value = diffDays;
            
            // Calculate and update total amount
            const dailyRate = <?php echo $machine['daily_rate']; ?>;
            const total = diffDays * dailyRate;
            document.getElementById('totalAmount').textContent = '₹' + total.toFixed(2);
            
            // Enable payment button
            payButton.disabled = false;
            payButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
            payButton.classList.add('bg-blue-500', 'hover:bg-blue-600');
        } else {
            // Disable payment button if dates are not selected
            payButton.disabled = true;
            payButton.classList.add('bg-gray-400', 'cursor-not-allowed');
            payButton.classList.remove('bg-blue-500', 'hover:bg-blue-600');
        }
    }

        function makePayment() {
            const totalAmount = document.getElementById('totalAmount').innerText.replace('₹', '').replace(',', '');
            const days = document.getElementById('rental_days').value;
            
            if (!days || days <= 0) {
                alert('Please select valid rental dates');
                return;
            }
            
            var options = {
                "key": "rzp_test_e233tPrR8WUuea", // Replace with your Razorpay Key ID
                "amount": parseFloat(totalAmount) * 100, // Amount in paise
                "currency": "INR",
                "name": "GEAR EQUIP",
                "description": "Equipment Rental Payment",
                "image": "images/logo.png",
                "handler": function (response) {
                    // On successful payment
                    document.location.href = 'process_rental.php?' + 
                        'payment_id=' + response.razorpay_payment_id + 
                        '&machine_id=<?php echo $machine_id; ?>' +
                        '&days=' + days +
                        '&amount=' + totalAmount +
                        '&start_date=' + document.getElementById('start_date').value +
                        '&end_date=' + document.getElementById('end_date').value;
                },
                "prefill": {
                    "name": "<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>",
                    "email": "<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>",
                    "contact": "<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>"
                },
                "theme": {
                    "color": "#3B82F6"
                }
            };

            var rzp1 = new Razorpay(options);
            rzp1.open();
        }

    // Set minimum date for end_date based on start_date
    document.getElementById('start_date').addEventListener('change', function() {
        document.getElementById('end_date').min = this.value;
        calculateDays();
    });

    document.getElementById('end_date').addEventListener('change', calculateDays);
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>