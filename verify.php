<?php
require_once "includes/autoloader.php";
$dbClass = new Database();
$conn = $dbClass->connect();

if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    
    $query = "SELECT * FROM `users` WHERE `verification_token` = '$token' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $update = "UPDATE `users` SET `is_verified` = 1, `verification_token` = NULL WHERE `verification_token` = '$token'";
        mysqli_query($conn, $update);
        echo "<script>alert('Account successfully verified! You can now log in.'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Invalid or expired verification token.'); window.location.href='index.php';</script>";
    }
}
exit();
?>
