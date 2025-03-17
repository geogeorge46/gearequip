<?php
include 'config.php';
session_start();

// Fetch all machines with related data
$query = "SELECT 
    m.machine_id,
    m.name AS machine_name,
    m.description,
    m.daily_rate,
    m.security_deposit,
    m.model_number,
    m.manufacturer,
    m.image_url,
    m.status,
    c.category_name,
    s.subcategory_name,
    COUNT(mu.unit_id) as total_units,
    SUM(CASE WHEN mu.status = 'available' THEN 1 ELSE 0 END) as available_units
FROM 
    machines m
    LEFT JOIN categories c ON m.category_id = c.category_id
    LEFT JOIN subcategories s ON m.subcategory_id = s.subcategory_id
    LEFT JOIN machine_units mu ON m.machine_id = mu.machine_id
GROUP BY 
    m.machine_id
ORDER BY 
    m.name";

$result = mysqli_query($conn, $query);
?>

<!-- Display Machines -->
<div class="container mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold mb-6">Available Machines</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php while($machine = mysqli_fetch_assoc($result)): ?>
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Machine Image -->
            <img src="<?php echo htmlspecialchars($machine['image_url']); ?>" 
                 alt="<?php echo htmlspecialchars($machine['machine_name']); ?>"
                 class="w-full h-48 object-cover">
            
            <div class="p-4">
                <!-- Machine Name and Category -->
                <h3 class="text-xl font-semibold mb-2">
                    <?php echo htmlspecialchars($machine['machine_name']); ?>
                </h3>
                <p class="text-gray-600 mb-2">
                    <?php echo htmlspecialchars($machine['category_name']); ?> / 
                    <?php echo htmlspecialchars($machine['subcategory_name']); ?>
                </p>
                
                <!-- Machine Details -->
                <div class="space-y-2 mb-4">
                    <p class="text-sm">
                        <span class="font-medium">Model:</span> 
                        <?php echo htmlspecialchars($machine['model_number']); ?>
                    </p>
                    <p class="text-sm">
                        <span class="font-medium">Manufacturer:</span> 
                        <?php echo htmlspecialchars($machine['manufacturer']); ?>
                    </p>
                    <p class="text-sm">
                        <span class="font-medium">Daily Rate:</span> 
                        ₹<?php echo htmlspecialchars($machine['daily_rate']); ?>
                    </p>
                    <p class="text-sm">
                        <span class="font-medium">Security Deposit:</span> 
                        ₹<?php echo htmlspecialchars($machine['security_deposit']); ?>
                    </p>
                    <p class="text-sm">
                        <span class="font-medium">Available Units:</span> 
                        <?php echo $machine['available_units']; ?> / <?php echo $machine['total_units']; ?>
                    </p>
                </div>
                
                <!-- Status Badge -->
                <div class="mb-4">
                    <span class="px-2 py-1 text-sm rounded-full 
                        <?php echo $machine['available_units'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo $machine['available_units'] > 0 ? 'Available' : 'Not Available'; ?>
                    </span>
                </div>
                
                <!-- Description -->
                <p class="text-gray-600 text-sm mb-4">
                    <?php echo htmlspecialchars($machine['description']); ?>
                </p>
                
                <!-- Rent Button -->
                <?php if($machine['available_units'] > 0): ?>
                <a href="rent_machine.php?id=<?php echo $machine['machine_id']; ?>" 
                   class="block w-full text-center bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                    Rent Now
                </a>
                <?php else: ?>
                <button disabled 
                        class="block w-full text-center bg-gray-300 text-gray-500 py-2 rounded-lg cursor-not-allowed">
                    Not Available
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div> 