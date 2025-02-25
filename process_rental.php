<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['machine_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$machine_id = $_POST['machine_id'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$rental_days = $_POST['rental_days'];

// Validate dates
$start = new DateTime($start_date);
$end = new DateTime($end_date);
$today = new DateTime();

if ($start < $today) {
    $_SESSION['error'] = "Start date cannot be in the past";
    header('Location: rent.php?id=' . $machine_id);
    exit();
}

if ($end < $start) {
    $_SESSION['error'] = "End date cannot be before start date";
    header('Location: rent.php?id=' . $machine_id);
    exit();
}

// Calculate total amount
$sql = "SELECT daily_rate FROM machines WHERE machine_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $machine_id);
$stmt->execute();
$result = $stmt->get_result();
$machine = $result->fetch_assoc();

$total_amount = $machine['daily_rate'] * $rental_days;

// Create rental record
$sql = "INSERT INTO rentals (user_id, machine_id, rental_days, total_amount, start_date, end_date, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiidss", 
    $_SESSION['user_id'], 
    $machine_id, 
    $rental_days, 
    $total_amount,
    $start_date,
    $end_date
);

if ($stmt->execute()) {
    $rental_id = $stmt->insert_id;
    header("Location: payment.php?rental_id=" . $rental_id);
} else {
    $_SESSION['error'] = "Error processing rental request";
    header("Location: rent.php?id=" . $machine_id);
}
exit();
?>