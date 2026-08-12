<?php
session_start();
require_once "includes/autoloader.php";

// Make sure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
$dbClass = new Database();
$conn = $dbClass->connect();

// Get the current user's ID and role
$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

// Variables used for SQL search filtering queries
$sku_search_query = "";
$order_id_search_query = "";

// Allow administrators to update the order delivery status
if ($role === 'admin' && isset($_POST['update_status'])) {
    $oId = intval($_POST['order_id']);
    $newStatus = mysqli_real_escape_string($conn, $_POST['order_status']);
    mysqli_query($conn, "UPDATE `orders` SET `status` = '$newStatus' WHERE `id` = $oId");

    // Reload the log matrix page layout after a successful update
    header("Location: orders_history.php");
    exit();
}

// FILTER BY CLEAN SKU: Flexible search using standard wildcard matching
if ($role === 'admin' && isset($_GET['search_sku']) && !empty($_GET['search_sku'])) {
    $search_sku = mysqli_real_escape_string($conn, trim($_GET['search_sku']));
    $sku_search_query = " AND p.sku LIKE '%$search_sku%' ";
}

// FILTER BY ORDER ID: Strict tracking using unique numbers
if ($role === 'admin' && isset($_GET['search_order_id']) && !empty($_GET['search_order_id'])) {
    $search_order_id = intval($_GET['search_order_id']);
    $order_id_search_query = " AND o.id = $search_order_id ";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management Tracking Matrix - TradeVibe</title>
    <link rel="stylesheet" href="styles.css">

</head>

<body id="bckgrnd">

    <header>
        <div class="navbar">
            <div class="brand-logo-zone" onclick="window.location.href='index.php';">
                <svg class="brand-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="16 3 21 3 21 8"></polyline>
                    <line x1="4" y1="20" x2="21" y2="4"></line>
                    <polyline points="21 16 21 21 16 21"></polyline>
                    <line x1="15" y1="15" x2="21" y2="21"></line>
                    <line x1="4" y1="4" x2="9" y2="9"></line>
                </svg>
                <span class="brand-name-text">Trade<span class="brand-accent-text">Vibe</span></span>
            </div>
            <span class="title">Orders Log Matrix</span>
            <ul class="btn-list">
                <li><a href="index.php" class="btn btn-secondary">STOREFRONT</a></li>
            </ul>
        </div>
    </header>

    <div class="order-history-container">

        <?php if ($role === 'admin'): ?>
            <!-- Product search panel for administrators -->
            <div class="monitoring-panels-wrapper">

                <!-- FILTER BY EXACT SKU FORMAT -->
                <div class="monitoring-panel">
                    <h3>🔍 Filter Orders by Product SKU</h3>
                    <form method="GET" action="orders_history.php" class="monitoring-form">
                        <input type="text" name="search_sku" placeholder="Enter SKU (e.g., 2004)" value="<?php echo isset($_GET['search_sku']) ? htmlspecialchars($_GET['search_sku']) : ''; ?>" class="monitoring-input" required>
                        <button type="submit" class="btn btn-primary" style="height: 38px; margin: 0 !important; white-space: nowrap;">Search SKU</button>
                        <?php if (isset($_GET['search_sku'])): ?>
                            <a href="orders_history.php" class="clear-filter-btn">✕ Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- FILTER BY ID: Search for a single unique order using its sequential number -->
                <div class="monitoring-panel">
                    <h3>📦 Search by Unique Order ID</h3>
                    <form method="GET" action="orders_history.php" class="monitoring-form">
                        <input type="number" name="search_order_id" placeholder="Enter Order ID (e.g., 7)" value="<?php echo isset($_GET['search_order_id']) ? htmlspecialchars($_GET['search_order_id']) : ''; ?>" class="monitoring-input" required>
                        <button type="submit" class="btn btn-primary" style="height: 38px; margin: 0 !important; white-space: nowrap;">Find Order</button>
                        <?php if (isset($_GET['search_order_id'])): ?>
                            <a href="orders_history.php" class="clear-filter-btn">✕ Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

            </div>
        <?php endif; ?>

        <h2 style="text-align: left; font-size: 22px; font-weight: 700; color: #1a202c; margin-bottom: 25px;">
            <?php echo ($role === 'admin') ? 'Global Shop Orders Matrix Logs' : 'Your Order History Logs'; ?>
        </h2>

        <?php
        // Get orders based on the user's role
        if ($role === 'user') {
            // Regular users can only see their own orders
            $query = "SELECT o.*, u.first_name, u.last_name, u.email as buyer_account_email 
                      FROM `orders` o 
                      JOIN `users` u ON o.user_id = u.id
                      WHERE o.user_id = $userId ORDER BY o.id DESC";
        } else {
            // Administrators can see orders related to their products
            $query = "SELECT DISTINCT o.*, u.first_name, u.last_name, u.email as buyer_account_email 
                      FROM `orders` o 
                      JOIN `order_items` oi ON o.id = oi.order_id
                      JOIN `producttable` p ON oi.product_id = p.id
                      JOIN `users` u ON o.user_id = u.id
                      WHERE p.admin_id = $userId {$sku_search_query} {$order_id_search_query}  ORDER BY o.id DESC";
        }

        // Run the order query
        $orders_res = mysqli_query($conn, $query);

        // Show a message when no orders are found
        if (mysqli_num_rows($orders_res) === 0):
            echo "<p style='color:#718096; text-align:left; font-size:15px; margin-top:20px;'>No orders registered inside the log matrix tracking protocols matching the selected criteria.</p>";
        else:
            // Display each order
            while ($order = mysqli_fetch_assoc($orders_res)):
                $oId = $order['id'];
                $orderTime = (!empty($order['order_date'])) ? $order['order_date'] : 'Recorded Realtime (Just Placed)';
                $realCustomerName = htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
        ?>
                <!-- Display information for one order -->
                <div class="order-card-box">
                    <!-- CLEANED ARCHITECTURE: No inline styles inside the header layer -->
                    <div class="order-card-header">
                        <div>
                            <span class="order-id-label">ORDER ID: #<?php echo $oId; ?></span>
                            <h4 class="order-receiver-name">Customer Profile: <span class="customer-name-accent"><?php echo $realCustomerName; ?></span></h4>
                        </div>
                        <div>
                            <?php
                            // REVERSE ENGINEER SHIPPING MATHEMATICS FOR THE LOG DISPLAY
                            $total_val = floatval($order['total_amount']);
                            $order_currency = $order['currency'];

                            // Set up dynamic threshold limits inspired by Ananas.mk marketplace
                            $threshold = 30.00;
                            $rate = 3.00;

                            if ($order_currency === 'MKD' || $order_currency === 'ден') {
                                $threshold = 1500.00;
                                $rate = 150.00;
                            } elseif ($order_currency === 'EUR' || $order_currency === '€') {
                                $threshold = 25.00;
                                $rate = 2.50;
                            }

                            // If grand total passed the limit, the shipping was free (Items subtotal equals grand total)
                            if ($total_val >= $threshold) {
                                $history_shipping_cost = 0.00;
                                $history_items_subtotal = $total_val;
                            } else {
                                // If grand total is lower, subtract shipping fee to get the raw items subtotal
                                $history_shipping_cost = $rate;
                                $history_items_subtotal = $total_val - $rate;
                            }
                            ?>

                            <!-- NEW DETAILED ACCOUNTING GRID WITH NO INLINE PROPERTIES -->
                            <div class="invoice-breakdown-panel">
                                <div>Items Subtotal: <strong><?php echo number_format($history_items_subtotal, 2) . ' ' . $order_currency; ?></strong></div>

                                <!-- NEW ROW: Shipping delivery fee rate display item -->
                                <div>Shipping Fee:
                                    <strong class="<?php echo ($history_shipping_cost == 0) ? 'shipping-free-green' : 'shipping-rate-orange'; ?>">
                                        <?php echo ($history_shipping_cost == 0) ? 'FREE' : number_format($history_shipping_cost, 2) . ' ' . $order_currency; ?>
                                    </strong>
                                </div>

                                <div class="order-total-amount">
                                    Total: <?php echo number_format($total_val, 2) . ' ' . $order_currency; ?>
                                </div>
                            </div>

                            <div class="status-action-wrapper">
                                <?php if ($role === 'admin'): ?>
                                    <!-- Administrators can change the order status -->
                                    <form method="POST" action="orders_history.php" class="status-update-form">
                                        <input type="hidden" name="order_id" value="<?php echo $oId; ?>">
                                        <select name="order_status" class="status-select">
                                            <option value="Pending" <?php echo ($order['status'] === 'Pending') ? 'selected' : ''; ?>>Pending (Влезена)</option>
                                            <option value="Shipped" <?php echo ($order['status'] === 'Shipped') ? 'selected' : ''; ?>>Shipped (Испратена/Пуштена)</option>
                                            <option value="Delivered" <?php echo ($order['status'] === 'Delivered') ? 'selected' : ''; ?>>Delivered (Примена)</option>
                                            <option value="Completed" <?php echo ($order['status'] === 'Completed') ? 'selected' : ''; ?>>Completed (Платена/Завршена)</option>
                                        </select>
                                        <button type="submit" name="update_status" class="status-update-btn">Update</button>
                                    </form>
                                <?php else: ?>
                                    <!-- Regular users can only view the current status -->
                                    <span class="size-badge-history">🚚 Status: <?php echo $order['status']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>


                    <!-- Display metadata info for the current order template loop -->
                    <div class="order-details-meta">
                        📅 <strong>Order Placement Time:</strong> <span class="order-time-accent"><?php echo $orderTime; ?></span><br>
                        📍 <strong>Delivery Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                        📞 <strong>Receiver Contact Phone:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?> <br>
                        💳 <strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?>
                        <span class="meta-divider-span">|</span>
                        🚚 <strong>Delivery Rate:</strong>
                        <span class="delivery-badge-status">
                            <?php
                            // Check if the total amount passed the free shipping threshold limits
                            $total_val = floatval($order['total_amount']);
                            echo ($total_val >= 30.00 && $order['currency'] === 'USD' || $total_val >= 1500.00) ? "FREE (Promo Approved)" : "Standard Rate Included";
                            ?>
                        </span>

                        <?php if (!empty($order['buyer_account_email'])): ?>
                            <span class="meta-divider-span">|</span> ✉ <strong>Account Email:</strong> <span class="buyer-email-link"><?php echo htmlspecialchars($order['buyer_account_email']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Display the products included in the order table loop grid -->
                    <table class="order-items-table">
                        <thead>
                            <tr>
                                <th class="text-left-th">Product Name</th>
                                <?php if ($role === 'user'): ?>
                                    <th>Sold By (Vendor)</th>
                                <?php endif; ?>
                                <th>SKU Code</th>
                                <th>Selected Size</th>
                                <th>Purchased Qty</th>
                                <th class="text-left-th">Inventory Current Stock Summary Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all purchased items for this order without filter locks
                            $items_query = "SELECT oi.*, p.name, p.sku, p.admin_id, v.first_name as v_first, v.last_name as v_last,
                                           (SELECT GROUP_CONCAT(CONCAT(size_name, ':', quantity)) FROM `product_stock` WHERE product_id = p.id) as stock_summary,
                                           (SELECT SUM(quantity) FROM `product_stock` WHERE product_id = p.id) as total_qty
                                           FROM `order_items` oi
                                           JOIN `producttable` p ON oi.product_id = p.id
                                           JOIN `users` v ON p.admin_id = v.id
                                           WHERE oi.order_id = $oId";

                            $items_res = mysqli_query($conn, $items_query);

                            while ($item = mysqli_fetch_assoc($items_res)):

                                // Build readable text log patterns for the warehouse inventory stock levels
                                $stock_status_text = "";
                                if (!empty($item['stock_summary'])) {
                                    $stock_items_raw = explode(',', $item['stock_summary']);
                                    $filtered_stock_array = [];

                                    foreach ($stock_items_raw as $raw_stock_element) {
                                        $parts = explode(':', $raw_stock_element);
                                        if (count($parts) === 2) {
                                            $sizeName = trim($parts[0]);
                                            $sizeQty  = intval($parts[1]);

                                            if ($sizeQty > 0) {
                                                $filtered_stock_array[] = "{$sizeName} (Qty:{$sizeQty})";
                                            }
                                        }
                                    }

                                    if (!empty($filtered_stock_array)) {
                                        $stock_status_text = "Active sizes left in warehouse: " . implode(', ', $filtered_stock_array);
                                    } else {
                                        $stock_status_text = "0 / OUT OF STOCK";
                                    }
                                } else {
                                    $total_pieces_left_check = intval($item['total_qty']);
                                    $stock_status_text = ($total_pieces_left_check > 0) ? "Total pieces left: " . $total_pieces_left_check . " items" : "0 / OUT OF STOCK";
                                }

                                $vendorName = htmlspecialchars($item['v_first'] . ' ' . $item['v_last']);
                            ?>
                                <tr>
                                    <td class="product-name-cell"><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>

                                    <?php if ($role === 'user'): ?>
                                        <td class="vendor-name-cell">🏪 <?php echo $vendorName; ?></td>
                                    <?php endif; ?>

                                    <td class="sku-cell-matrix"><?php echo htmlspecialchars($item['sku']); ?></td>
                                    <td><span class="size-badge-history"><?php echo htmlspecialchars($item['selected_size']); ?></span></td>
                                    <td class="qty-weight-cell"><?php echo $item['quantity']; ?> pcs</td>
                                    <td class="stock-monitoring-status"><?php echo $stock_status_text; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
        <?php
            endwhile;
        endif;
        ?>
    </div>



    <!-- МОДЕРЕН ОПТИМИЗИРАН ФУТЕР БЛОК -->
    <footer class="site-footer">
        <div class="footer-navbar">
            <span class="footer-title">TradeVibe Infrastructure Ecosystem Matrix Control Logs</span>
            <p class="footer-author">by Blagoja Sarkisjan</p>
        </div>
    </footer>

</body>

</html>