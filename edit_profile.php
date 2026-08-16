<?php
session_start();
require_once "includes/autoloader.php";

// Redirect users who are not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Connect to the database
$dbClass = new Database();
$conn = $dbClass->connect();

$error = "";
$success = "";
$pass_error = "";
$pass_success = "";

$userId = $_SESSION['user_id'];

// Get the latest user data from the database
$query = "SELECT * FROM `users` WHERE `id` = $userId";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle personal information and delivery address update
    if (isset($_POST['action']) && $_POST['action'] === 'update_info') {
        $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
        $last_name  = mysqli_real_escape_string($conn, trim($_POST['last_name']));
        $phone      = mysqli_real_escape_string($conn, trim($_POST['phone']));
        $address    = mysqli_real_escape_string($conn, trim($_POST['address'])); // НОВАТА АДРЕСА

        // Check that required fields are not empty
        if (empty($first_name) || empty($last_name) || empty($address)) {
            $error = "First Name, Last Name, and Delivery Address fields cannot be empty!";
        } else {
            // Update the user's personal information
            $updateQuery = "UPDATE `users` SET 
                            `first_name` = '$first_name', 
                            `last_name` = '$last_name', 
                            `phone` = '$phone',
                            `address` = '$address' 
                            WHERE `id` = $userId";

            if (mysqli_query($conn, $updateQuery)) {
                // Update the session so the new information is available immediately
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name']  = $last_name;
                $_SESSION['phone']      = $phone;
                $_SESSION['address']    = $address;

                $success = "Profile and Delivery details updated successfully!";

                // Update the displayed user data without another database query
                $user['first_name'] = $first_name;
                $user['last_name']  = $last_name;
                $user['phone']      = $phone;
                $user['address']    = $address;
            } else {
                $error = "Error updating database record: " . mysqli_error($conn);
            }
        }
    }

    // Handle password change
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        // Remove spaces from the entered passwords
        $old_pass     = str_replace(' ', '', $_POST['old_password']);
        $new_pass     = str_replace(' ', '', $_POST['new_password']);
        $confirm_pass = str_replace(' ', '', $_POST['confirm_password']);

        // Check that all password fields are filled in
        if (empty($old_pass) || empty($new_pass) || empty($confirm_pass)) {
            $pass_error = "All password fields are required!";
        }
        // Check that the new passwords match
        elseif ($new_pass !== $confirm_pass) {
            $pass_error = "The new password and confirmation password do not match!";
        }
        // Prevent using the same password
        elseif ($old_pass === $new_pass) {
            $pass_error = "New password cannot be the same as your current password!";
        } else {
            // Verify the current password
            if (password_verify($old_pass, $user['password'])) {

                // Hash the new password before saving it
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
    <title>Edit Profile & Security - TradeVibe</title>
    <link rel="icon" type="image" href="uploads/favicon.ico">
    <link rel="stylesheet" href="styles.css">


</head>

<body id="bckgrnd">
    <div class="edit-box">
        <h2>Account Settings</h2>

        <!-- Personal information section -->
        <div class="section-title">Personal Information</div>

        <?php if (!empty($error)): ?>
            <p style="color:red; margin-bottom:10px; font-weight:bold; font-size:14px; text-align:left;"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <p style="color:green; margin-bottom:10px; font-weight:bold; font-size:14px; text-align:left;"><?php echo $success; ?></p>
        <?php endif; ?>

        <!-- Form for updating personal information -->
        <form action="edit_profile.php" method="POST">
            <input type="hidden" name="action" value="update_info">

            <label class="form-label">First Name</label>
            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>

            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>

            <!-- Email cannot be changed from this form -->
            <label class="form-label">Email Address (Readonly)</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly style="background-color: #f1f3f5; cursor: not-allowed; color: #a0aec0;">

            <label class="form-label">Phone Number</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="Enter phone number">

            <label class="form-label">Delivery / Shipping Address</label>
            <input type="text" name="address" value="<?php echo isset($user['address']) ? htmlspecialchars($user['address']) : ''; ?>" placeholder="Enter your full street address for shipping" required>

            <input type="submit" class="save-profile-btn" value="SAVE CHANGES">
        </form>


        <!-- Password and security section -->
        <div class="section-title" style="margin-top: 40px;">Security & Password</div>

        <?php if (!empty($pass_error)): ?>
            <p style="color:red; margin-bottom:10px; font-weight:bold; font-size:14px; text-align:left;"><?php echo $pass_error; ?></p>
        <?php endif; ?>
        <?php if (!empty($pass_success)): ?>
            <p style="color:green; margin-bottom:10px; font-weight:bold; font-size:14px; text-align:left;"><?php echo $pass_success; ?></p>
        <?php endif; ?>

        <!-- Form for changing the password -->
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

        <!-- Link back to the main store -->
        <p style="margin-top:30px; font-size:0.9em;">
            <a href="index.php" style="color:#3498db; font-weight:bold; text-decoration: none;">← Back to Store</a>
        </p>
    </div>
</body>

</html>