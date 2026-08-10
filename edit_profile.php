<?php
session_start();
require_once "includes/autoloader.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$dbClass = new Database();
$conn = $dbClass->connect();

$error = "";
$success = "";
$pass_error = "";
$pass_success = "";

$userId = $_SESSION['user_id'];

// Повлечи ги најсвежите податоци од базата за рендерирање во формите
$query = "SELECT * FROM `users` WHERE `id` = $userId";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ==========================================================================
    // ФОРМА 1: АЖУРИРАЊЕ НА ЛИЧНИ ПОДАТОЦИ + АДРЕСА НА ДОСТАВА
    // ==========================================================================
    if (isset($_POST['action']) && $_POST['action'] === 'update_info') {
        $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
        $last_name  = mysqli_real_escape_string($conn, trim($_POST['last_name']));
        $phone      = mysqli_real_escape_string($conn, trim($_POST['phone']));
        $address    = mysqli_real_escape_string($conn, trim($_POST['address'])); // НОВАТА АДРЕСА

        if (empty($first_name) || empty($last_name) || empty($address)) {
            $error = "First Name, Last Name, and Delivery Address fields cannot be empty!";
        } else {
            // Го додаваме address во UPDATE упитот
            $updateQuery = "UPDATE `users` SET 
                            `first_name` = '$first_name', 
                            `last_name` = '$last_name', 
                            `phone` = '$phone',
                            `address` = '$address' 
                            WHERE `id` = $userId";

            if (mysqli_query($conn, $updateQuery)) {
                // КРИТИЧЕН ФИКС: Веднаш ја ажурираме сесијата за да се смени и на почетната страна
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name']  = $last_name;
                $_SESSION['phone']      = $phone;
                $_SESSION['address']    = $address; 
                
                $success = "Profile and Delivery details updated successfully!";
                
                $user['first_name'] = $first_name;
                $user['last_name']  = $last_name;
                $user['phone']      = $phone;
                $user['address']    = $address;
            } else {
                $error = "Error updating database record: " . mysqli_error($conn);
            }
        }
    }

    // ==========================================================================
    // ФОРМА 2: БЕЗБЕДНА ПРОМЕНА НА ЛОЗИНКА (НОВО)
    // ==========================================================================
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        // Автоматски ги отстрануваме СИТЕ празни места (spaces) од лозинките како што побара
        $old_pass     = str_replace(' ', '', $_POST['old_password']);
        $new_pass     = str_replace(' ', '', $_POST['new_password']);
        $confirm_pass = str_replace(' ', '', $_POST['confirm_password']);

        // 1. Проверка дали полињата се празни
        if (empty($old_pass) || empty($new_pass) || empty($confirm_pass)) {
            $pass_error = "All password fields are required!";
        } 
        // 2. Проверка дали новата лозинка и потврдата се совпаѓаат
        elseif ($new_pass !== $confirm_pass) {
            $pass_error = "The new password and confirmation password do not match!";
        } 
        // 3. Проверка дали новата лозинка е идентична со старата (опционално, за подобра безбедност)
        elseif ($old_pass === $new_pass) {
            $pass_error = "New password cannot be the same as your current password!";
        }
        else {
            // 4. ВЕРИФИКАЦИЈА: Проверка дали старата лозинка е точна
            if (password_verify($old_pass, $user['password'])) {
                
                // Сè е во ред, безбедно ја хешираме новата лозинка
                $new_hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
                
                $updatePassQuery = "UPDATE `users` SET `password` = '$new_hashed_password' WHERE `id` = $userId";
                
                if (mysqli_query($conn, $updatePassQuery)) {
                    $pass_success = "Password changed successfully! Use your new password next time you log in.";
                } else {
                    $pass_error = "Database error updating password: " . mysqli_error($conn);
                }
            } else {
                $pass_error = "Your current (old) password is incorrect!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile & Security - E-Shop</title>
    <link rel="stylesheet" href="styles.css">

</head>
<body id="bckgrnd">
    <div class="edit-box">
        <h2>Account Settings</h2>
        
        <!-- ==========================================================================
             ХЕНДЛИНГ НА ФОРМА 1: ЛИЧНИ ПОДАТОЦИ
             ========================================================================== -->
        <div class="section-title">Personal Information</div>
        
        <?php if(!empty($error)): ?>
            <p style="color:red; margin-bottom:10px; font-weight:bold; font-size:14px; text-align:left;"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if(!empty($success)): ?>
            <p style="color:green; margin-bottom:10px; font-weight:bold; font-size:14px; text-align:left;"><?php echo $success; ?></p>
        <?php endif; ?>

<form action="edit_profile.php" method="POST">
    <input type="hidden" name="action" value="update_info">
    
    <label class="form-label">First Name</label>
    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
    
    <label class="form-label">Last Name</label>
    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
    
    <label class="form-label">Email Address (Readonly)</label>
    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly style="background-color: #f1f3f5; cursor: not-allowed; color: #a0aec0;">
    
    <label class="form-label">Phone Number</label>
    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="Enter phone number">

    <!-- КРИТИЧЕН ФИКС: ОВА ПОЛЕ МОРА ДА СТОИ ТУКА ЗА ДА СЕ ПОЈАВИ НА ЕКРАНОТ -->
    <label class="form-label">Delivery / Shipping Address</label>
    <input type="text" name="address" value="<?php echo isset($user['address']) ? htmlspecialchars($user['address']) : ''; ?>" placeholder="Enter your full street address for shipping" required>

    <input type="submit" class="save-profile-btn" value="SAVE CHANGES">
</form>

        
        <!-- ==========================================================================
             ХЕНДЛИНГ НА ФОРМА 2: ПРОМЕНА НА ЛОЗИНКА (НОВО)
             ========================================================================== -->
        <div class="section-title" style="margin-top: 40px;">Security & Password</div>
        
        <?php if(!empty($pass_error)): ?>
            <p style="color:red; margin-bottom:10px; font-weight:bold; font-size:14px; text-align:left;"><?php echo $pass_error; ?></p>
        <?php endif; ?>
        <?php if(!empty($pass_success)): ?>
            <p style="color:green; margin-bottom:10px; font-weight:bold; font-size:14px; text-align:left;"><?php echo $pass_success; ?></p>
        <?php endif; ?>

        <form action="edit_profile.php" method="POST">
            <input type="hidden" name="action" value="change_password">
            
            <label class="form-label">Current Password</label>
            <input type="password" name="old_password" placeholder="Enter current password" required>
            
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" placeholder="Enter new password" required>
            
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Repeat new password" required>

            <input type="submit" class="change-pass-btn" value="UPDATE PASSWORD">
        </form>
        
        <p style="margin-top:30px; font-size:0.9em;">
            <a href="index.php" style="color:#3498db; font-weight:bold; text-decoration: none;">← Back to Store</a>
        </p>
    </div>
</body>
</html>
