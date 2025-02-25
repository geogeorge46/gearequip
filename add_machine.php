<?php
include 'config.php';
session_start();

// Define the target directory for uploaded images
$target_dir = "uploads/"; // Ensure this directory exists

// Check if user is logged in and is a manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Fetch categories from the database
$categories = [];
$category_query = "SELECT category_id, category_name FROM categories"; // Use correct column names
$result = $conn->query($category_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch machines from the database
$machines = [];
$machine_query = "SELECT machine_id, name, daily_rate, image_url, status, available_count FROM machines"; // Include available_count
$result = $conn->query($machine_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $machines[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: Check if form data is received
    echo "<p>Form submitted!</p>";

    $name = $_POST['name'];
    $daily_rate = $_POST['daily_rate'];
    $status = $_POST['status'];
    $description = $_POST['description'];
    $selected_categories = isset($_POST['category_ids']) ? $_POST['category_ids'] : [];

    // Handle image upload
    $image_url = '';
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $target_dir = "uploads/";
        
        // Create uploads directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Generate unique filename
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        // Debug: Print upload information
        echo "Debug - Upload path: " . $target_file . "<br>";
        echo "Debug - File type: " . $_FILES["image"]["type"] . "<br>";
        echo "Debug - File size: " . $_FILES["image"]["size"] . "<br>";

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = $target_file;
            echo "Debug - File uploaded successfully to: " . $image_url . "<br>";
        } else {
            echo "Debug - Upload failed. Error: " . error_get_last()['message'] . "<br>";
        }
    }

    // Prepare the SQL statement
    $insert_query = "INSERT INTO machines (name, daily_rate, image_url, status, description, available_count) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);

    if (!$stmt) {
        echo "Error preparing statement: " . $conn->error;
        exit();
    }

    $available_count = 1; // Set initial available count to 1
    
    // Bind parameters
    $stmt->bind_param("sdsssi", $name, $daily_rate, $image_url, $status, $description, $available_count);
    
    // Execute the statement
    if ($stmt->execute()) {
        $machine_id = $stmt->insert_id;
        
        // Insert categories
        foreach ($selected_categories as $category_id) {
            $link_query = "INSERT INTO machine_categories (machine_id, category_id) VALUES (?, ?)";
            $link_stmt = $conn->prepare($link_query);
            $link_stmt->bind_param("ii", $machine_id, $category_id);
            $link_stmt->execute();
        }
        
        $_SESSION['success_message'] = "Machine added successfully!";
        header('Location: manager_dashboard.php');
        exit();
    }
}

// Display success message if set
if (isset($_SESSION['success_message'])) {
    echo "<p class='success-message'>" . $_SESSION['success_message'] . "</p>";
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Machine - GEAR EQUIP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
<header class="bg-white shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="images/logo.png" alt="GEAR EQUIP Logo" class="h-10">
                <span class="text-xl font-bold text-gray-800">GEAR EQUIP</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">Logout</a>
            </div>
        </div>
    </div>
</header>

<div class="max-w-7xl mx-auto px-4 py-16 mt-20">
    <h2 class="text-2xl font-bold mb-6">Add New Machine</h2>
    <form id="addMachineForm" action="add_machine.php" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md">
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">Machine Name</label>
            <input type="text" name="name" id="name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
        </div>
        <div class="mb-4">
            <label for="daily_rate" class="block text-sm font-medium text-gray-700">Daily Rate</label>
            <input type="number" name="daily_rate" id="daily_rate" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" step="0.01">
        </div>
        <div class="mb-4">
            <label for="image" class="block text-sm font-medium text-gray-700">Upload Image</label>
            <input type="file" name="image" id="image" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
        </div>
        <div class="mb-4">
            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
            <select name="status" id="status" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                <option value="available">Available</option>
                <option value="rented">Rented</option>
                <option value="maintenance">Maintenance</option>
            </select>
        </div>
        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea name="description" id="description" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"></textarea>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Categories</label>
            <?php foreach ($categories as $category): ?>
                <div class="flex items-center mb-2">
                    <input type="checkbox" name="category_ids[]" value="<?php echo $category['category_id']; ?>" id="category_<?php echo $category['category_id']; ?>" class="mr-2">
                    <label for="category_<?php echo $category['category_id']; ?>" class="text-sm text-gray-600"><?php echo htmlspecialchars($category['category_name']); ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">Add Machine</button>
    </form>
</div>

<!-- Add this JavaScript code before closing body tag -->
<script>
document.getElementById('addMachineForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent default form submission
    
    // Create FormData object from the form
    const formData = new FormData(this);
    
    // Send form data using fetch
    fetch('add_machine.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        console.log('Machine added successfully!');
        // Redirect to manager dashboard
        window.location.href = 'manager_dashboard.php';
    })
    .catch(error => {
        console.error('Error:', error);
    });
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>