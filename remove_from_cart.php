<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['cart_id'])) {
    $cart_id = (int)$_GET['cart_id'];
    $user_id = $_SESSION['user_id'];

    $delete_query = "DELETE FROM cart WHERE cart_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
    mysqli_stmt_execute($stmt);

    $_SESSION['success'] = "Item removed from cart!";
}

header('Location: cart.php');
exit(); 