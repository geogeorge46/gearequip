<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'User not logged in';
    header('Location: cart.php');
    exit;
}

// Get cart_id from GET parameter
if (isset($_GET['cart_id'])) {
    $cart_id = (int)$_GET['cart_id'];
    
    // Delete the cart item
    $delete_query = "DELETE FROM cart WHERE cart_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $_SESSION['user_id']);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Item removed from cart successfully";
    } else {
        $_SESSION['error'] = "Error removing item from cart";
    }
    
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error'] = "Invalid request";
}

// Redirect back to cart
header('Location: cart.php');
exit;
?> 