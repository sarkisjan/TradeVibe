<?php
session_start();
require_once "includes/autoloader.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

$dbClass = new Database();
$conn = $dbClass->connect();

$error = "";
$success = "";

if (!$conn) {
    die("Database setup link breakdown: Class instantiate error.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Преземање на влезните вредности
    $raw_username   = isset($_POST['username'])   ? trim($_POST['username'])   : '';
    $raw_first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $raw_last_name  = isset($_POST['last_name'])  ? trim($_POST['last_name'])  : '';
    $raw_email      = isset($_POST['email'])      ? $_POST['email']            : '';
    $raw_password   = isset($_POST['password'])   ? $_POST['password']         : '';
    $raw_address    = isset($_POST['address'])    ? trim($_POST['address'])    : '';
    $raw_role       = isset($_POST['role'])       ? $_POST['role']             : ''; 

    // 1. Валидација за празни места: Проверка дали е-мејлот или лозинката содржат празно место (space)
    if (preg_match('/\s/', $raw_email)) {
        $error = "Email address cannot contain spaces!";
    } 
    // 2. Валидација за празни места во лозинката
    elseif (preg_match('/\s/', $raw_password)) {
        $error = "Password cannot contain spaces!";
    }
    // 3. Стандардна проверка за празни полиња
    elseif (empty($raw_username) || empty($raw_first_name) || empty($raw_last_name) || empty($raw_email) || empty($raw_password) || empty($raw_address) || empty($raw_role)) {
        $error = "Please fill out all the fields!";
    } 
    elseif (!in_array($raw_role, ['admin', 'user'])) {
        $error = "Invalid account role selected!";
    } 
    else {
        // Чистење на е-мејлот за секој случај
        $clean_email = str_replace(' ', '', $raw_email);
        
        // Санитација на променливите за база
        $username   = mysqli_real_escape_string($conn, $raw_username);
        $first_name = mysqli_real_escape_string($conn, $raw_first_name);
        $last_name  = mysqli_real_escape_string($conn, $raw_last_name);
        $email      = mysqli_real_escape_string($conn, $clean_email);
        $address    = mysqli_real_escape_string($conn, $raw_address);
        $role       = mysqli_real_escape_string($conn, $raw_role);

        // Проверка за дупликат е-мејл
        $checkEmail = mysqli_query($conn, "SELECT `id` FROM `users` WHERE `email` = '$email'");
        
        if ($checkEmail && mysqli_num_rows($checkEmail) > 0) {
            $error = "An account with this email address is already registered!";
        } else {
            // Безбедно хеширање на лозинката
            $hashedPassword = password_hash($raw_password, PASSWORD_DEFAULT);

            // 1. ГЕНЕРИРАЊЕ НА УНИКАТЕН БЕЗБЕДНОСЕН ТОКЕН ЗА ВЕРИФИКАЦИЈА
            $token = bin2hex(random_bytes(32));

            // 2. ПОПРАВЕНО: Го зачувуваме токенот, а 'is_verified' го ставаме на 0 (неактивен)
            $query = "INSERT INTO `users` (`username`, `first_name`, `last_name`, `email`, `password`, `address`, `role`, `verification_token`, `is_verified`) 
                      VALUES ('$username', '$first_name', '$last_name', '$email', '$hashedPassword', '$address', '$role', '$token', 0)";

            if (mysqli_query($conn, $query)) {
                
                // 3. ИСПРАЌАЊЕ НА ОФИЦИЈАЛЕН ВЕРИФИКАЦИСКИ Е-МЕЈЛ ДО КОРИСНИКОТ
                $to = $email;
                $subject = "Verify Your Account - TradeVibe Market";
                $verification_link = "http://localhost/TradeVibe/verify.php?token=" . $token;

                // ЧИСТ И ОДДЕЛЕН CSS СТИЛСКИ СИСТЕМ ЗА Е-МЕЈЛ ПОРАКАТА
                $email_css = "
                    .email-body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #1a202c; padding: 30px; line-height: 1.6; }
                    .email-heading { color: #1a202c; font-weight: 800; font-size: 22px; letter-spacing: -0.5px; margin-top: 0; }
                    .email-text { font-size: 15px; color: #4a5568; margin-bottom: 25px; }
                    .verify-btn { display: inline-block; padding: 12px 26px; background-color: #3498db; color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2); }
                    .email-footer { font-size: 12px; color: #718096; border-top: 1px solid #edf2f7; padding-top: 15px; margin-top: 35px; }
                ";

                // КРИСТАЛНО ЧИСТ HTML СТРИНГ БЕЗ ИНЛАЈН СТИЛОВИ ВО ТАГОВИТЕ
                $message = "
                <html>
                <head>
                    <title>Verify Your Account</title>
                    <meta charset='UTF-8'>
                    <style>{$email_css}</style>
                </head>
                <body class='email-body'>
                    <h2 class='email-heading'>Welcome to TradeVibe Market, {$first_name}!</h2>
                    <p class='email-text'>Thank you for registering. To activate your secure marketplace account and enable immediate purchasing configurations, please click the secure button below to verify your identity within our ecosystem:</p>
                    
                    <a href='{$verification_link}' class='verify-btn'>VERIFY MY ACCOUNT</a>
                    
                    <p class='email-footer'>If you did not initiate this registration sequence, you can safely ignore and discard this transactional email footprint.</p>
                </body>
                </html>";

                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: no-reply@tradevibe-market.com" . "\r\n";

                // БЕЗБЕДНОСЕН ЛОКАЛЕН ГАРД ОД SMTP ГРЕШКИ
                $buyer_email_check = strtolower(trim($email));
                if (str_contains($buyer_email_check, '@eshop.com')) {
                    $success = "Registration successful! This is a test user with a fictional email address, verification link has been bypassed. Please manually activate this user inside your phpMyAdmin dashboard matrix by setting is_verified to 1.";
                } else {
                    @mail($to, $subject, $message, $headers);
                    $success = "Registration successful! A verification link has been successfully processed. Please verify your account before logging in.";
                }

            } else {
                $error = "Database registration error: " . mysqli_error($conn);
            }


        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create an Account - TradeVibe</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body id="bckgrnd">
    <div class="register-box">
        <h2>Create an Account</h2>
        
        <?php if(!empty($error)): ?>
            <p class="status-msg text-danger"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <p class="status-msg text-success"><?php echo $success; ?></p>
        <?php endif; ?>


        <form action="signup.php" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="address" placeholder="Residential Address" required>
            
            <select name="role" required>
                <option value="">-- Select Account Type --</option>
                <option value="user">Customer (Buyer)</option>
                <option value="admin">Seller (Administrator)</option>
            </select>

            <input type="submit" class="register-btn" value="REGISTER">
        </form>
        
        <p style="margin-top:20px; font-size:0.9em;">
            Already have an account? <a href="login.php" style="color:#3498db; font-weight:bold; text-decoration: none;">Sign in here</a>
        </p>
    </div>
</body>
</html>
