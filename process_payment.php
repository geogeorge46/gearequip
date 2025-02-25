<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['rental_id'])) {
    header('Location: index.php');
    exit();
}

$rental_id = $_POST['rental_id'];
$user_id = $_SESSION['user_id'];

// Update rental status only
$sql = "UPDATE rentals 
        SET status = 'paid'
        WHERE rental_id = ? AND user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $rental_id, $user_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Payment successful! Your equipment rental has been confirmed.";
    header('Location: dashboard.php');
} else {
    $_SESSION['error_message'] = "Payment failed. Please try again.";
    header('Location: payment.php?rental_id=' . $rental_id);
}
exit();
?>