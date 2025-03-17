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
    try {
        $conn->begin_transaction();

        // Check if asset tag already exists
        $check_query = "SELECT COUNT(*) FROM machines WHERE asset_tag_number = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $_POST['asset_tag_number']);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($count > 0) {
            throw new Exception('This Asset Tag Number already exists!');
        }

        // Handle image upload
        $image_url = '';
        if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_url = $target_file;
            }
        }

        // Insert into machines table
        $insert_query = "INSERT INTO machines (
            name, 
            asset_tag_number,
            description,
            daily_rate,
            security_deposit,
            status,
            image_url,
            model_number,
            serial_number,
            manufacturer,
            manufacturing_year,
            purchase_date,
            purchase_price,
            current_value,
            vendor_info,
            invoice_number,
            location,
            specifications,
            power_rating,
            fuel_type,
            operating_hours,
            max_operating_hours,
            condition_status,
            warranty_start_date,
            warranty_expiry,
            warranty_details,
            maintenance_interval,
            next_maintenance_date,
            maintenance_notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sssddsssssissddsssssiisssssis",
            $_POST['name'],
            $_POST['asset_tag_number'],
            $_POST['description'],
            $_POST['daily_rate'],
            $_POST['security_deposit'],
            $_POST['status'],
            $image_url,
            $_POST['model_number'],
            $_POST['serial_number'],
            $_POST['manufacturer'],
            $_POST['manufacturing_year'],
            $_POST['purchase_date'],
            $_POST['purchase_price'],
            $_POST['current_value'],
            $_POST['vendor_info'],
            $_POST['invoice_number'],
            $_POST['location'],
            $_POST['specifications'],
            $_POST['power_rating'],
            $_POST['fuel_type'],
            $_POST['operating_hours'],
            $_POST['max_operating_hours'],
            $_POST['condition_status'],
            $_POST['warranty_start_date'],
            $_POST['warranty_expiry'],
            $_POST['warranty_details'],
            $_POST['maintenance_interval'],
            $_POST['next_maintenance_date'],
            $_POST['maintenance_notes']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting machine: " . $stmt->error);
        }

        // Get the ID of the newly inserted machine
        $machine_id = $conn->insert_id;

        // Insert into machine_categories table
        $category_query = "INSERT INTO machine_categories (machine_id, category_id) VALUES (?, ?)";
        $cat_stmt = $conn->prepare($category_query);
        $cat_stmt->bind_param("ii", $machine_id, $_POST['category_type']);

        if (!$cat_stmt->execute()) {
            throw new Exception("Error assigning category: " . $cat_stmt->error);
        }

        $conn->commit();
        
        // Return JSON response for success
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success', 
            'message' => 'Machine added successfully!',
            'machine_id' => $machine_id,
            'category_id' => $_POST['category_type']
        ]);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        
        // Return JSON response for error
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

