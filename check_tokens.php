<?php
require_once 'config.php';

// Check if the table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'password_reset_tokens'");
if (mysqli_num_rows($table_check) == 0) {
    echo "Table 'password_reset_tokens' does not exist!\n";
    echo "Creating table...\n";
    
    $create_table = "CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL,
        expiry DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )";
    
    if (mysqli_query($conn, $create_table)) {
        echo "Table created successfully!\n";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "\n";
    }
}

// Show all tokens
$query = "SELECT t.*, u.email, u.full_name, 
          CASE WHEN t.expiry > NOW() THEN 'Valid' ELSE 'Expired' END as status
          FROM password_reset_tokens t 
          JOIN users u ON t.user_id = u.user_id";
$result = mysqli_query($conn, $query);

echo "\nCurrent Tokens:\n";
echo "----------------------------------------\n";
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "User: " . $row['full_name'] . " (" . $row['email'] . ")\n";
        echo "Token: " . $row['token'] . "\n";
        echo "Expiry: " . $row['expiry'] . "\n";
        echo "Status: " . $row['status'] . "\n";
        echo "----------------------------------------\n";
    }
} else {
    echo "No tokens found in database\n";
}

// Show current server time
echo "\nServer Time: " . date('Y-m-d H:i:s') . "\n";
