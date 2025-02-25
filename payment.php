<?php
include 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if rental_id is provided
if (!isset($_GET['rental_id'])) {
    header('Location: index.php');
    exit();
}

// Fetch rental details
$rental_id = $_GET['rental_id'];
$sql = "SELECT r.*, m.name as machine_name, m.daily_rate, m.image_url 
        FROM rentals r 
        JOIN machines m ON r.machine_id = m.machine_id 
        WHERE r.rental_id = ? AND r.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $rental_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$rental = $result->fetch_assoc();

// If rental doesn't exist or doesn't belong to user
if (!$rental) {
    header('Location: index.php');
    exit();
}

// Fetch rental details from session or POST data
$amount = $_POST['amount'] * 100; // Convert to paise
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - GEAR EQUIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Fixed Header -->
    <header class="bg-white shadow-md fixed top-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <img src="images/logo.png" alt="GEAR EQUIP Logo" class="h-10">
                    <span class="text-xl font-bold text-gray-800">GEAR EQUIP</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-16 mt-20">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="md:flex">
                <!-- Order Summary -->
                <div class="md:w-1/2 p-8 border-r">
                    <h2 class="text-2xl font-bold mb-6">Order Summary</h2>
                    <div class="flex items-center mb-6">
                        <img src="<?php echo !empty($rental['image_url']) ? htmlspecialchars($rental['image_url']) : 'images/default-machine.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($rental['machine_name']); ?>"
                             class="w-24 h-24 object-cover rounded-lg">
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($rental['machine_name']); ?></h3>
                            <p class="text-gray-600">Rental Duration: <?php echo $rental['rental_days']; ?> days</p>
                            <p class="text-gray-600">Daily Rate: ₹<?php echo number_format($rental['daily_rate'], 2); ?></p>
                        </div>
                    </div>
                    <div class="border-t pt-4">
                        <div class="flex justify-between mb-2">
                            <span>Subtotal</span>
                            <span>₹<?php echo number_format($rental['total_amount'], 2); ?></span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span>Security Deposit (Refundable)</span>
                            <span>₹<?php echo number_format($rental['total_amount'] * 0.2, 2); ?></span>
                        </div>
                        <div class="flex justify-between font-bold text-lg border-t pt-2">
                            <span>Total Amount</span>
                            <span>₹<?php echo number_format($rental['total_amount'] * 1.2, 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="md:w-1/2 p-8">
                    <h2 class="text-2xl font-bold mb-6">Complete Payment</h2>
                    <div class="mb-6">
                        <p class="text-gray-600">Total Amount: ₹<?php echo number_format($_POST['amount'], 2); ?></p>
                    </div>
                    <button id="payButton" 
                            class="w-full bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition">
                        Pay Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    var options = {
        "key": "YOUR_RAZORPAY_KEY_ID", // Replace with your key
        "amount": "<?php echo $amount; ?>",
        "currency": "INR",
        "name": "GEAR EQUIP",
        "description": "Equipment Rental Payment",
        "image": "images/logo.png",
        "handler": function (response){
            // On successful payment, redirect to verification page
            document.location.href = 'verify_payment.php?payment_id=' + response.razorpay_payment_id + 
                                   '&rental_id=<?php echo $rental_id; ?>&amount=<?php echo $_POST['amount']; ?>';
        },
        "prefill": {
            "name": "<?php echo $_SESSION['full_name']; ?>",
            "email": "<?php echo $_SESSION['email']; ?>",
            "contact": "<?php echo $_SESSION['phone']; ?>"
        },
        "theme": {
            "color": "#3B82F6"
        }
    };

    document.getElementById('payButton').onclick = function(e){
        var rzp1 = new Razorpay(options);
        rzp1.open();
        e.preventDefault();
    }
    </script>

    <?php include 'footer.php'; ?>
</body>
</html> 