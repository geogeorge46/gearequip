<?php
include 'config.php';
session_start();

// Fetch all categories
$categories_query = "SELECT * FROM categories";
$categories_result = mysqli_query($conn, $categories_query);

// If a category is selected, fetch its machines
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : null;
if ($selected_category) {
    $machines_query = "SELECT m.* FROM machines m 
                      JOIN machine_categories mc ON m.machine_id = mc.machine_id 
                      WHERE mc.category_id = ?";
    $stmt = mysqli_prepare($conn, $machines_query);
    mysqli_stmt_bind_param($stmt, "i", $selected_category);
    mysqli_stmt_execute($stmt);
    $machines_result = mysqli_stmt_get_result($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machine Categories</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Navigation Styles */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 40px;
            width: auto;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 500;
            font-size: 16px;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #27ae60;
        }

        .auth-buttons {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .login-btn, .register-btn {
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .login-btn {
            background: linear-gradient(145deg, #27ae60, #219a52);
            color: white;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }

        .register-btn {
            background: linear-gradient(145deg, #2196f3, #1e88e5);
            color: white;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        }

        .login-btn:hover, .register-btn:hover {
            transform: translateY(-2px);
        }

        /* Dropdown styles */
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            min-width: 200px;
            z-index: 1000;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #2c3e50;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .dropdown-menu a:hover {
            background-color: #f8f9fa;
        }

        .dropdown-menu svg {
            width: 16px;
            height: 16px;
            margin-right: 8px;
        }

        .dropdown-menu hr {
            margin: 8px 0;
            border: none;
            border-top: 1px solid #eee;
        }

        /* Your existing machines.php styles */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .categories-container {
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 0 20px;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 40px;
        }

        .category-card {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }

        .category-card:hover {
            transform: translateY(-10px);
        }

        .category-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }

        .category-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 20px;
            color: white;
        }

        .category-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: white;
        }

        .category-description {
            font-size: 14px;
            opacity: 0.9;
            color: white;
        }

        /* Machines Grid Styling */
        .machines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .machine-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
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

        /* Add responsive design */
        @media (max-width: 768px) {
            .categories-grid {
                grid-template-columns: 1fr;
            }

            .categories-container {
                margin-top: 100px;
            }

            .category-image {
                height: 250px;
            }
        }

        /* Rent Now button styling */
        .cart-button {
            display: block;
            width: 100%;
            padding: 10px;
            background: #3498db;
            color: white;
            text-align: center;
            border-radius: 8px;
            margin-top: 15px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .cart-button:hover {
            background: #2980b9;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .auth-buttons {
                gap: 8px;
            }

            .login-btn, .register-btn {
                padding: 8px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="categories-container">
        <?php if (!$selected_category): ?>
            <h1 class="text-4xl font-bold text-center mb-8">Machine Categories</h1>
            <div class="categories-grid">
                <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                    <a href="?category=<?php echo $category['category_id']; ?>" class="category-card">
                        <img src="images/categories/<?php echo strtolower(str_replace(' ', '-', $category['category_name'])); ?>.jpg" 
                             alt="<?php echo htmlspecialchars($category['category_name']); ?>"
                             class="category-image">
                        <div class="category-overlay">
                            <h2 class="category-title"><?php echo htmlspecialchars($category['category_name']); ?></h2>
                            <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <?php 
            $category_query = "SELECT category_name FROM categories WHERE category_id = ?";
            $stmt = mysqli_prepare($conn, $category_query);
            mysqli_stmt_bind_param($stmt, "i", $selected_category);
            mysqli_stmt_execute($stmt);
            $category_result = mysqli_stmt_get_result($stmt);
            $category = mysqli_fetch_assoc($category_result);
            ?>
            <a href="machines.php" class="back-button">← Back to Categories</a>
            <h1 class="text-4xl font-bold mb-8"><?php echo htmlspecialchars($category['category_name']); ?> Machines</h1>
            
            <div class="machines-grid">
                <?php while($machine = mysqli_fetch_assoc($machines_result)): ?>
                    <div class="machine-card">
                        <img src="<?php echo htmlspecialchars($machine['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($machine['name']); ?>"
                             class="machine-image"
                             onerror="this.src='images/default-machine.jpg'">
                        <div class="machine-info">
                            <h3 class="machine-title"><?php echo htmlspecialchars($machine['name']); ?></h3>
                            <p class="machine-price">₹<?php echo number_format($machine['daily_rate'], 2); ?> / day</p>
                            <span class="machine-status <?php echo $machine['status'] == 'available' ? 'status-available' : ''; ?>">
                                <?php echo ucfirst(htmlspecialchars($machine['status'])); ?>
                            </span>
                            
                            <p class="text-sm text-gray-600 mt-2">
                                Available Units: <?php echo htmlspecialchars($machine['available_count']); ?>
                            </p>

                            <?php if(isset($_SESSION['user_id']) && $machine['status'] == 'available' && $machine['available_count'] > 0): ?>
                                <div class="mt-3">
                                    <label for="quantity_<?php echo $machine['machine_id']; ?>" class="text-sm text-gray-600">
                                        Quantity:
                                    </label>
                                    <select id="quantity_<?php echo $machine['machine_id']; ?>" 
                                            class="ml-2 border rounded px-2 py-1">
                                        <?php for($i = 1; $i <= $machine['available_count']; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <a href="#" 
                                   onclick="addToCart(<?php echo $machine['machine_id']; ?>)"
                                   class="cart-button">
                                    Add To Cart
                                </a>
                            <?php elseif(!isset($_SESSION['user_id'])): ?>
                                <a href="login.php" class="cart-button">
                                    Login to Rent
                                </a>
                            <?php else: ?>
                                <button disabled class="cart-button bg-gray-400 cursor-not-allowed">
                                    Not Available
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
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