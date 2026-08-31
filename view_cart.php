<?php
session_start();
require_once "includes/autoloader.php";
require_once "classes/Currency.php";

$process = new Process();
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Clear the cart if requested
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['cart'] = [];
    header("Location: view_cart.php");
    exit();
}

$product_ids = [];
$products = [];
// Collect all valid product IDs currently stored in the cart session
foreach ($cart_items as $cartItem) {
    if (is_array($cartItem) && isset($cartItem['product_id'])) {
        $product_ids[] = intval($cartItem['product_id']);
    }
}

$product_ids = array_unique($product_ids);

// Fetch product details for all products currently stored in the cart
if (!empty($product_ids)) {
    $products = $process->getCartProducts($product_ids);
}

$currency = isset($_SESSION['currency']) ? $_SESSION['currency'] : 'USD';
$currency_symbol = Currency::getSymbol($currency);

$total_price = 0;
$missing_size_detected = false;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="icon" type="image" href="uploads/favicon.ico">
    <link rel="stylesheet" href="styles.css">
</head>

<body id="bckgrnd">
    <div class="view-cart-container">
        <header>
            <div class="navbar">
                <div class="brand-logo-zone" onclick="window.location.href='index.php';">
                    <!-- SVG Icon representing fast market trading and exchange -->
                    <svg class="brand-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="16 3 21 3 21 8"></polyline>
                        <line x1="4" y1="20" x2="21" y2="4"></line>
                        <polyline points="21 16 21 21 16 21"></polyline>
                        <line x1="15" y1="15" x2="21" y2="21"></line>
                        <line x1="4" y1="4" x2="9" y2="9"></line>
                    </svg>
                    <span class="brand-name-text">Trade<span class="brand-accent-text">Vibe</span></span>
                </div>
                <span class="title">Shopping Cart</span>
                <ul class="btn-list">
                    <li><a href="index.php" class="btn btn-secondary">STOREFRONT</a></li>
                    <?php if (!empty($products)): ?>
                        <li><a href="view_cart.php?action=clear" class="btn clear-btn">CLEAR CART</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </header>
        <!-- MARKETING PROMO TICKER: Dynamically adjusted based on active session currency logs -->
        <?php
        // Catch the active store currency layout from session memory registers
        $current_store_currency = isset($_SESSION['currency']) ? $_SESSION['currency'] : 'USD';

        // Initialize the default fallback promo copy parameters
        $promo_text_message = "Free shipping on all orders over $50! Add more premium items to your shopping cart and save on delivery checkout configurations!";

        // Conditional logic block updating the layout copy text inline with currency selections
        if ($current_store_currency === 'MKD' || $current_store_currency === 'ден') {
            $promo_text_message = "Free shipping on all orders over 2500 MKD! Add more premium items to your shopping cart and save on delivery checkout configurations!";
        } elseif ($current_store_currency === 'EUR' || $current_store_currency === '€') {
            $promo_text_message = "Free shipping on all orders over 42 EUR! Add more premium items to your shopping cart and save on delivery checkout configurations!";
        }
        ?>
        <div class="promo-ticker-banner">
            <div class="ticker-text-wrap">
                <span>🔥 SPECIAL PROMO OFFER: <?php echo $promo_text_message; ?> 🔥</span>
            </div>
        </div>

        <div class="cart-container">
            <!-- Clean layout with classes instead of inline properties -->
            <h2 style="text-align: left; font-size: 22px; font-weight: 700; color: #1a202c;">Your Selected Items</h2>

            <?php if (empty($products)): ?>
                <p style="margin-top: 25px; font-size: 1.1em; text-align: left; color:#718096;">Your cart is empty. <a href="index.php" style="color: #3498db; font-weight: bold; text-decoration: none;">Go back to the shop</a> to add products.</p>
            <?php else: ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Selected Size</th> <!-- Size Tracking Column -->
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $cartKey => $cartItem):

                            // Make sure this is a valid cart item
                            if (!is_array($cartItem) || !isset($cartItem['product_id'])) {
                                continue;
                            }

                            // Get product ID, quantity and selected size from the cart item
                            $id = intval($cartItem['product_id']);
                            $qty = intval($cartItem['qty']);
                            $size = isset($cartItem['size']) && $cartItem['size'] !== ''
                                ? $cartItem['size']
                                : 'Standard';

                            // Find the product information returned from the database
                            $product = null;

                            foreach ($products as $p) {
                                if (intval($p['id']) === $id) {
                                    $product = $p;
                                    break;
                                }
                            }

                            // Skip invalid/missing products
                            if (!$product) {
                                continue;
                            }

                            // Verify if size configuration is required
                            // for Clothing and Footwear products
                            $subLower = isset($product['subcategory'])
                                ? strtolower($product['subcategory'])
                                : '';

                            $isSizeRequired = in_array(
                                $subLower,
                                ['shoes', 'clothing', 'summer', 'winter']
                            );

                            $size_display_html = "";

                            if (
                                $isSizeRequired &&
                                ($size === 'Standard' || empty($size))
                            ) {

                                // Block checkout if a required size is missing
                                $size_display_html =
                                    "<span class='size-error-badge'>⚠️ Not Selected</span>";

                                $missing_size_detected = true;
                            } else {

                                $size_display_html =
                                    "<span class='size-badge'>" .
                                    htmlspecialchars($size) .
                                    "</span>";
                            }

                            // Clean price string
                            $clean_price = str_replace(
                                ['$', ' ', 'den', 'EUR', '€'],
                                '',
                                $product['price']
                            );

                            $item_base_price = floatval($clean_price);

                            // Apply product discount
                            $discount = intval($product['discount']);

                            if ($discount > 0) {
                                $item_base_price =
                                    $item_base_price -
                                    ($item_base_price * ($discount / 100));
                            }

                            // Convert price to selected currency
                            $converted_unit_price =
                                Currency::convert($item_base_price, $currency);

                            // Calculate subtotal for this specific
                            // product + size combination
                            $subtotal = $converted_unit_price * $qty;

                            $total_price += $subtotal;

                            // Product images
                            $allImages = $product['image']
                                ? explode(',', $product['image'])
                                : [];

                            $firstImage =
                                (!empty($allImages) && trim($allImages[0]) !== '')
                                ? "uploads/" . $allImages[0]
                                : "";

                        ?>
                            <tr id="row-<?php echo htmlspecialchars($cartKey); ?>">
                                <td>
                                    <div class="cart-img" style="background-image: url('<?php echo $firstImage; ?>');">
                                        <?php if ($firstImage === ''): ?>
                                            <span style="font-size: 10px; color: #aaa; line-height: 60px;">No Img</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>

                                <!-- SIZE VISUAL MATRIX CELL -->
                                <td><?php echo $size_display_html; ?></td>

                                <td><span style="color:#ef4444; font-weight:700;"><?php echo number_format($converted_unit_price, 2) . $currency_symbol; ?></span></td>

                                <td>
                                    <select
                                        class="qty-dropdown"
                                        data-id="<?php echo $id; ?>"
                                        data-size="<?php echo htmlspecialchars($size); ?>"
                                        data-cart-key="<?php echo htmlspecialchars($cartKey); ?>">
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($i == $qty) ? 'selected' : ''; ?>>
                                                <?php echo $i; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </td>

                                <td class="subtotal-cell" data-price="<?php echo $converted_unit_price; ?>" style="font-weight:700; color:#1a202c;">
                                    <?php echo number_format($subtotal, 2) . $currency_symbol; ?>
                                </td>
                                <td>
                                    <button
                                        class="remove-item-btn"
                                        data-id="<?php echo $id; ?>"
                                        data-size="<?php echo htmlspecialchars($size); ?>"
                                        data-cart-key="<?php echo htmlspecialchars($cartKey); ?>">REMOVE</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="cart-summary">
                    <?php
                    // Shipping calculation constants based on marketplace standards
                    $shipping_threshold = 30.00; // Free shipping limit for USD
                    $standard_shipping_rate = 3.00; // Standard shipping rate for USD

                    // Adjust thresholds dynamically if the currency is set to Macedonian Denars (MKD)
                    if ($currency === 'MKD' || $currency === 'ден') {
                        $shipping_threshold = 1500.00;
                        $standard_shipping_rate = 150.00;
                    } elseif ($currency === 'EUR' || $currency === '€') {
                        $shipping_threshold = 25.00;
                        $standard_shipping_rate = 2.50;
                    }

                    // If total price is above the threshold, shipping becomes completely free
                    $shipping_cost = ($total_price >= $shipping_threshold) ? 0.00 : $standard_shipping_rate;
                    $final_grand_total = $total_price + $shipping_cost;
                    ?>

                    <!-- Structured calculation lines without inline styling -->
                    <div class="summary-line-item">
                        <span>Items Subtotal:</span>
                        <strong><?php echo number_format($total_price, 2) . ' ' . $currency_symbol; ?></strong>
                    </div>

                    <div class="summary-line-item">
                        <span>Shipping Delivery:</span>
                        <strong style="color: <?php echo ($shipping_cost == 0) ? '#2cbd6c' : '#e67e22'; ?>;">
                            <?php echo ($shipping_cost == 0) ? 'FREE' : number_format($shipping_cost, 2) . ' ' . $currency_symbol; ?>
                        </strong>
                    </div>

                    <hr style="border: 0; border-top: 1px solid #edf2f7; margin: 10px 0;">

                    <p class="cart-total-amount-wrapper">
                        <strong>Total Amount: <span id="total-amount-display"><?php echo number_format($final_grand_total, 2) . ' ' . $currency_symbol; ?></span></strong>
                    </p>

                    <?php if ($missing_size_detected): ?>
                        <button class="checkout-btn disabled-btn" onclick="alert('Cannot proceed to checkout! One or more items require a size configuration.');">CHECKOUT LOCKED</button>
                    <?php else: ?>
                        <button type="button" class="checkout-btn" id="openCheckoutModalBtn">PROCEED TO CHECKOUT</button>
                    <?php endif; ?>
                </div>
        </div>
    <?php endif; ?>
    </div>

    <footer class="site-footer">
        <div class="footer-navbar">
            <span class="footer-title">TRADEVIBE</span>
            <p class="footer-author">by Blagoja Sarkisjan</p>
        </div>
    </footer>
    <!-- CHECKOUT POTVRDA MODAL -->
    <div id="checkoutConfirmModal" class="checkout-modal-overlay">
        <div class="checkout-modal-box">
            <button id="closeCheckoutModalBtn" class="checkout-close-btn">✕</button>
            <h3>Confirm Shipping Details</h3>

            <form action="checkout.php" method="POST" id="final_checkout_form">
                <div class="checkout-field">
                    <label>Receiver Full Name</label>
                    <input type="text" name="delivery_name" value="<?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>" required>
                </div>
                <div class="checkout-field">
                    <label>Shipping Address</label>
                    <input type="text" name="delivery_address" value="<?php echo htmlspecialchars($_SESSION['address']); ?>" required>
                </div>
                <div class="checkout-field">
                    <label>Contact Phone Number</label>
                    <input type="text" name="delivery_phone" value="<?php echo htmlspecialchars(!empty($_SESSION['phone']) ? $_SESSION['phone'] : ''); ?>" required placeholder="Enter active phone number">
                </div>

                <div class="payment-method-zone">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="Cash on Delivery" checked required>
                        <span class="custom-radio"></span>
                        <strong>Cash on Delivery (Плаќање при достава)</strong>
                    </label>
                </div>

                <div class="checkout-modal-footer">
                    <p>Total to Pay: <strong><?php echo number_format($total_price, 2) . $currency_symbol; ?></strong></p>
                    <button type="submit" class="btn btn-success">PLACE ORDER</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Store the currently selected currency symbol for JavaScript calculations
        const currencySymbol = "<?php echo $currency_symbol; ?>";

        document.addEventListener("DOMContentLoaded", () => {

            //  Handle quantity changes through the quantity dropdown
            document.querySelectorAll('.qty-dropdown').forEach(dropdown => {

                dropdown.addEventListener('change', function() {

                    const productId =
                        this.getAttribute('data-id');

                    const cartKey =
                        this.getAttribute('data-cart-key');

                    const newQty =
                        parseInt(this.value);

                    const row =
                        document.getElementById(
                            `row-${cartKey}`
                        );

                    const subtotalCell =
                        row.querySelector('.subtotal-cell');

                    const price =
                        parseFloat(
                            subtotalCell.getAttribute('data-price')
                        );

                    const xhttp =
                        new XMLHttpRequest();

                    xhttp.open(
                        "POST",
                        "cart_handler.php",
                        true
                    );

                    xhttp.setRequestHeader(
                        'Content-Type',
                        'application/json'
                    );

                    xhttp.onreadystatechange = function() {

                        if (
                            this.readyState === 4 &&
                            this.status === 200
                        ) {

                            const res =
                                JSON.parse(this.response);

                            if (res.success) {

                                // Use the server-confirmed quantity when available
                                const actualQty =
                                    res.quantity || newQty;

                                const newSubtotal =
                                    price * actualQty;

                                subtotalCell.innerText =
                                    newSubtotal.toFixed(2) +
                                    currencySymbol;

                                // Recalculate the complete cart total
                                recalculateTotal();

                            } else {

                                /*
                                 * If server rejected the quantity,
                                 * restore the actual quantity.
                                 */
                                if (res.quantity) {
                                    dropdown.value =
                                        res.quantity;
                                }

                                alert(res.message);
                            }
                        }
                    };

                    // Send the quantity update request to the cart handler
                    xhttp.send(JSON.stringify({
                        action: 'update',
                        product_id: productId,
                        cart_key: cartKey,
                        quantity: newQty
                    }));
                });
            });

            // Handle product removal from the shopping cart
            document.addEventListener('click', function(event) {

                if (
                    event.target &&
                    event.target.classList.contains('remove-item-btn')
                ) {

                    const productId =
                        event.target.getAttribute('data-id');

                    const cartKey =
                        event.target.getAttribute('data-cart-key');

                    const xhttp = new XMLHttpRequest();

                    xhttp.open("POST", "cart_handler.php", true);

                    xhttp.setRequestHeader(
                        'Content-Type',
                        'application/json'
                    );

                    xhttp.onreadystatechange = function() {

                        if (
                            this.readyState === 4 &&
                            this.status === 200
                        ) {

                            const res =
                                JSON.parse(this.response);

                            if (res.success) {

                                // Remove the corresponding product row from the page
                                const rowToRemove =
                                    document.getElementById(
                                        `row-${cartKey}`
                                    );

                                if (rowToRemove) {
                                    rowToRemove.remove();
                                }

                                // Reload the page when the cart becomes empty
                                if (
                                    document.querySelectorAll(
                                        '.qty-dropdown'
                                    ).length === 0
                                ) {

                                    window.location.reload();

                                } else {

                                    // Recalculate the total after removing an item
                                    recalculateTotal();
                                }
                            }
                        }
                    };

                    // Send the product removal request to the cart handler
                    xhttp.send(JSON.stringify({
                        action: 'remove',
                        product_id: productId,
                        cart_key: cartKey
                    }));
                }
            });
            // Handle the checkout confirmation modal
            const checkoutModal = document.getElementById('checkoutConfirmModal');
            const proceedBtn = document.querySelector('.checkout-btn:not(.disabled-btn)');
            const closeCheckoutBtn = document.getElementById('closeCheckoutModalBtn');

            // Open the checkout confirmation modal
            if (proceedBtn && checkoutModal) {
                proceedBtn.removeAttribute('href');
                proceedBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    checkoutModal.style.display = 'flex'; // Елегантна флекс визуелизација
                });
            }

            // Close the checkout confirmation modal
            if (closeCheckoutBtn && checkoutModal) {
                closeCheckoutBtn.addEventListener('click', () => {
                    checkoutModal.style.display = 'none'; // Се сокрива безбедно
                });
            }

            // Dynamically recalculate the total amount displayed in the cart
            function recalculateTotal() {
                let total = 0;
                document.querySelectorAll('.subtotal-cell').forEach(cell => {
                    let cellText = cell.innerText.replace(currencySymbol.trim(), '').trim();
                    let subtotalValue = parseFloat(cellText);
                    if (!isNaN(subtotalValue)) {
                        total += subtotalValue;
                    }
                });
                document.getElementById('total-amount-display').innerText = total.toFixed(2) + currencySymbol;
            }

        });
    </script>


</body>

</html>