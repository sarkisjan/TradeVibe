<?php
session_start();

// Change currency and reload the page
if (isset($_GET['currency'])) {
    $_SESSION['currency'] = $_GET['currency'];
    header("Location: index.php");
    exit();
}

// Use USD as the default currency
$selected_currency = isset($_SESSION['currency']) ? $_SESSION['currency'] : 'USD';

// Redirect users who are not logged in
if (!isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['user_role'];
$current_user_id = $_SESSION['user_id'];

// Get the total number of items in the cart
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO meta tags -->
    <title>TradeVibe Marketplace - Buy Shoes, Clothing & Home Appliances</title>
    <meta name="description" content="Discover the ultimate multi-vendor marketplace at TradeVibe. Explore premium home and garden assets, workout equipment, high-quality footwear, and active apparel with hot discounts.">
    <meta name="keywords" content="e-shop, tradevibe, online market, buy shoes, sports clothing, furniture, home decor, appliances">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="http://localhost/TradeVibe/index.php">

    <!-- Open Graph tags for social media sharing -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="TradeVibe Marketplace - Smart Shopping Ecosystem">
    <meta property="og:description" content="Explore and purchase high-quality international brands from top-tier vetted vendors. Secure checkout and realtime stock matrix optimization.">
    <meta property="og:url" content="http://localhost/TradeVibe/index.php">
    <meta property="og:image" content="http://localhost/TradeVibe/uploads/default-og-share.png">

    <link rel="icon" type="image/svg+xml" href="uploads/favicon.svg?v=2.0">
    <link rel="stylesheet" href="styles.css">
</head>

<body id="bckgrnd">

    <!-- Dark overlay shown when the sidebar is open -->
    <div class="overlay-bg" id="menuOverlay"></div>

    <!-- Sidebar with categories and filters -->
    <div class="sidebar-filters" id="sidebarMenu">
        <button class="close-sidebar" id="closeMenuBtn">✕</button>
        <h2 class="sidebar-main-title">Categories & Filters</h2>

        <!-- Product sorting options -->
        <div class="filter-section">
            <h3>Sort By</h3>
            <select id="sortFilter">
                <option value="default">Default Sorting</option>
                <option value="price-low">Price: Low to High</option>
                <option value="price-high">Price: High to Low</option>
                <option value="discount-high">Highest Discount (%)</option>
            </select>
        </div>

        <!-- Show all products -->
        <div class="filter-section">
            <ul class="subcat-list" style="margin: 0; padding: 0; list-style: none;">
                <li class="allProducts" data-sub="global-all">🌐 All Products Marketplace</li>
            </ul>
        </div>
        <!-- Home and Garden category -->
        <div class="filter-section">
            <h3>Home & Garden</h3>
            <ul class="subcat-list" data-category="Home & Garden">
                <li data-sub="all" class="active">All Home & Garden</li>
                <li data-sub="Souvenirs">Souvenirs & Decor</li>

                <!-- Furniture category with room submenu -->
                <li data-sub="Furniture-Parent" id="furnitureParentLi" class="furniture-accordion-item">
                    <div class="furniture-trigger-zone">
                        <span>Furniture</span>
                        <span id="furnitureArrow">▼</span>
                    </div>

                    <ul class="room-dropdown-menu" id="furnitureRoomMenu">
                        <li data-room="all" class="all-rooms-btn">All Rooms</li>
                        <li data-room="Bedroom">Bedroom</li>
                        <li data-room="Kitchen">Kitchen</li>
                        <li data-room="Living room">Living room</li>
                        <li data-room="Dining room">Dining room</li>
                        <li data-room="Children's room">Children's room</li>
                        <li data-room="Home office">Home office</li>
                        <li data-room="Bathroom">Bathroom</li>
                        <li data-room="Hallway">Hallway</li>
                    </ul>
                </li>

                <li data-sub="Balcony">Balcony & Garden</li>
                <li data-sub="Kitchen">Kitchen Appliances</li>
                <li data-sub="Appliances">White Goods / Appliances</li>
                <li data-sub="Bedding">Bedding</li>
            </ul>
        </div>

        <!-- Sports and Recreation category -->
        <div class="filter-section">
            <h3>Sports & Recreation</h3>
            <ul class="subcat-list" data-category="Sports & Recreation">
                <li data-sub="all" data-category="Sports & Recreation" class="active">All Sports</li>
                <li data-sub="Supplements">Supplements (Vitamins, Proteins etc.)</li>
                <li data-sub="Book">Books</li>
                <li data-sub="Clothing">Sports Clothing</li>
                <li data-sub="Equipment">Workout Equipment</li>
                <li data-sub="Shoes">Footwear / Shoes</li>
                <li data-sub="Summer">Seasonal: Swimwear & Beach</li>
                <li data-sub="Winter">Seasonal: Skiing Equipment</li>
            </ul>
        </div>

        <!-- Brand filter -->
        <div class="filter-section">
            <h3>Filter By Brand</h3>
            <select id="brandFilter">
                <option value="all">All Brands</option>
                <option value="Nike">Nike</option>
                <option value="Adidas">Adidas</option>
                <option value="Puma">Puma</option>
                <option value="Hummel">Hummel</option>
                <option value="Kappa">Kappa</option>
                <option value="Ikea">Ikea</option>
                <option value="Treska">Treska</option>
                <option value="Formanova">Formanova</option>
                <option value="Jela">Jela</option>
                <option value="Samsung">Samsung</option>
                <option value="Whirlpool">Whirlpool</option>
                <option value="Beko">Beko</option>
                <option value="Indesit">Indesit</option>
                <option value="LG">LG</option>
            </select>
        </div>

        <div class="filter-section">
            <!-- Color filter -->
            <h3>Filter By Color</h3>
            <select id="colorFilter">
                <option value="all">All Colors</option>
                <option value="Black">Black</option>
                <option value="White">White</option>
                <option value="Red">Red</option>
                <option value="Blue">Blue</option>
                <option value="Brown">Brown</option>
            </select>
        </div>

        <!-- Size filter -->
        <div class="filter-section">
            <h3>Filter By Size</h3>
            <select id="sizeFilter">
                <option value="all">All Sizes</option>
                <option value="Standard">Standard Size</option>
                <option value="XS">XS</option>
                <option value="S">S</option>
                <option value="M">M</option>
                <option value="L">L</option>
                <option value="XL">XL</option>
                <option value="XXL">XXL</option>
                <option value="XXXL">XXXL</option>
            </select>
        </div>

        <!-- Gender filter -->
        <div class="filter-section">
            <h3>Filter By Gender</h3>
            <select id="genderFilter">
                <option value="all">All Genders</option>
                <option value="Unisex">Unisex</option>
                <option value="Men">Men</option>
                <option value="Women">Women</option>
                <option value="Kids">Kids</option>
            </select>
        </div>

        <!-- Reset all filters -->
        <button class="btn btn-primary" id="resetFiltersBtn">RESET FILTERS</button>
    </div>


    <!-- Main page content -->
    <div class="form_container">
        <header>

            <div class="navbar">
                <div class="nav-left-wrapper">

                    <!-- Open the categories sidebar -->
                    <button class="hamburger-btn" id="hamburgerBtn" title="Open Categories">
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                        <span class="hamburger-line"></span>
                    </button>

                    <!-- TradeVibe logo and home link -->
                    <div class="brand-logo-zone" onclick="window.location.href='index.php';">
                        <!-- SVG icon used for the TradeVibe logo -->
                        <svg class="brand-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 3 21 3 21 8"></polyline>
                            <line x1="4" y1="20" x2="21" y2="4"></line>
                            <polyline points="21 16 21 21 16 21"></polyline>
                            <line x1="15" y1="15" x2="21" y2="21"></line>
                            <line x1="4" y1="4" x2="9" y2="9"></line>
                        </svg>
                        <span class="brand-name-text">Trade<span class="brand-accent-text">Vibe</span></span>
                    </div>

                    <!-- Currency selector -->
                    <!-- MULTI-CURRENCY CONTEXT SWITCHER: Pure semantic HTML layout synchronized via clean javascript events -->
                    <div class="currency-switcher">
                        <label for="currencySelect">Currency: </label>
                        <select id="currencySelect" class="currency-dropdown-select">
                            <option value="index.php?currency=USD" <?php echo ($selected_currency === 'USD') ? 'selected' : ''; ?>>USD ($)</option>
                            <option value="index.php?currency=EUR" <?php echo ($selected_currency === 'EUR') ? 'selected' : ''; ?>>EUR (€)</option>
                            <option value="index.php?currency=MKD" <?php echo ($selected_currency === 'MKD') ? 'selected' : ''; ?>>MKD (ден)</option>
                        </select>
                    </div>

                </div>
                <!-- Navigation buttons -->
                <!-- NAVIGATION BUTTON LIST: Multi-role responsive action triggers configured dynamically -->

                <ul class="btn-list">
                    <!-- Show the cart only for verified customer session layers -->
                    <?php if ($_SESSION['user_role'] === 'user'): ?>
                        <li>
                            <a href="view_cart.php" class="nav-cart-link">
                                <div class="cart-icon-wrapper">
                                    <span class="cart-icon"></span>
                                    <span id="cart-counter" class="cart-badge"><?php echo $cartCount; ?></span>
                                </div>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Show platform admin management actions for vendor and root supervisor profiles -->
                    <?php if ($role === 'admin' || $role === 'root'): ?>
                        <!-- Dedicated conditional barrier: Only verified sellers can append new catalog items -->
                        <?php if ($role === 'admin'): ?>
                            <li><a href="add.php" class="btn btn-primary">ADD</a></li>
                        <?php endif; ?>
                        <li><button class="btn btn-danger mass_delete">DELETE</button></li>
                    <?php endif; ?>

                </ul>

                <!-- User profile interactive dropdown menu ecosystem -->
                <span class="profile-container">
                    <button class="profile-icon-btn" id="profileBtn">👤</button>

                    <div class="profile-dropdown" id="profileMenu">
                        <!-- Display basic account specification information logs -->
                        <p><strong>Account Type:</strong> <?php echo ($_SESSION['user_role'] === 'admin') ? 'Seller' : ($_SESSION['user_role'] === 'root' ? 'Root System Admin' : 'Customer'); ?></p>
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($_SESSION['address']); ?></p>
                        <hr>

                        <!-- Render explicit control dashboard actions based on real-time security roles -->
                        <?php if ($_SESSION['user_role'] === 'root'): ?>
                            <a href="manage_sellers.php" class="root-matrix-link">⚙ Manage Sellers Matrix</a>
                        <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="orders_history.php" class="profile-edit-link vendor-matrix-link">📋 Global Orders Matrix</a>
                        <?php elseif ($_SESSION['user_role'] === 'user'): ?>
                            <a href="orders_history.php" class="profile-edit-link buyer-history-link">🛍 My Orders (History Log)</a>
                        <?php endif; ?>

                        <!-- Account profile adjustment operations layout bound to clean standalone classes -->
                        <a href="edit_profile.php" class="profile-edit-link profile-info-block">Edit Profile Info</a>
                        <a href="logout.php" class="logout-link">Log Out</a>
                    </div>
                </span>


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

        <!-- Products are loaded here by appIndex.js -->
        <div class="productList">

        </div>
    </div>

    <!-- Quick edit modal for product price and discount -->
    <div id="editPriceModal" class="quick-edit-modal-overlay">
        <div class="quick-edit-content-box">
            <h3>Quick Edit Product</h3>

            <!-- Store the product ID while editing -->
            <input type="hidden" id="edit_modal_product_id">

            <div class="quick-edit-field-group">
                <label>Base Price ($)</label>
                <input type="number" id="edit_modal_price" step="0.01">
            </div>

            <div class="quick-edit-field-group field-bottom-margin">
                <label>Discount (%)</label>
                <input type="number" id="edit_modal_discount" min="0" max="99">
            </div>

            <!-- Modal action buttons -->
            <div class="quick-edit-action-zone">
                <button class="btn btn-secondary quick-edit-modal-btn" id="closeEditModalBtn">Cancel</button>
                <button class="btn btn-success quick-edit-modal-btn" id="savePriceBtn">Save Changes</button>
            </div>
        </div>
    </div>


    <!-- Product quick-view modal -->

    <div id="productViewModal" class="modal-overlay">
        <div class="modal-content-box">

            <button id="closeViewModalBtn" class="modal-close-btn">✕</button>

            <!-- Product image gallery -->
            <div class="modal-left-gallery">
                <button id="prevImgBtn" class="modal-nav-btn">‹</button>
                <div id="modalImageFrame" class="modal-image-frame"></div>
                <button id="nextImgBtn" class="modal-nav-btn">›</button>
            </div>

            <!-- Product information -->
            <div class="modal-right-info">
                <div>
                    <span id="modalSku" class="modal-sku"></span>
                    <h2 id="modalName" class="modal-title"></h2>

                    <div id="modalSpecs" class="modal-specs-container"></div>

                    <!-- Collapsible product description -->
                    <div id="modalDescWrapper" class="modal-desc-wrapper">
                        <button id="toggleDescBtn" class="modal-desc-toggle">
                            <span>Product Description</span>
                            <span id="descArrow">▼</span>
                        </button>
                        <div id="modalDescription" class="modal-desc-content"></div>
                    </div>
                    <!-- Stock management for sellers -->
                    <div id="modalSellerStockSection" class="quick-edit-field-group" style="display: none; margin-top: 20px;">
                        <h4 style="font-size: 14px; font-weight: 700; color: #2d3748; margin-bottom: 10px; border-bottom: 1px dashed #cbd5e0; padding-bottom: 5px;">📦 Stock Management System</h4>
                        <div id="modalStockMatrixTableContainer"></div>
                    </div>

                    <!-- Product size or configuration options -->
                    <div id="modalSizeWrapper" class="modal-size-wrapper">
                        <label class="modal-size-label">Select Size / Configuration:</label>
                        <div id="modalSizesContainer" class="modal-sizes-flex"></div>
                        <!-- Stores the selected size -->
                        <input type="hidden" id="modalSelectedSizeTracker" value="">
                    </div>
                </div>

                <!-- Product price and action buttons -->
                <div class="modal-footer-action">
                    <div>
                        <span class="modal-price-label">Price Amount:</span>
                        <span id="modalPrice" class="modal-price-val"></span>
                    </div>
                    <div id="modalActionContainer" class="modal-action-btn-zone"></div>
                </div>
            </div>

        </div>
    </div>

    <footer class="site-footer">
        <div class="footer-navbar">
            <span class="footer-title">TRADEVIBE</span>
            <p class="footer-author">by Blagoja Sarkisjan</p>
        </div>
    </footer>

    <!-- Pass PHP session data to JavaScript -->
    <script>
        const currentUserRole = "<?php echo $_SESSION['user_role']; ?>";
        const currentUserId = parseInt("<?php echo $_SESSION['user_id']; ?>");
        console.log("Logged User Role:", currentUserRole, "ID:", currentUserId);
    </script>

    <!-- Main product JavaScript file -->
    <script src="scripts/appIndex.js"></script>

    <!-- Handle sidebar, category, furniture, and profile menu actions -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const closeMenuBtn = document.getElementById('closeMenuBtn');
            const sidebarMenu = document.getElementById('sidebarMenu');
            const menuOverlay = document.getElementById('menuOverlay');

            const profileBtn = document.getElementById('profileBtn');
            const profileMenu = document.getElementById('profileMenu');

            // Open and close the hamburger sidebar
            if (hamburgerBtn && sidebarMenu && menuOverlay) {
                hamburgerBtn.addEventListener('click', () => {
                    sidebarMenu.classList.add('active');
                    menuOverlay.classList.add('active');
                });

                const closeSidebar = () => {
                    sidebarMenu.classList.remove('active');
                    menuOverlay.classList.remove('active');
                };

                if (closeMenuBtn) closeMenuBtn.addEventListener('click', closeSidebar);
                menuOverlay.addEventListener('click', closeSidebar);
            }

            // Handle clicks on regular product categories
            document.querySelectorAll(".subcat-list > li").forEach(li => {
                li.addEventListener("click", function(e) {
                    // Ignore clicks inside the furniture room menu
                    if (e.target.closest('.room-dropdown-menu') || this.id === "furnitureParentLi") return;

                    // Remove active states from other categories
                    document.querySelectorAll(".subcat-list li").forEach(el => el.classList.remove("active"));
                    document.querySelectorAll(".room-dropdown-menu li").forEach(el => el.classList.remove("active-room"));

                    // Store the selected category and subcategory
                    window.selectedFurnitureRoom = "all";
                    window.activeSelectedSubcategoryTracker = this.getAttribute("data-sub");
                    window.activeSelectedCategoryTracker = this.parentElement.getAttribute("data-category");

                    this.classList.add("active");

                    // Update the product list
                    if (typeof filterAndRenderProducts === "function") {
                        filterAndRenderProducts();
                    }

                    // Close the sidebar after selecting a category
                    if (sidebarMenu && menuOverlay) {
                        sidebarMenu.classList.remove('active');
                        menuOverlay.classList.remove('active');
                    }
                });
            });

            // Handle furniture room selection
            document.querySelectorAll(".room-dropdown-menu li").forEach(roomLi => {
                roomLi.addEventListener("click", function(e) {
                    // Prevent the parent Furniture item from being triggered   
                    e.stopPropagation();

                    // Update the selected room
                    document.querySelectorAll(".room-dropdown-menu li").forEach(el => el.classList.remove("active-room"));
                    this.classList.add("active-room");

                    // Store the selected furniture filters
                    window.selectedFurnitureRoom = this.getAttribute("data-room");
                    window.activeSelectedSubcategoryTracker = "Furniture";
                    window.activeSelectedCategoryTracker = "Home & Garden";

                    // Set Furniture as the active category
                    document.querySelectorAll(".subcat-list li").forEach(el => el.classList.remove("active"));
                    document.getElementById("furnitureParentLi").classList.add("active");

                    // Update the product list
                    if (typeof filterAndRenderProducts === "function") {
                        filterAndRenderProducts();
                    }

                    // Close the sidebar after selecting a room
                    if (sidebarMenu && menuOverlay) {
                        sidebarMenu.classList.remove('active');
                        menuOverlay.classList.remove('active');
                    }
                });
            });



            // Open and close the profile menu
            if (profileBtn && profileMenu) {
                profileBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    profileMenu.style.display = (profileMenu.style.display === 'block') ? 'none' : 'block';
                });
                // Close the profile menu when clicking outside it
                document.addEventListener('click', (e) => {
                    if (!profileMenu.contains(e.target) && e.target !== profileBtn) {
                        profileMenu.style.display = 'none';
                    }
                });
            }
        });
    </script>


</body>

</html>