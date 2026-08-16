<?php
session_start();
require_once "includes/autoloader.php";

// Hide PHP errors from users while still reporting them internally
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Connect to the database
$dbClass = new Database();
$conn = $dbClass->connect();

// Store any login error message
$error = "";

// Stop the script if the database connection fails
if (!$conn) {
    die("Database setup link breakdown: Connection error.");
}

// Handle the login form when it is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get the email and password entered by the user
    $raw_email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $raw_password = isset($_POST['password']) ? $_POST['password'] : '';

    // Make sure both fields have been filled in
    if (empty($raw_email) || empty($raw_password)) {
        $error = "Please fill out all the fields!";
    } else {
        // Escape the email before using it in the SQL query
        $email = mysqli_real_escape_string($conn, $raw_email);

        // Find the user by email, even if the account is not verified yet
        $query = "SELECT * FROM `users` WHERE `email` = '$email' LIMIT 1";
        $result = mysqli_query($conn, $query);

        // Check if an account was found
        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

            // Check if the entered password matches the stored password
            if (password_verify($raw_password, $user['password'])) {

                // Check if the account has been verified
                if (intval($user['is_verified']) !== 1) {
                    $error = "Your account has been registered successfully, but it is currently unverified! 
                              <br><br>
                              ⚠️ <strong>Local Environment Restriction Notice:</strong> 
                              Because this application is operating within a local sandbox environment (XAMPP Server Layout), 
                              realtime outbound SMTP email transmission is simulated and restricted. 
                              <br><br>
                              <strong>How to proceed:</strong>
                              <br>
                              1. You can instantly test the system using any of the pre-vetted <strong>Demo Environment Test Accounts</strong> listed above.
                              <br>
                              2. If you strictly wish to test with your newly created personal profile, please contact the administrator (Blagoja Sarkisjan) to manually approve and verify your account status within the database control layers.";
                } else {
                    // Save the user's information in the session after successful login
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['phone'] = $user['phone'];
                    $_SESSION['address'] = $user['address'];

                    // Redirect the user to the main page
                    header("Location: index.php");
                    exit();
                }
            } else {
                // Show an error when the password is incorrect
                $error = "Invalid password entered! Please try again.";
            }
        } else {
            // Show an error when no account exists with the entered email
            $error = "No registered account found with this email address!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - TradeVibe</title>
    <link rel="stylesheet" href="styles.css">

</head>

<body id="bckgrnd">

    <div class="login-box">
        <h2>Sign In</h2>
        <!-- Show demo accounts that can be used for testing -->
        <div class="test-credentials-panel">
            <h4>🔐 Demo Environment Test Accounts</h4>

            <div class="user-type-group"><span class="badge-role role-admin">Seller 1</span><strong>Email:</strong> admin@eshop.com | <strong>Pass:</strong> admin123</div>
            <div class="user-type-group"><span class="badge-role role-admin">Seller 2</span><strong>Email:</strong> seller2@eshop.com | <strong>Pass:</strong> admin123</div>
            <div class="user-type-group"><span class="badge-role role-user">Customer 1</span><strong>Email:</strong> user@eshop.com | <strong>Pass:</strong> user123</div>
            <div class="user-type-group"><span class="badge-role role-user">Customer 2</span><strong>Email:</strong> buyer2@eshop.com | <strong>Pass:</strong> user123</div>
        </div>

        <?php if (!empty($error)): ?>
            <!-- Display the login error if one exists -->
            <div class="error-banner">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="email" name="email" autocomplete="username" placeholder="Enter your email address" required>
            <input type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
            <input type="submit" class="login-btn" value="SIGN IN">
        </form>

        <p style="margin-top: 25px; font-size: 13px; color: #718096;">
            New to our shop? <a href="signup.php" style="color: #3498db; font-weight: bold; text-decoration: none;">Create an account here</a>
        </p>
    </div>

</body>

</html>