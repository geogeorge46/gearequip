<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch cart items
$cart_query = "SELECT c.*, m.name, m.daily_rate, m.image_url, m.status, m.available_count 
               FROM cart c 
               JOIN machines m ON c.machine_id = m.machine_id 
               WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $cart_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$cart_result = mysqli_stmt_get_result($stmt);

// Initialize variables
$subtotal = 0;
$total_gst = 0;
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add date picker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 0 20px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cart-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        .cart-info {
            flex: 1;
            margin-left: 20px;
        }
        .remove-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
        }
        .checkout-btn {
            background: #28a745;
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .date-inputs {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .date-input {
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 130px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'nav.php'; ?>

    <div class="cart-container">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Your Cart</h1>
            <a href="machines.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Back to Machines
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 text-green-700 p-4 mb-4 rounded">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($cart_result) > 0): ?>
            <form method="post" action="update_cart.php">
                <?php 
                while($item = mysqli_fetch_assoc($cart_result)): 
                    $rental_days = (strtotime($item['end_date']) - strtotime($item['start_date'])) / (60 * 60 * 24);
                    $item_subtotal = $item['daily_rate'] * $rental_days * $item['quantity'];
                    $item_gst = $item_subtotal * 0.18; // 18% GST
                    $item_total = $item_subtotal + $item_gst;
                    $subtotal += $item_subtotal;
                    $total_gst += $item_gst;
                    $total += $item_total;
                ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             class="cart-image">
                        <div class="cart-info">
                            <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="text-gray-600">₹<?php echo number_format($item['daily_rate'], 2); ?> / day</p>
                            <div class="date-inputs">
                                <div>
                                    <label>Start Date:</label>
                                    <input type="date" 
                                           name="start_date[<?php echo $item['cart_id']; ?>]" 
                                           value="<?php echo $item['start_date']; ?>"
                                           min="<?php echo date('Y-m-d'); ?>"
                                           class="date-input"
                                           required>
                                </div>
                                <div>
                                    <label>End Date:</label>
                                    <input type="date" 
                                           name="end_date[<?php echo $item['cart_id']; ?>]" 
                                           value="<?php echo $item['end_date']; ?>"
                                           min="<?php echo date('Y-m-d'); ?>"
                                           class="date-input"
                                           required>
                                </div>
                            </div>
                            <p class="mt-2">
                                Rental Duration: <?php echo $rental_days; ?> days
                                (₹<?php echo number_format($item['daily_rate'] * $rental_days, 2); ?>)
                            </p>
                        </div>
                        <a href="remove_from_cart.php?cart_id=<?php echo $item['cart_id']; ?>" 
                           class="remove-btn"
                           onclick="return confirm('Are you sure you want to remove this item?')">
                            Remove
                        </a>
                    </div>
                <?php endwhile; ?>

                <!-- Cart Summary -->
                <div class="bg-white p-6 rounded-lg shadow-md mt-6">
                    <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                    <div class="flex justify-between mb-2">
                        <span>Subtotal:</span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span>GST (18%):</span>
                        <span>₹<?php echo number_format($total_gst, 2); ?></span>
                    </div>
                    <div class="flex justify-between text-xl font-bold">
                        <span>Total:</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update Cart</button>
                    <a href="checkout.php" class="checkout-btn ml-4">Proceed to Checkout</a>
                </div>
            </form>
        <?php else: ?>
            <p class="text-center text-gray-600">Your cart is empty.</p>
            <p class="text-center mt-4">
                <a href="machines.php" class="text-blue-500 hover:underline">Browse Machines</a>
            </p>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize date pickers
        const dateInputs = document.querySelectorAll('.date-input');
        dateInputs.forEach(input => {
            input.addEventListener('change', function() {
                const cartItem = this.closest('.cart-item');
                const startDate = cartItem.querySelector('input[name^="start_date"]').value;
                const endDate = cartItem.querySelector('input[name^="end_date"]').value;
                
                if(startDate && endDate) {
                    if(new Date(endDate) < new Date(startDate)) {
                        alert('End date cannot be earlier than start date');
                        this.value = '';
                    }
                }
            });
        });
    });
    </script>
</body>
</html> 