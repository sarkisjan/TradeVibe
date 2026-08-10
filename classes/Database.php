
<?php

class Database
{
    private $servername;
    private $username;
    private $password;
    private $dbname;

    // Дефинирање на имињата на сите табели како својства
    protected $tbname;          // Табела за производи
    protected $usersTable;      // Табела за корисници
    protected $cartTable;       // Табела за привремена кошничка
    protected $ordersTable;     // НОВО: Табела за главни нарачки
    protected $orderItemsTable; // НОВО: Табела за артикли во нарачките

    public $errors = [];

    public function initDatabase()
    {
        $this->connect();
    }

    public function connect()
    {
        $this->servername = "localhost";
        $this->username = "root";
        $this->password = "";
        $this->dbname = "products";

        // Имиња на табелите во базата
        $this->tbname          = "producttable";
        $this->usersTable      = "users";
        $this->cartTable       = "cart";
        $this->ordersTable     = "orders";
        $this->orderItemsTable = "order_items";

        // Поврзување со серверот
        $conn = mysqli_connect($this->servername, $this->username, $this->password);

        if (!$conn) {
            $this->errors[] = "Connection failed: " . mysqli_connect_error();
            return null;
        }

        // Креирање на базата ако не постои
        $sql_db = "CREATE DATABASE IF NOT EXISTS `$this->dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
        if (!mysqli_query($conn, $sql_db)) {
            $this->errors[] = "Error creating database: " . mysqli_error($conn);
            return null;
        }

        // Селектирање на базата
        mysqli_select_db($conn, $this->dbname);

        // ТАБЕЛА ЗА КОРИСНИЦИ (АЖУРИРАНА СО СИТЕ НОВИ ПОЛИЊА И УЛОГАТА ROOT)

        $sql_users = "CREATE TABLE IF NOT EXISTS `$this->usersTable` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `username` VARCHAR(50) NOT NULL,
            `first_name` VARCHAR(50) NOT NULL,
            `last_name` VARCHAR(50) NOT NULL,
            `phone` VARCHAR(20) NOT NULL,
            `email` VARCHAR(100) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `address` VARCHAR(255) NOT NULL,
            `role` ENUM('user', 'admin', 'root') NOT NULL DEFAULT 'user',
            `verification_token` VARCHAR(64) DEFAULT NULL,
            `is_verified` TINYINT(1) DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email_unique` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";


        if (!mysqli_query($conn, $sql_users)) {
            $this->errors[] = "Error creating users table: " . mysqli_error($conn);
        }

        // ТАБЕЛА ЗА ПРОИЗВОДИ (АЖУРИРАНА СО КАТЕГОРИИ, ОПИС, ПОПУСТ И CASCADE АДМИН ИД)
        $sql_products = "CREATE TABLE IF NOT EXISTS `$this->tbname` (
                `id` INT(10) NOT NULL AUTO_INCREMENT,
                `admin_id` INT(11) NOT NULL DEFAULT 1,
                `sku` VARCHAR(10) NOT NULL,
                `name` VARCHAR(50) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `price` DECIMAL(10,2) NOT NULL,
                `discount` INT(3) DEFAULT 0,
                `image` VARCHAR(255) DEFAULT 'default-product.png',
                `category` VARCHAR(50) NOT NULL,
                `subcategory` VARCHAR(50) NOT NULL,
                `furniture_room` VARCHAR(50) DEFAULT NULL,
                `brand` VARCHAR(50) DEFAULT NULL,
                `color` VARCHAR(30) DEFAULT NULL,
                `size_attr` VARCHAR(20) DEFAULT NULL,
                `gender` ENUM('Unisex', 'Men', 'Women', 'Kids') DEFAULT 'Unisex',
                `size` INT(10) DEFAULT NULL,
                `weight` INT(10) DEFAULT NULL,
                `height` INT(6) DEFAULT NULL,
                `width` INT(6) DEFAULT NULL,
                `length` INT(6) DEFAULT NULL,
                `productType` VARCHAR(50) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `sku_unique` (`sku`),
                FOREIGN KEY (`admin_id`) REFERENCES `$this->usersTable`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if (!mysqli_query($conn, $sql_products)) {
            $this->errors[] = "Error creating products table: " . mysqli_error($conn);
        }

        // ТАБЕЛА ЗА ПРИВРЕМЕНА КОШНИЧКА
        $sql_cart = "CREATE TABLE IF NOT EXISTS `$this->cartTable` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL,
                `product_id` INT(10) NOT NULL,
                `quantity` INT(5) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES `$this->usersTable`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`product_id`) REFERENCES `$this->tbname`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if (!mysqli_query($conn, $sql_cart)) {
            $this->errors[] = "Error creating cart table: " . mysqli_error($conn);
        }

        // ГЛАВНИ НАРАЧКИ (ORDERS за зачувување при Checkout)
        $sql_orders = "CREATE TABLE IF NOT EXISTS `$this->ordersTable` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL,
                `total_amount` DECIMAL(10,2) NOT NULL,
                `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
                `shipping_address` VARCHAR(255) NOT NULL,
                `shipping_name` VARCHAR(255) NOT NULL,
                `shipping_phone` VARCHAR(50) NOT NULL,
                `payment_method` VARCHAR(100) NOT NULL,
                `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
                `order_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES `$this->usersTable`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if (!mysqli_query($conn, $sql_orders)) {
            $this->errors[] = "Error creating orders table: " . mysqli_error($conn);
        }

        // СТАВКИ ОД НАРАЧКАТА (ORDER_ITEMS - кои точни производи се купени)

        $sql_order_items = "CREATE TABLE IF NOT EXISTS `$this->orderItemsTable` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `order_id` INT(11) NOT NULL,
                `product_id` INT(10) NOT NULL,
                `quantity` INT(5) NOT NULL,
                `price_at_purchase` DECIMAL(10,2) NOT NULL,
                `selected_size` VARCHAR(50) NOT NULL DEFAULT 'Standard',
                PRIMARY KEY (`id`),
                FOREIGN KEY (`order_id`) REFERENCES `$this->ordersTable`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`product_id`) REFERENCES `$this->tbname`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";


        if (!mysqli_query($conn, $sql_order_items)) {
            $this->errors[] = "Error creating order_items table: " . mysqli_error($conn);
        }
        // 8. ТАБЕЛА 8: Следење на количини по поединечни големини (Stock Inventory)
        $sql_stock = "CREATE TABLE IF NOT EXISTS `product_stock` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `product_id` INT(10) NOT NULL,
                `size_name` VARCHAR(10) NOT NULL, -- На пр: 'M', 'XL', '42', '43', 'Standard'
                `quantity` INT(5) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`product_id`) REFERENCES `$this->tbname`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if (!mysqli_query($conn, $sql_stock)) {
            $this->errors[] = "Error creating stock table: " . mysqli_error($conn);
        }


        return $conn;
    }
}
?>
