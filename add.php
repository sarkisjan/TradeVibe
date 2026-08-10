<?php
session_start();
// Безбедносна кочница: Само најавени продавачи (admin) имаат пристап
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Premium E-Shop</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body id="bckgrnd">
    <div class="form_container">
        <header>
            <div class="navbar">
                <div class="brand-logo-zone" onclick="window.location.href='index.php';">
                    <!-- СВГ Икона која визуелно претставува брза трговија, пазар и размена на стока -->
                    <svg class="brand-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="16 3 21 3 21 8"></polyline>
                        <line x1="4" y1="20" x2="21" y2="4"></line>
                        <polyline points="21 16 21 21 16 21"></polyline>
                        <line x1="15" y1="15" x2="21" y2="21"></line>
                        <line x1="4" y1="4" x2="9" y2="9"></line>
                    </svg>
                    <span class="brand-name-text">Trade<span class="brand-accent-text">Vibe</span></span>
                </div>
                <span class="title">Add Product</span>
                <ul class="btn-list">
                    <li><button form="product_form" id="save" type="submit" class="btn btn-success" name="submit">SAVE</button></li>
                    <li><a href="index.php" class="btn btn-secondary">CANCEL</a></li>
                </ul>
            </div>
        </header>

        <div class="add-product-container">
            <form id="product_form" action="#" method="POST" enctype="multipart/form-data">
                <div class="form-grid">

                    <!-- 1. ОСНОВНИ ПОДАТОЦИ -->
                    <div class="form-item">
                        <label for="sku">SKU Code</label>
                        <input id="sku" type="text" placeholder="Enter unique SKU" name="sku">
                        <span class="error-msg" id="skuError"></span>
                    </div>

                    <div class="form-item">
                        <label for="name">Product Name</label>
                        <input id="name" type="text" placeholder="Enter product name" name="name">
                        <span class="error-msg" id="nameError"></span>
                    </div>

                    <div class="form-item full-width-field">
                        <label for="description">Product Description (Optional)</label>
                        <textarea id="description" name="description" rows="3" placeholder="Enter detailed product specifications, material info, or description..."></textarea>
                        <span class="error-msg" id="descriptionError"></span>
                    </div>

                    <div class="form-item">
                        <label for="price">Base Price ($)</label>
                        <input id="price" type="number" step="0.01" placeholder="Enter price in USD" name="price">
                        <span class="error-msg" id="priceError"></span>
                    </div>

                    <div class="form-item">
                        <label for="discount">Discount (%)</label>
                        <input id="discount" type="number" min="0" max="99" value="0" placeholder="0 if no discount" name="discount">
                        <span class="error-msg" id="discountError"></span>
                    </div>

                    <!-- 2. СЛИКА НА ПРОДУКТОТ -->
                    <div class="form-item full-width-field">
                        <label for="product_image">Product Image Gallery (Select one or multiple images)</label>
                        <input id="product_image" type="file" name="product_image[]" accept="image/*" class="file-upload-input-matrix" multiple>
                        <span class="error-msg" id="imageError"></span>
                    </div>

                    <!-- 3. КАТЕГОРИЗАЦИЈА -->
                    <div class="form-item">
                        <label for="category">Main Category</label>
                        <select name="category" id="category" required>
                            <option value="">-- Select Main Category --</option>
                            <option value="Home & Garden">Home & Garden</option>
                            <option value="Sports & Recreation">Sports & Recreation</option>
                        </select>
                        <span class="error-msg" id="categoryError"></span>
                    </div>

                    <div class="form-item">
                        <label for="subcategory">Subcategory</label>
                        <select name="subcategory" id="subcategory" required disabled>
                            <option value="">-- Select Category First --</option>
                        </select>
                        <span class="error-msg" id="subcategoryError"></span>
                    </div>

                    <!-- ДИНАМИЧНО ПОЛЕ ЗА ТИП НА ПРОСТОРИЈА ЗА МЕБЕЛ -->
                    <div class="form-item" id="furniture_type_section" style="display: none;">
                        <label for="furniture_room">Furniture Room Type</label>
                        <select name="furniture_room" id="furniture_room">
                            <option value="">-- Select Room Type --</option>
                            <option value="Bedroom">Bedroom</option>
                            <option value="Kitchen">Kitchen</option>
                            <option value="Living room">Living room</option>
                            <option value="Dining room">Dining room</option>
                            <option value="Children's room">Children's room</option>
                            <option value="Home office">Home office</option>
                            <option value="Bathroom">Bathroom</option>
                            <option value="Hallway">Hallway</option>
                        </select>
                        <span class="error-msg" id="furnitureRoomError"></span>
                    </div>

                    <!-- 4. Е-КОМЕРЦ СПЕЦИФИКАЦИИ -->
                    <div class="form-item">
                        <label for="brand">Brand / Manufacturer</label>
                        <select name="brand" id="brand" disabled>
                            <option value="">-- Select Subcategory First --</option>
                        </select>
                        <span class="error-msg" id="brandError"></span>
                    </div>

                    <!-- ДИНАМИЧНО ПОЛЕ ЗА РАЧНО ВНЕСУВАЊЕ НА БРЕНД -->
                    <div class="form-item" id="other_brand_section" style="display: none;">
                        <label for="other_brand">Enter Brand Name</label>
                        <input type="text" id="other_brand" name="other_brand" placeholder="Type manufacturer name">
                        <span class="error-msg" id="otherBrandError"></span>
                    </div>

                    <div class="form-item">
                        <label for="color">Color</label>
                        <select name="color" id="color">
                            <option value="">-- Select Color (Optional) --</option>
                            <option value="Black">Black</option>
                            <option value="White">White</option>
                            <option value="Red">Red</option>
                            <option value="Blue">Blue</option>
                            <option value="Brown">Brown</option>
                            <option value="Green">Green</option>
                            <option value="Pink">Pink</option>
                            <option value="Grey">Grey</option>
                        </select>
                        <span class="error-msg" id="colorError"></span>
                    </div>

                    <div class="form-item">
                        <label for="size_attr">Size Tag</label>
                        <select name="size_attr" id="size_attr">
                            <option value="">-- Select Size (Optional) --</option>
                            <option value="S">Small (S)</option>
                            <option value="M">Medium (M)</option>
                            <option value="L">Large (L)</option>
                            <option value="XL">Extra Large (XL)</option>
                            <option value="Standard">Standard Size</option>
                        </select>
                        <!-- СИНТАКСИЧКИ ФИКС: Овој таг сега е сигурно затворен! -->
                        <span class="error-msg" id="size_attrError"></span>
                    </div>

                    <div class="form-item">
                        <label for="gender">Gender Target</label>
                        <select name="gender" id="gender">
                            <option value="Unisex">Unisex</option>
                            <option value="Men">Men</option>
                            <option value="Women">Women</option>
                            <option value="Kids">Kids</option>
                        </select>
                        <span class="error-msg" id="genderError"></span>
                    </div>

                </div>

                <!-- Контејнерот каде JS динамички ги црта димензиите/тежините/големините со чист CSS -->
                <div class="shown_form_inputs"></div>

            </form>
        </div>
    </div>
    <footer class="site-footer">
        <div class="footer-navbar">
            <span class="footer-title">TRADEVIBE</span>
            <p class="footer-author">by Blagoja Sarkisjan</p>
        </div>
    </footer>
    <!-- Најважниот линк до твојот прочистен JS фајл -->
    <script src="scripts/app.js"></script>
</body>

</html>