<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch cart items and calculate total
$cart_query = "SELECT c.*, m.name, m.daily_rate, m.image_url 
               FROM cart c 
               JOIN machines m ON c.machine_id = m.machine_id 
               WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $cart_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

// Calculate total
$subtotal = 0;
$total_gst = 0;
$total = 0;

while($item = mysqli_fetch_assoc($cart_result)) {
    $rental_days = (strtotime($item['end_date']) - strtotime($item['start_date'])) / (60 * 60 * 24);
    $item_subtotal = $item['daily_rate'] * $rental_days * $item['quantity'];
    $item_gst = $item_subtotal * 0.18; // 18% GST
    $subtotal += $item_subtotal;
    $total_gst += $item_gst;
}
$total = $subtotal + $total_gst;

// Convert to paise for Razorpay
$total_in_paise = $total * 100;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body class="bg-gray-100">
    <?php include 'nav.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-8 mt-20">
        <h1 class="text-3xl font-bold mb-8">Checkout</h1>

        <!-- Order Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span>Subtotal:</span>
                    <span>₹<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="flex justify-between">
                    <span>GST (18%):</span>
                    <span>₹<?php echo number_format($total_gst, 2); ?></span>
                </div>
                <div class="flex justify-between text-lg font-semibold">
                    <span>Total:</span>
                    <span>₹<?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Button -->
        <button onclick="makePayment()" 
                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
            Pay Now
        </button>
    </div>

    <script>
    function makePayment() {
        var options = {
            "key": "rzp_test_e233tPrR8WUuea", // Replace with your Razorpay Key ID
            "amount": "<?php echo $total_in_paise; ?>",
            "currency": "INR",
            "name": "GEAR EQUIP",
            "description": "Equipment Rental Payment",
            "image": "images/logo.png",
            "handler": function (response){
                // Send payment details to server for verification
                verifyPayment(response);
            },
            "prefill": {
                "name": "<?php echo $_SESSION['full_name']; ?>",
                "email": "<?php echo $_SESSION['email'] ?? ''; ?>",
            },
            "theme": {
                "color": "#3B82F6"
            }
        };
        var rzp1 = new Razorpay(options);
        rzp1.open();
    }

    function verifyPayment(response) {
        console.log('Payment response:', response);
        
        // Check if we have a payment ID
        if (!response.razorpay_payment_id) {
            alert('Payment failed: No payment ID received');
            return;
        }
        
        fetch('verify_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                razorpay_payment_id: response.razorpay_payment_id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'payment_success.php';
            } else {
                alert('Payment verification failed: ' + (data.message || 'Please contact support.'));
            }
        })
        .catch(error => {
            console.error('Verification error:', error);
            alert('An error occurred during payment verification: ' + error.message);
        });
    }
    </script>
</body>
</html> 