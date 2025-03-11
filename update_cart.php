<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['start_date']) && isset($_POST['end_date']) && isset($_POST['quantity'])) {
    foreach ($_POST['start_date'] as $cart_id => $start_date) {
        $cart_id = (int)$cart_id;
        $start_date = mysqli_real_escape_string($conn, $_POST['start_date'][$cart_id]);
        $end_date = mysqli_real_escape_string($conn, $_POST['end_date'][$cart_id]);
        $quantity = (int)$_POST['quantity'][$cart_id];
        $user_id = $_SESSION['user_id'];

        // Validate dates
        if (strtotime($end_date) < strtotime($start_date)) {
            $_SESSION['error'] = "End date cannot be earlier than start date";
            header('Location: cart.php');
            exit();
        }

        // Check available quantity
        $check_query = "SELECT available_count FROM machines m 
                       JOIN cart c ON m.machine_id = c.machine_id 
                       WHERE c.cart_id = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "i", $cart_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $machine = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($quantity > $machine['available_count']) {
            $_SESSION['error'] = "Requested quantity exceeds available machines";
            header('Location: cart.php');
            exit();
        }

        // Update cart
        $update_query = "UPDATE cart 
                        SET start_date = ?, 
                            end_date = ?,
                            quantity = ?,
                            rental_days = DATEDIFF(?, ?) + 1
                        WHERE cart_id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssiisii", 
            $start_date, 
            $end_date, 
            $quantity,
            $end_date, 
            $start_date, 
            $cart_id, 
            $user_id
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    $_SESSION['success'] = "Cart updated successfully!";
}

header('Location: cart.php');
exit(); 