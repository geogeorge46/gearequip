<?php
include 'config.php';
session_start();

// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Handle form submission for new machine
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    
    // Validate machine name (must start with a letter)
    if (!preg_match("/^[a-zA-Z][a-zA-Z0-9\s-]*$/", $name)) {
        $error_message = "Machine name must start with a letter and can contain only letters, numbers, spaces and hyphens";
    } else {
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $daily_rate = mysqli_real_escape_string($conn, $_POST['daily_rate']);
        $security_deposit = mysqli_real_escape_string($conn, $_POST['security_deposit']);
        $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
        $subcategory_id = mysqli_real_escape_string($conn, $_POST['subcategory_id']);
        $model_number = mysqli_real_escape_string($conn, $_POST['model_number']);
        $manufacturer = mysqli_real_escape_string($conn, $_POST['manufacturer']);
        $available_count = mysqli_real_escape_string($conn, $_POST['available_count']);
        
        // Handle image upload
        $image_url = '';
        if(isset($_FILES['image_url']) && $_FILES['image_url']['error'] == 0) {
            $target_dir = "uploads/";
            $image_url = $target_dir . time() . '_' . basename($_FILES["image_url"]["name"]);
            move_uploaded_file($_FILES["image_url"]["tmp_name"], $image_url);
        }
        
        // First insert the machine
        $query = "INSERT INTO machines (name, description, daily_rate, security_deposit, 
                  category_id, subcategory_id, model_number, manufacturer, 
                  image_url, status) 
                  VALUES ('$name', '$description', '$daily_rate', '$security_deposit', 
                  '$category_id', '$subcategory_id', '$model_number', '$manufacturer', 
                  '$image_url', 'available')";
        
        if (mysqli_query($conn, $query)) {
            $machine_id = mysqli_insert_id($conn);
            
            // Then create individual units for this machine
            for ($i = 1; $i <= $available_count; $i++) {
                $unit_number = $model_number . "-" . str_pad($i, 3, "0", STR_PAD_LEFT);
                $unit_query = "INSERT INTO machine_units (machine_id, unit_number, status) 
                              VALUES ('$machine_id', '$unit_number', 'available')";
                mysqli_query($conn, $unit_query);
            }
            $success_message = "Machine and $available_count units added successfully!";
        } else {
            $error_message = "Error adding machine: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Machines - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Machine Name Validation
        const nameInput = document.querySelector('input[name="name"]');
        const nameError = document.createElement('span');
        nameError.className = 'text-red-500 text-sm mt-1';
        nameInput.parentNode.appendChild(nameError);

        nameInput.addEventListener('input', function() {
            const value = this.value;
            const isValid = /^[a-zA-Z][a-zA-Z0-9\s-]*$/.test(value);
            
            if (!value) {
                nameError.textContent = 'Machine name is required';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else if (!isValid) {
                nameError.textContent = 'Must start with a letter and contain only letters, numbers, spaces and hyphens';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else {
                nameError.textContent = '';
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            }
        });

        // Daily Rate Validation
        const rateInput = document.querySelector('input[name="daily_rate"]');
        const rateError = document.createElement('span');
        rateError.className = 'text-red-500 text-sm mt-1';
        rateInput.parentNode.appendChild(rateError);

        rateInput.addEventListener('input', function() {
            const value = parseFloat(this.value);
            
            if (!this.value) {
                rateError.textContent = 'Daily rate is required';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else if (isNaN(value) || value < 10 || value > 10000) {
                rateError.textContent = 'Daily rate must be between ₹10 and ₹10000';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else {
                rateError.textContent = '';
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            }
        });

        // Available Count Validation
        const countInput = document.querySelector('input[name="available_count"]');
        const countError = document.createElement('span');
        countError.className = 'text-red-500 text-sm mt-1';
        countInput.parentNode.appendChild(countError);

        countInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            
            if (!this.value) {
                countError.textContent = 'Available count is required';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else if (isNaN(value) || value < 1) {
                countError.textContent = 'Available count must be at least 1';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else if (value > 100) {  // Adding a reasonable upper limit
                countError.textContent = 'Available count cannot exceed 100';
                this.classList.add('border-red-500');
                this.classList.remove('border-green-500');
            } else {
                countError.textContent = '';
                this.classList.remove('border-red-500');
                this.classList.add('border-green-500');
            }
        });

        // Update form validation to include available count
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const nameValid = /^[a-zA-Z][a-zA-Z0-9\s-]*$/.test(nameInput.value);
            const rateValid = parseFloat(rateInput.value) >= 10 && parseFloat(rateInput.value) <= 10000;
            const countValue = parseInt(countInput.value);
            const countValid = countValue >= 1 && countValue <= 100;

            if (!nameValid || !rateValid || !countValid) {
                e.preventDefault();
                if (!nameValid) {
                    nameInput.focus();
                } else if (!rateValid) {
                    rateInput.focus();
                } else if (!countValid) {
                    countInput.focus();
                }
            }
        });
    });
    </script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="managerstore.php" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Store
            </a>
        </div>

        <!-- Add Machine Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Add New Machine</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <?php if(isset($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>

                <?php if(isset($success_message)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?php echo $success_message; ?></span>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category_id" id="category_id" required 
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Category</option>
                            <?php
                            $categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");
                            while($category = mysqli_fetch_assoc($categories)) {
                                echo "<option value='" . $category['category_id'] . "'>" . 
                                     htmlspecialchars($category['category_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subcategory</label>
                        <select name="subcategory_id" id="subcategory_id" required 
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Subcategory</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Machine Name</label>
                        <input type="text" 
                               name="name" 
                               required 
                               pattern="^[a-zA-Z][a-zA-Z0-9\s-]*$"
                               title="Must start with a letter and can contain only letters, numbers, spaces and hyphens"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Model Number</label>
                        <input type="text" name="model_number" required 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Daily Rate (₹)</label>
                        <input type="number" 
                               name="daily_rate" 
                               required 
                               min="10" 
                               max="1000" 
                               step="0.01"
                               title="Daily rate must be between ₹10 and ₹1000"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Security Deposit (₹)</label>
                        <input type="number" name="security_deposit" required step="0.01" 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Manufacturer</label>
                        <input type="text" name="manufacturer" required 
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Available Count</label>
                        <input type="number" 
                               name="available_count" 
                               required 
                               min="1"
                               max="100"
                               value="1"
                               title="Enter number of units (1-100)"
                               class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <small class="text-gray-500">Number of machine units to be added (1-100)</small>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" required 
                              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Machine Image</label>
                    <input type="file" name="image_url" accept="image/*" required 
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mt-6">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Add Machine
                    </button>
                </div>
            </form>
        </div>

        <!-- Machines List -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold">All Machines</h2>
            </div>
            <div class="p-6">
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="pb-4 font-medium">Machine</th>
                            <th class="pb-4 font-medium">Category</th>
                            <th class="pb-4 font-medium">Daily Rate</th>
                            <th class="pb-4 font-medium">Total Units</th>
                            <th class="pb-4 font-medium">Available</th>
                            <th class="pb-4 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT m.*, c.category_name, s.subcategory_name,
                                  COUNT(mu.unit_id) as total_units,
                                  SUM(CASE WHEN mu.status = 'available' THEN 1 ELSE 0 END) as available_units
                                  FROM machines m 
                                  LEFT JOIN categories c ON m.category_id = c.category_id 
                                  LEFT JOIN subcategories s ON m.subcategory_id = s.subcategory_id 
                                  LEFT JOIN machine_units mu ON m.machine_id = mu.machine_id
                                  GROUP BY m.machine_id
                                  ORDER BY m.name";
                        $result = mysqli_query($conn, $query);
                        
                        while($row = mysqli_fetch_assoc($result)) {
                            echo '<tr class="border-b">';
                            echo '<td class="py-4">' . htmlspecialchars($row['name']) . '</td>';
                            echo '<td class="py-4">' . htmlspecialchars($row['category_name']) . 
                                 ' / ' . htmlspecialchars($row['subcategory_name']) . '</td>';
                            echo '<td class="py-4">₹' . htmlspecialchars($row['daily_rate']) . '</td>';
                            echo '<td class="py-4">' . htmlspecialchars($row['total_units']) . '</td>';
                            echo '<td class="py-4">' . htmlspecialchars($row['available_units']) . '</td>';
                            echo '<td class="py-4">';
                            echo '<a href="edit_machine.php?id=' . $row['machine_id'] . '" 
                                    class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 mr-2">Edit</a>';
                            echo '<a href="view_units.php?id=' . $row['machine_id'] . '" 
                                    class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">View Units</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // Dynamic subcategory loading
    $('#category_id').change(function() {
        var category_id = $(this).val();
        $.ajax({
            url: 'get_subcategories.php',
            type: 'POST',
            data: {category_id: category_id},
            success: function(response) {
                $('#subcategory_id').html(response);
            }
        });
    });
    </script>
</body>
</html> 