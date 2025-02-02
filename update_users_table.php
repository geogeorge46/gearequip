<?php
include 'config.php';

// Add role column to users table
$sql = "ALTER TABLE users 
        ADD COLUMN role ENUM('user', 'manager', 'admin') DEFAULT 'user'";

if ($conn->query($sql) === TRUE) {
    echo "Role column added successfully to users table<br>";
    
    // Now create the admin account
    $admin_email = "admin@gearequip.com";
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    $admin_name = "Admin";
    $role = "admin";

    $sql = "INSERT INTO users (full_name, email, password, role) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $admin_name, $admin_email, $admin_password, $role);

    if ($stmt->execute()) {
        echo "Admin account created successfully!<br>";
        echo "Email: admin@gearequip.com<br>";
        echo "Password: admin123";
    } else {
        echo "Error creating admin account: " . $conn->error;
    }
} else {
    echo "Error adding role column: " . $conn->error;
}

$conn->close();
?>  