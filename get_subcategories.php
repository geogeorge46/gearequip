<?php
// At the top of the file
error_log('get_subcategories.php called');
error_log('POST data: ' . print_r($_POST, true));

include 'config.php';

if (isset($_GET['category_id'])) {
    $category_id = (int)$_GET['category_id'];
    
    $query = "SELECT subcategory_id, subcategory_name 
              FROM subcategories 
              WHERE category_id = ? 
              ORDER BY subcategory_name";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $subcategories = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $subcategories[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($subcategories);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Category ID is required']);
}
?> 