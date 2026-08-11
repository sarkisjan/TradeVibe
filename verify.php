<?php
// Load classes automatically
require_once "includes/autoloader.php";

// Connect to the database
$dbClass = new Database();
$conn = $dbClass->connect();

// Check if the verification token is present in the URL
if (isset($_GET['token'])) {
     // Sanitize the token to prevent SQL injection
    $token = mysqli_real_escape_string($conn, $_GET['token']);
       
    // Search for the user with this specific token
    $query = "SELECT * FROM `users` WHERE `verification_token` = '$token' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    // If the user is found in the database
    if (mysqli_num_rows($result) > 0) {
        // Activate the account and clear the temporary token
        $update = "UPDATE `users` SET `is_verified` = 1, `verification_token` = NULL WHERE `verification_token` = '$token'";
        mysqli_query($conn, $update);
        // Show success alert message and redirect to login page
        echo "<script>alert('Account successfully verified! You can now log in.'); window.location.href='login.php';</script>";
    } else {
        // Show error alert message if the token is wrong or expired
        echo "<script>alert('Invalid or expired verification token.'); window.location.href='index.php';</script>";
    }
}
exit();
?>
