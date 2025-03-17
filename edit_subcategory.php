<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

$subcategory_id = mysqli_real_escape_string($conn, $_GET['id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
    $subcategory_name = mysqli_real_escape_string($conn, $_POST['subcategory_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $update_query = "UPDATE subcategories SET 
                     category_id = '$category_id',
                     subcategory_name = '$subcategory_name',
                     description = '$description'
                     WHERE subcategory_id = '$subcategory_id'";
    
    if (mysqli_query($conn, $update_query)) {
        $success_message = "Subcategory updated successfully!";
    } else {
        $error_message = "Error updating subcategory: " . mysqli_error($conn);
    }
}

// Fetch subcategory data
$query = "SELECT * FROM subcategories WHERE subcategory_id = '$subcategory_id'";
$result = mysqli_query($conn, $query);
$subcategory = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subcategory - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <a href="manage_subcategories.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Subcategories
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Edit Subcategory</h2>
            <form method="POST" action="">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select Category
                        </label>
                        <select name="category_id" required 
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php
                            $categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");
                            while($category = mysqli_fetch_assoc($categories)) {
                                $selected = ($category['category_id'] == $subcategory['category_id']) ? 'selected' : '';
                                echo "<option value='" . $category['category_id'] . "' $selected>" . 
                                     htmlspecialchars($category['category_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Subcategory Name
                        </label>
                        <input type="text" 
                               name="subcategory_name" 
                               value="<?php echo htmlspecialchars($subcategory['subcategory_name']); ?>"
                               required 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea name="description" 
                              rows="3" 
                              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($subcategory['description']); ?></textarea>
                </div>
                <div class="mt-6">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Update Subcategory
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 