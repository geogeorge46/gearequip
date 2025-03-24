<?php
// At the top of the file
error_log('get_subcategories.php called');
error_log('POST data: ' . print_r($_POST, true));

include 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Log the request
error_log('get_subcategories.php called with category_id: ' . $_GET['category_id']);

if (isset($_GET['category_id'])) {
    $category_id = (int)$_GET['category_id'];
    
    try {
        // Prepare statement
        $query = "SELECT subcategory_id, subcategory_name 
                 FROM subcategories 
                 WHERE category_id = ? 
                 ORDER BY subcategory_name";
        
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . mysqli_error($conn));
        }
        
        // Bind parameter and execute
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
        }
        
        // Get results
        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            throw new Exception("Result failed: " . mysqli_error($conn));
        }
        
        // Fetch all subcategories
        $subcategories = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $subcategories[] = array(
                'subcategory_id' => $row['subcategory_id'],
                'subcategory_name' => $row['subcategory_name']
            );
        }
        
        // Output JSON
        echo json_encode($subcategories);
        
        // Close statement
        mysqli_stmt_close($stmt);
        
    } catch (Exception $e) {
        error_log('Error in get_subcategories.php: ' . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Category ID is required']);
}

// Close connection
mysqli_close($conn);
?> 