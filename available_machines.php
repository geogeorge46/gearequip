<?php
include 'config.php';
session_start();












// Important file it is for manager available machines















// Check if user is logged in as manager
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'manager') {
    header('Location: login.php');
    exit();
}

// Fetch categories for the filter
$categories_query = "SELECT * FROM categories";
$categories = $conn->query($categories_query);

// For the machines list, make sure we're correctly joining with categories
$machines_query = "SELECT m.*, 
                  GROUP_CONCAT(DISTINCT c.category_name) as category_names,
                  GROUP_CONCAT(DISTINCT c.category_id) as category_ids
                  FROM machines m 
                  LEFT JOIN machine_categories mc ON m.machine_id = mc.machine_id 
                  LEFT JOIN categories c ON mc.category_id = c.category_id 
                  GROUP BY m.machine_id";
$machines = $conn->query($machines_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Machines - GEAR EQUIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Side Navigation -->
    <div class="fixed left-0 top-0 w-64 h-full bg-white shadow-lg">
        <!-- Logo Section -->
        <div class="p-4 border-b">
            <div class="flex items-center space-x-3">
                <img src="images/logo.png" alt="GEAR EQUIP" class="h-8">
                <span class="text-xl font-bold text-gray-800">GEAR EQUIP</span>
            </div>
        </div>
        
        <!-- Manager Info -->
        <div class="p-4 border-b">
            <p class="text-sm text-gray-500">Welcome,</p>
            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4">
            <div class="space-y-2">
                <a href="manager_dashboard.php" class="block px-4 py-2 rounded-lg hover:bg-gray-50 text-gray-700">
                    <i class="fas fa-home mr-3"></i>Overview
                </a>
                <a href="available_machines.php" class="block px-4 py-2 rounded-lg bg-blue-50 text-blue-700 font-medium">
                    <i class="fas fa-cogs mr-3"></i>Available Machines
                </a>
                <!-- ... other navigation links ... -->
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <!-- Top Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Available Machines</h1>
            <div class="flex gap-4">
                <button onclick="window.location.href='add_machine.php'" 
                        class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                    Add New Machine
                </button>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                    Logout
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <div class="flex gap-4 items-center">
                <select id="categoryFilter" class="border rounded-lg px-3 py-2">
                    <option value="all">All Categories</option>
                    <?php while($category = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $category['category_id']; ?>">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select id="statusFilter" class="border rounded-lg px-3 py-2">
                    <option value="all">All Status</option>
                    <option value="available">Available</option>
                    <option value="rented">Rented</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
        </div>

        <!-- Machines List -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Machine</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Daily Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    if ($machines && $machines->num_rows > 0) {
                        while($machine = $machines->fetch_assoc()):
                    ?>
                    <tr class="hover:bg-gray-50 machine-row" data-categories="<?php echo htmlspecialchars($machine['category_ids']); ?>">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <img class="h-10 w-10 rounded-full object-cover" 
                                     src="<?php echo !empty($machine['image_url']) && file_exists($machine['image_url']) 
                                           ? htmlspecialchars($machine['image_url']) 
                                           : 'images/default-machine.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($machine['name']); ?>">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($machine['name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($machine['category_names']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">₹<?php echo number_format($machine['daily_rate'], 2); ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <select name="status" 
                                    class="border rounded px-2 py-1" 
                                    id="status_<?php echo $machine['machine_id']; ?>">
                                <option value="available" <?php echo $machine['status'] === 'available' ? 'selected' : ''; ?>>
                                    Available
                                </option>
                                <option value="rented" <?php echo $machine['status'] === 'rented' ? 'selected' : ''; ?>>
                                    Rented
                                </option>
                                <option value="maintenance" <?php echo $machine['status'] === 'maintenance' ? 'selected' : ''; ?>>
                                    Maintenance
                                </option>
                            </select>
                        </td>
                        <td class="px-6 py-4">
                            <input type="number" 
                                   name="available_count" 
                                   value="<?php echo htmlspecialchars($machine['available_count']); ?>" 
                                   min="0" 
                                   class="border rounded px-2 py-1 w-20" 
                                   id="available_count_<?php echo $machine['machine_id']; ?>">
                        </td>
                        <td class="px-6 py-4">
                            <input type="text" 
                                   name="description" 
                                   value="<?php echo htmlspecialchars($machine['description']); ?>" 
                                   class="border rounded px-2 py-1 w-40" 
                                   id="description_<?php echo $machine['machine_id']; ?>">
                        </td>
                        <td class="px-6 py-4">
                            <button onclick="updateMachine(<?php echo $machine['machine_id']; ?>)" 
                                    class="bg-blue-500 text-white px-2 py-1 rounded-lg hover:bg-blue-600 transition mr-2">
                                Update
                            </button>
                            <button onclick="deleteMachine(<?php echo $machine['machine_id']; ?>)" 
                                    class="bg-red-500 text-white px-2 py-1 rounded-lg hover:bg-red-600 transition">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>

    <!-- JavaScript for machine management -->
    <script>
    function updateMachine(machineId) {
        const status = document.getElementById(`status_${machineId}`).value;
        const availableCount = document.getElementById(`available_count_${machineId}`).value;
        const description = document.getElementById(`description_${machineId}`).value;

        const formData = new FormData();
        formData.append('machine_id', machineId);
        formData.append('status', status);
        formData.append('available_count', availableCount);
        formData.append('description', description);

        fetch('update_machine.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Machine updated successfully!');
                // Update the status cell color
                const statusCell = document.querySelector(`#status_${machineId}`).parentElement;
                updateStatusStyle(statusCell, status);
            } else {
                alert('Error updating machine: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating machine');
        });
    }

    function updateStatusStyle(cell, status) {
        const statusClasses = {
            'available': 'bg-green-100 text-green-800',
            'rented': 'bg-yellow-100 text-yellow-800',
            'maintenance': 'bg-red-100 text-red-800'
        };

        // Remove all possible status classes
        cell.classList.remove('bg-green-100', 'text-green-800', 
                             'bg-yellow-100', 'text-yellow-800', 
                             'bg-red-100', 'text-red-800');
        
        // Add the appropriate class
        if (statusClasses[status]) {
            cell.classList.add(...statusClasses[status].split(' '));
        }
    }

    function deleteMachine(machineId) {
        if (confirm('Are you sure you want to delete this machine?')) {
            fetch('delete_machine.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `machine_id=${machineId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Machine deleted successfully!');
                    location.reload();
                } else {
                    alert('Error deleting machine');
                }
            });
        }
    }

    // Filter functionality
    document.getElementById('categoryFilter').addEventListener('change', filterMachines);
    document.getElementById('statusFilter').addEventListener('change', filterMachines);

    function filterMachines() {
        const categoryValue = document.getElementById('categoryFilter').value;
        const statusValue = document.getElementById('statusFilter').value;
        const rows = document.querySelectorAll('tbody tr');

        rows.forEach(row => {
            let showRow = true;
            
            if (categoryValue !== 'all') {
                const categories = (row.dataset.categories || '').split(',');
                if (!categories.includes(categoryValue)) {
                    showRow = false;
                }
            }

            if (statusValue !== 'all') {
                const status = row.querySelector('td:nth-child(3) select').value.toLowerCase();
                if (status !== statusValue) {
                    showRow = false;
                }
            }

            row.style.display = showRow ? '' : 'none';
        });
    }
    </script>
</body>
</html> 