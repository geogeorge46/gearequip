<?php
include 'config.php';
session_start();

// Initialize variables
$category = null;
$machines = [];
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;

if (!$category_id) {
    header('Location: machines.php');
    exit();
}

// Get category details
$cat_query = "SELECT category_id, category_name FROM categories WHERE category_id = ?";
$cat_stmt = $conn->prepare($cat_query);
$cat_stmt->bind_param("i", $category_id);
$cat_stmt->execute();
$category = $cat_stmt->get_result()->fetch_assoc();

if ($category) {
    // Modified query to work with your table structure
    $query = "
        SELECT 
            MIN(m.machine_id) as machine_id,
            m.name,
            SUBSTRING_INDEX(m.asset_tag_number, '-', 1) as base_name,
            COUNT(DISTINCT m.machine_id) as total_variants,
            SUM(CASE WHEN m.status = 'available' THEN 1 ELSE 0 END) as available_count,
            MIN(m.image_url) as image_url,
            MIN(m.daily_rate) as daily_rate,
            MIN(m.description) as description,
            MIN(m.condition_status) as condition_status,
            MIN(CASE WHEN m.status = 'available' THEN m.machine_id END) as first_available_id,
            MIN(CASE WHEN m.status = 'available' THEN m.asset_tag_number END) as first_available_tag
        FROM machines m
        JOIN machine_categories mc ON m.machine_id = mc.machine_id
        WHERE mc.category_id = ?
        GROUP BY m.name, SUBSTRING_INDEX(m.asset_tag_number, '-', 1)
        ORDER BY m.name
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $machines[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machines - <?php echo htmlspecialchars($category['category_name'] ?? ''); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Add these styles for proper grid layout */
        .categories-container {
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 0 20px;
        }

        .machines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .machine-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .machine-card:hover {
            transform: translateY(-5px);
        }

        .machine-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .machine-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .machine-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .machine-price {
            color: #27ae60;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .machine-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-available {
            background: #e7f7ed;
            color: #27ae60;
        }

        .status-unavailable {
            background: #fee2e2;
            color: #dc2626;
        }

        .cart-button {
            display: block;
            width: 100%;
            padding: 10px;
            background: #3498db;
            color: white;
            text-align: center;
            border-radius: 8px;
            margin-top: auto;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .cart-button:hover {
            background: #2980b9;
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background: #2c3e50;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }

        .back-button:hover {
            background: #34495e;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .machines-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .categories-container {
                margin-top: 100px;
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="categories-container">
        <a href="machines.php" class="back-button">← Back to Categories</a>
        
        <h1 class="text-4xl font-bold mb-8">
            <?php echo htmlspecialchars($category['category_name']); ?> Machines
        </h1>
        
        <div class="machines-grid">
            <?php foreach($machines as $machine): ?>
                <div class="machine-card">
                    <img src="<?php echo htmlspecialchars($machine['image_url'] ?? 'images/default-machine.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($machine['name']); ?>"
                         class="machine-image"
                         onerror="this.src='images/default-machine.jpg'">
                    
                    <div class="machine-info">
                        <h3 class="machine-title">
                            <?php echo htmlspecialchars($machine['name']); ?>
                            <span class="text-sm text-gray-500">
                                <!-- (<?php echo htmlspecialchars($machine['base_name']); ?>) -->
                            </span>
                        </h3>
                        
                        <p class="machine-price">₹<?php echo number_format($machine['daily_rate'], 2); ?> / day</p>
                        <p class="text-sm text-gray-600">Condition: <?php echo $machine['condition_status']; ?></p>
                        
                        <div class="flex justify-between items-center mt-2 mb-4">
                            <span class="text-sm text-gray-600" id="available_<?php echo $machine['machine_id']; ?>">
                                Available: <?php echo $machine['available_count']; ?>/<?php echo $machine['total_variants']; ?>
                            </span>
                            <?php if ($machine['available_count'] > 0): ?>
                                <span class="machine-status status-available" id="status_<?php echo $machine['machine_id']; ?>">
                                    In Stock
                                </span>
                            <?php else: ?>
                                <span class="machine-status status-unavailable" id="status_<?php echo $machine['machine_id']; ?>">
                                    Out of Stock
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if(isset($_SESSION['user_id']) && $machine['available_count'] > 0): ?>
                            <div class="mt-3 mb-4">
                                <label for="quantity_<?php echo $machine['first_available_id']; ?>" 
                                       class="text-sm text-gray-600">
                                    Quantity:
                                </label>
                                <select id="quantity_<?php echo $machine['first_available_id']; ?>" 
                                        class="ml-2 border rounded px-2 py-1">
                                    <?php for($i = 1; $i <= $machine['available_count']; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <a href="#" 
                               onclick="addToCart(<?php echo $machine['first_available_id']; ?>)"
                               class="cart-button">
                                Add To Cart
                            </a>
                        <?php elseif(!isset($_SESSION['user_id'])): ?>
                            <a href="login.php" class="cart-button">Login to Rent</a>
                        <?php else: ?>
                            <button disabled class="cart-button bg-gray-400 cursor-not-allowed">
                                Not Available
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
    function addToCart(machineId) {
        const quantity = document.getElementById('quantity_' + machineId).value;
        
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `machine_id=${machineId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Added to cart successfully!');
                window.location.href = 'cart.php';
            } else {
                alert(data.message || 'Error adding to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding to cart');
        });
    }
    </script>
</body>
</html> 