// Display success/error messages if they exist in session
if (isset($_SESSION['success_message'])) {
    echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative' role='alert'>
            <span class='block sm:inline'>{$_SESSION['success_message']}</span>
          </div>";
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>
            <span class='block sm:inline'>{$_SESSION['error_message']}</span>
          </div>";
    unset($_SESSION['error_message']);
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

<!-- Back button - add this before your form -->
<div class="max-w-7xl mx-auto px-4 py-4 mt-20">
    <button onclick="window.location.href='manager_dashboard.php'" 
            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
        </svg>
        Back to Dashboard
    </button>
</div>

<!-- Your existing form starts here -->
<div class="max-w-7xl mx-auto px-4 py-16">
    <h2 class="text-2xl font-bold mb-6">Add New Machine</h2>
    <form id="addMachineForm" action="add_machine.php" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-lg shadow-md">
        <!-- Basic Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Machine Name*</label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       required 
                       pattern="^[A-Za-z][A-Za-z0-9\s-]*$"
                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"
                       title="Machine name must start with a letter"
                >
                <div id="name-error" class="text-red-500 text-sm mt-1 hidden"></div>
            </div>
            <div>
                <label for="asset_tag_number" class="block text-sm font-medium text-gray-700">Asset Tag Number*</label>
                <input type="text" name="asset_tag_number" id="asset_tag_number" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="model_number" class="block text-sm font-medium text-gray-700">Model Number*</label>
                <input type="text" name="model_number" id="model_number" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="serial_number" class="block text-sm font-medium text-gray-700">Serial Number*</label>
                <input type="text" name="serial_number" id="serial_number" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="manufacturer" class="block text-sm font-medium text-gray-700">Manufacturer*</label>
                <input type="text" name="manufacturer" id="manufacturer" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="manufacturing_year" class="block text-sm font-medium text-gray-700">Manufacturing Year*</label>
                <input type="number" name="manufacturing_year" id="manufacturing_year" required min="1900" max="2024" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="category_type" class="block text-sm font-medium text-gray-700">Category*</label>
                <select name="category_type" id="category_type" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['category_id']); ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Purchase and Warranty Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="purchase_date" class="block text-sm font-medium text-gray-700">Purchase Date*</label>
                <input type="date" name="purchase_date" id="purchase_date" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="purchase_price" class="block text-sm font-medium text-gray-700">Purchase Price (₹)*</label>
                <input type="number" name="purchase_price" id="purchase_price" required step="0.01" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="vendor_info" class="block text-sm font-medium text-gray-700">Vendor Information</label>
                <input type="text" name="vendor_info" id="vendor_info" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="invoice_number" class="block text-sm font-medium text-gray-700">Invoice Number*</label>
                <input type="text" name="invoice_number" id="invoice_number" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
        </div>

        <!-- Technical Specifications -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="power_rating" class="block text-sm font-medium text-gray-700">Power Rating</label>
                <select name="power_rating" id="power_rating" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </div>
            <div>
                <label for="fuel_type" class="block text-sm font-medium text-gray-700">Fuel Type</label>
                <input type="text" name="fuel_type" id="fuel_type" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="operating_hours" class="block text-sm font-medium text-gray-700">Operating Hours</label>
                <input type="number" name="operating_hours" id="operating_hours" min="0" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="max_operating_hours" class="block text-sm font-medium text-gray-700">Max Operating Hours*</label>
                <input type="number" name="max_operating_hours" id="max_operating_hours" required min="0" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
        </div>

        <!-- Warranty Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="warranty_start_date" class="block text-sm font-medium text-gray-700">Warranty Start Date</label>
                <input type="date" name="warranty_start_date" id="warranty_start_date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="warranty_expiry" class="block text-sm font-medium text-gray-700">Warranty Expiry Date</label>
                <input type="date" name="warranty_expiry" id="warranty_expiry" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="warranty_details" class="block text-sm font-medium text-gray-700">Warranty Details</label>
                <textarea name="warranty_details" id="warranty_details" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"></textarea>
            </div>
        </div>

        <!-- Existing Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="daily_rate" class="block text-sm font-medium text-gray-700">Daily Rate (₹)*</label>
                <input type="number" name="daily_rate" id="daily_rate" required min="50" max="10000" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="security_deposit" class="block text-sm font-medium text-gray-700">Security Deposit (₹)*</label>
                <input type="number" name="security_deposit" id="security_deposit" required step="0.01" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="current_value" class="block text-sm font-medium text-gray-700">Current Value (₹)</label>
                <input type="number" name="current_value" id="current_value" step="0.01" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
        </div>

        <!-- Status and Condition -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status*</label>
                <select name="status" id="status" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <option value="available">Available</option>
                    <option value="rented">Rented</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            <div>
                <label for="condition_status" class="block text-sm font-medium text-gray-700">Condition*</label>
                <select name="condition_status" id="condition_status" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    <option value="Excellent">Excellent</option>
                    <option value="Good">Good</option>
                    <option value="Fair">Fair</option>
                    <option value="Poor">Poor</option>
                </select>
            </div>
        </div>

        <!-- Location and Description -->
        <div class="grid grid-cols-1 gap-4 mb-6">
            <div>
                <label for="location" class="block text-sm font-medium text-gray-700">Location*</label>
                <input type="text" name="location" id="location" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description*</label>
                <textarea name="description" id="description" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" rows="3"></textarea>
            </div>
            <div>
                <label for="specifications" class="block text-sm font-medium text-gray-700">Specifications</label>
                <textarea name="specifications" id="specifications" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" rows="3"></textarea>
            </div>
        </div>

        <!-- Maintenance Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="maintenance_interval" class="block text-sm font-medium text-gray-700">Maintenance Interval (days)</label>
                <input type="number" name="maintenance_interval" id="maintenance_interval" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="last_maintenance_date" class="block text-sm font-medium text-gray-700">Last Maintenance Date</label>
                <input type="date" name="last_maintenance_date" id="last_maintenance_date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="next_maintenance_date" class="block text-sm font-medium text-gray-700">Next Maintenance Date</label>
                <input type="date" name="next_maintenance_date" id="next_maintenance_date" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="maintenance_notes" class="block text-sm font-medium text-gray-700">Maintenance Notes</label>
                <textarea name="maintenance_notes" id="maintenance_notes" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"></textarea>
            </div>
        </div>

        <!-- Image Upload -->
        <div class="mb-6">
            <label for="image" class="block text-sm font-medium text-gray-700">Machine Image*</label>
            <input type="file" name="image" id="image" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
        </div>

        <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">Add Machine</button>
    </form>
