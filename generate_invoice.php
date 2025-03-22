<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['rental_id'])) {
    exit('Unauthorized access');
}

$rental_id = $_GET['rental_id'];
$user_id = $_SESSION['user_id'];

// Get rental details with machine and user information
$query = "SELECT r.*, m.name as machine_name, m.image_url, m.daily_rate,
          u.full_name, u.email, u.phone
          FROM rentals r
          JOIN machines m ON r.machine_id = m.machine_id
          JOIN users u ON r.user_id = u.user_id
          WHERE r.rental_id = ? AND r.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $rental_id, $user_id);
$stmt->execute();
$rental = $stmt->get_result()->fetch_assoc();

if (!$rental) {
    exit('Invoice not found');
}

// Calculate rental duration
$start_date = new DateTime($rental['start_date']);
$end_date = new DateTime($rental['end_date']);
$duration = $start_date->diff($end_date)->days + 1;

?>

<!-- Add custom print styles -->
<style>
    @media print {
        body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        .no-print { display: none; }
        .print-only { display: block; }
    }
    .invoice-box { max-width: 800px; margin: auto; }
    .watermark {
        position: absolute;
        opacity: 0.1;
        transform: rotate(-45deg);
        font-size: 150px;
        z-index: 0;
        color: #888;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-45deg);
    }
</style>

<div class="invoice-box p-8 relative bg-white">
    <!-- Watermark for paid status -->
    <?php if ($rental['status'] === 'completed'): ?>
    <div class="watermark">PAID</div>
    <?php endif; ?>

    <!-- Company Logo and Header Section -->
    <div class="flex justify-between items-start mb-8">
        <!-- Left Side - Logo and Invoice Info -->
        <div class="flex items-start space-x-4">
            <img src="images/logo.png" alt="GEAR EQUIP" class="h-8  mt-1">
            <div class="flex flex-col">
                <h2 class="text-2xl font-bold text-gray-800 mb-1">INVOICE</h2>
                <div class="space-y-1">
                    <p class="text-gray-600 text-sm">Invoice #: INV-<?php echo str_pad($rental['rental_id'], 6, '0', STR_PAD_LEFT); ?></p>
                    <p class="text-gray-600 text-sm">Date: <?php echo date('M d, Y'); ?></p>
                </div>
            </div>
        </div>

        <!-- Right Side - Company Info -->
        <div class="text-right flex flex-col space-y-1">
            <h3 class="text-xl font-bold text-gray-800 mb-2">GEAR EQUIP</h3>
            <div class="text-sm text-gray-600 space-y-0.5">
                <p>123 Equipment Street</p>
                <p>Mumbai, Maharashtra</p>
                <p>India - 400001</p>
                <p class="font-medium">Phone: +91 1234567890</p>
                <p class="font-medium">GSTIN: 27XXXXXXXXXXXZX</p>
            </div>
        </div>
    </div>

    <!-- Billing & Shipping Information -->
    <div class="grid grid-cols-2 gap-8 mb-8">
        <div>
            <h4 class="text-gray-600 mb-2 font-semibold">Bill To:</h4>
            <p class="font-bold"><?php echo htmlspecialchars($rental['full_name']); ?></p>
            <p class="text-gray-600"><?php echo htmlspecialchars($rental['email']); ?></p>
            <p class="text-gray-600"><?php echo htmlspecialchars($rental['phone']); ?></p>
            <p class="text-gray-600"><?php echo htmlspecialchars($rental['address'] ?? 'Address not provided'); ?></p>
        </div>
        <div>
            <h4 class="text-gray-600 mb-2 font-semibold">Payment Information:</h4>
            <p class="text-gray-600">Status: <span class="font-semibold <?php echo $rental['status'] === 'completed' ? 'text-green-600' : 'text-yellow-600'; ?>">
                <?php echo ucfirst($rental['status']); ?></span></p>
            <p class="text-gray-600">Payment Method: Online Payment</p>
            <p class="text-gray-600">Transaction ID: <?php echo $rental['transaction_id'] ?? 'N/A'; ?></p>
        </div>
    </div>

    <!-- Rental Details Table -->
    <table class="min-w-full mb-8 border">
        <thead>
            <tr class="bg-gray-50">
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase border">Item Description</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase border">Duration</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase border">Daily Rate</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase border">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="px-6 py-4 border">
                    <div class="flex items-center">
                        <img src="<?php echo htmlspecialchars($rental['image_url']); ?>" alt="Machine" class="h-16 w-16 object-cover rounded mr-4">
                        <div>
                            <p class="font-semibold"><?php echo htmlspecialchars($rental['machine_name']); ?></p>
                            <p class="text-sm text-gray-500">
                                Rental Period:<br>
                                <?php echo date('M d, Y', strtotime($rental['start_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($rental['end_date'])); ?>
                            </p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 border"><?php echo $duration; ?> days</td>
                <td class="px-6 py-4 border">₹<?php echo number_format($rental['daily_rate'], 2); ?></td>
                <td class="px-6 py-4 border">₹<?php echo number_format($rental['total_amount'], 2); ?></td>
            </tr>
            <!-- Security Deposit Row -->
            <tr class="bg-gray-50">
                <td colspan="3" class="px-6 py-4 border text-right">Security Deposit:</td>
                <td class="px-6 py-4 border">₹<?php echo number_format($rental['security_deposit'] ?? 0, 2); ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Calculation Details -->
    <div class="flex justify-end mb-8">
        <div class="w-80 border rounded-lg p-4 bg-gray-50">
            <div class="flex justify-between mb-2">
                <span class="font-medium">Subtotal:</span>
                <span>₹<?php echo number_format($rental['total_amount'], 2); ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="font-medium">Security Deposit:</span>
                <span>₹<?php echo number_format($rental['security_deposit'] ?? 0, 2); ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="font-medium">CGST (9%):</span>
                <span>₹<?php echo number_format($rental['total_amount'] * 0.09, 2); ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="font-medium">SGST (9%):</span>
                <span>₹<?php echo number_format($rental['total_amount'] * 0.09, 2); ?></span>
            </div>
            <div class="flex justify-between font-bold text-lg border-t pt-2">
                <span>Total Amount:</span>
                <span>₹<?php echo number_format(($rental['total_amount'] * 1.18) + ($rental['security_deposit'] ?? 0), 2); ?></span>
            </div>
        </div>
    </div>

    <!-- Terms and Conditions -->
    <div class="mb-8 text-sm text-gray-600">
        <h4 class="font-semibold mb-2">Terms & Conditions:</h4>
        <ol class="list-decimal pl-5 space-y-1">
            <li>Security deposit will be refunded after the equipment is returned in good condition.</li>
            <li>Late returns will incur additional charges as per the rental agreement.</li>
            <li>Any damage to the equipment will be deducted from the security deposit.</li>
            <li>This is a computer-generated invoice and doesn't require a signature.</li>
        </ol>
    </div>

    <!-- Footer -->
    <div class="text-center text-gray-600 text-sm border-t pt-4">
        <p class="font-semibold mb-1">Thank you for choosing GEAR EQUIP!</p>
        <p>For support: support@gearequip.com | +91 1234567890</p>
        <p class="text-xs mt-2">This invoice is valid for GST input credit when accompanied by proof of payment</p>
    </div>

    <!-- Action Buttons -->
    <div class="mt-8 flex justify-center space-x-4 no-print">
        <button onclick="window.print()" 
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print Invoice
        </button>
        <button onclick="downloadPDF()" 
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Download PDF
        </button>
    </div>
</div>

<script>
function downloadPDF() {
    // Add PDF download functionality if needed
    window.print();
}
</script>