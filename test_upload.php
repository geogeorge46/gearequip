<?php
$target_dir = "uploads/";
$test_file = $target_dir . "test.txt";

// Try to create the directory
if (!file_exists($target_dir)) {
    if (mkdir($target_dir, 0777, true)) {
        echo "Directory created successfully<br>";
    } else {
        echo "Failed to create directory<br>";
    }
}

// Try to write a file
if (file_put_contents($test_file, "Test content")) {
    echo "Test file created successfully<br>";
    echo "Full path: " . realpath($test_file) . "<br>";
} else {
    echo "Failed to create test file<br>";
}

// Check directory permissions
echo "Directory permissions: " . substr(sprintf('%o', fileperms($target_dir)), -4) . "<br>";
?> 