</div>

<!-- Add this JavaScript function to check asset tag before form submission -->
<script>
async function checkAssetTag(assetTag) {
    const formData = new FormData();
    formData.append('asset_tag_number', assetTag);
    
    try {
        const response = await fetch('check_asset_tag.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        return data.exists;
    } catch (error) {
        console.error('Error:', error);
        return false;
    }
}

document.getElementById('addMachineForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Get the machine name value
    const machineName = document.getElementById('name').value.trim();
    
    // Check if machine name starts with a letter
    if (!machineName.match(/^[A-Za-z]/)) {
        Swal.fire({
            title: 'Invalid Input!',
            text: 'Machine name must start with a letter!',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return false;
    }

    const assetTag = document.getElementById('asset_tag_number').value;
    
    // Show loading state
    Swal.fire({
        title: 'Processing...',
        text: 'Checking asset tag number...',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const exists = await checkAssetTag(assetTag);
        if (exists) {
            Swal.fire({
                title: 'Error!',
                text: 'This Asset Tag Number already exists!',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        // If asset tag is unique, proceed with form submission
        const formData = new FormData(this);
        
        Swal.fire({
            title: 'Processing...',
            text: 'Please wait while we add the machine.',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const response = await fetch('add_machine.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'manager_dashboard.php';
                }
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: data.message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Error!',
            text: 'An unexpected error occurred. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
});

// Add real-time validation for the machine name input
document.getElementById('name').addEventListener('input', function(e) {
    const input = e.target;
    const value = input.value.trim();
    const errorDiv = document.getElementById('name-error') || createErrorDiv('name-error');
    
    if (!value.match(/^[A-Za-z]/)) {
        input.classList.add('border-red-500');
        errorDiv.textContent = 'Machine name must start with a letter';
        errorDiv.classList.remove('hidden');
    } else {
        input.classList.remove('border-red-500');
        errorDiv.classList.add('hidden');
    }
});

// Helper function to create error message div
function createErrorDiv(id) {
    const div = document.createElement('div');
    div.id = id;
    div.className = 'text-red-500 text-sm mt-1';
    document.getElementById('name').parentNode.appendChild(div);
    return div;
}
</script>

<?php include 'footer.php'; ?>
</body>
</html>