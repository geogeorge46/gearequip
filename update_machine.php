<?php
include 'config.php';
session_start();

// Check if user is logged in and is a manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $machine_id = $_POST['machine_id'];
    $available_count = $_POST['available_count'];
    $description = $_POST['description'];

    // Update the available count and description in the database
    $update_query = "UPDATE machines SET available_count = ?, description = ? WHERE machine_id = ?";
    
    // Prepare the statement
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("isi", $available_count, $description, $machine_id);
    
    // Execute the statement
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    
    // Close the statement
    $stmt->close();
}

// Close the database connection
$conn->close();
?> 