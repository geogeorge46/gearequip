<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $machine_id = $_POST['machine_id'];
    $quantity = intval($_POST['quantity']);
    $user_id = $_SESSION['user_id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if machine is available and has enough units
        $check_query = "SELECT available_count, status FROM machines WHERE machine_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $machine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $machine = $result->fetch_assoc();

        if (!$machine) {
            throw new Exception("Machine not found");
        }

        if ($machine['status'] != 'available' || $machine['available_count'] < $quantity) {
            throw new Exception("Not enough units available");
        }

        // Check if machine already in cart
        $cart_check = "SELECT cart_id FROM cart WHERE user_id = ? AND machine_id = ?";
        $stmt = $conn->prepare($cart_check);
        $stmt->bind_param("ii", $user_id, $machine_id);
        $stmt->execute();
        $existing_cart = $stmt->get_result();

        if ($existing_cart->num_rows > 0) {
            // Update existing cart item
            $update_query = "UPDATE cart 
                            SET quantity = ?, 
                                start_date = CURRENT_DATE, 
                                end_date = DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY) 
                            WHERE user_id = ? AND machine_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("iii", $quantity, $user_id, $machine_id);
            $stmt->execute();
        } else {
            // Add new cart item
            $insert_query = "INSERT INTO cart (user_id, machine_id, quantity, start_date, end_date) 
                             VALUES (?, ?, ?, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY))";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iii", $user_id, $machine_id, $quantity);
            $stmt->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>