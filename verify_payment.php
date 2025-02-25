<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$payment_id = $_GET['payment_id'];
$rental_id = $_GET['rental_id'];
$amount = $_GET['amount'];

// Update rental status and payment details in database
$sql = "UPDATE rentals SET 
        payment_status = 'paid',
        payment_id = ?,
        updated_at = NOW()
        WHERE rental_id = ? AND user_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $payment_id, $rental_id, $_SESSION['user_id']);

if ($stmt->execute()) {
    header('Location: payment_success.php');
} else {
    header('Location: payment_failed.php');
}
exit(); 