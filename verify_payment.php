<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get the payment details from POST request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data']);
    exit();
}

// Verify we have the required payment data
$razorpay_payment_id = $data['razorpay_payment_id'] ?? null;
if (!$razorpay_payment_id) {
    echo json_encode(['success' => false, 'message' => 'Payment ID not found']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Get cart items
    $cart_query = "SELECT c.*, m.daily_rate 
                  FROM cart c 
                  JOIN machines m ON c.machine_id = m.machine_id 
                  WHERE c.user_id = ?";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $cart_items = $stmt->get_result();

    // Create rentals for each cart item
    while ($item = $cart_items->fetch_assoc()) {
        $rental_days = (strtotime($item['end_date']) - strtotime($item['start_date'])) / (60 * 60 * 24);
        $total_amount = $item['daily_rate'] * $rental_days * $item['quantity'];

        // Insert rental record
        $rental_query = "INSERT INTO rentals (user_id, machine_id, rental_days, 
                                            total_amount, start_date, end_date, 
                                            status, payment_id, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, 'active', ?, NOW())";
        
        $stmt = $conn->prepare($rental_query);
        $stmt->bind_param("iiidsss", 
            $_SESSION['user_id'],
            $item['machine_id'],
            $rental_days,
            $total_amount,
            $item['start_date'],
            $item['end_date'],
            $razorpay_payment_id
        );
        $stmt->execute();

        // Update machine status and available count
        $update_machine = "UPDATE machines 
                         SET status = CASE 
                                        WHEN available_count <= 1 THEN 'rented'
                                        ELSE status 
                                    END,
                             available_count = GREATEST(available_count - ?, 0)
                         WHERE machine_id = ?";
        $stmt = $conn->prepare($update_machine);
        $stmt->bind_param("ii", $item['quantity'], $item['machine_id']);
        $stmt->execute();
    }

    // Clear the cart
    $clear_cart = "DELETE FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($clear_cart);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 