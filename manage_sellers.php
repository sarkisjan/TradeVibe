<?php
session_start();
require_once "includes/autoloader.php";

// Безбедносна кочница: Само Root корисникот има пристап до овој панел
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'root') {
    header("Location: login.php");
    exit();
}

$dbClass = new Database();
$conn = $dbClass->connect();

// 1. НОВА ОПЦИЈА: ИНСТАНТНА ВЕРИФИКАЦИЈА ОД СТРАНА НА ROOT КОРИСНИКОТ
if (isset($_POST['approve_verify_user'])) {
    $uId = intval($_POST['user_id']);
    mysqli_query($conn, "UPDATE `users` SET `is_verified` = 1 WHERE `id` = $uId");
    echo "<script>alert('Account successfully verified and activated within the ecosystem matrix!'); window.location.href='manage_sellers.php';</script>";
    exit();
}

// 2. БРИШЕЊЕ НА ПРОФИЛ (Продавач или Купувач)
if (isset($_POST['delete_user'])) {
    $uId = intval($_POST['user_id']);
    mysqli_query($conn, "DELETE FROM `producttable` WHERE `admin_id` = $uId");
    mysqli_query($conn, "DELETE FROM `users` WHERE `id` = $uId");
    header("Location: manage_sellers.php");
    exit();
}

// 3. РЕСЕТИРАЊЕ НА ЛОЗИНКА
if (isset($_POST['reset_pwd'])) {
    $uId = intval($_POST['user_id']);
    $raw_custom_password = isset($_POST['custom_password']) ? trim($_POST['custom_password']) : '';
    
    if ($raw_custom_password === '') {
        echo "<script>alert('Please type a password inside the input field layouts first!'); window.location.href='manage_sellers.php';</script>";
        exit();
    }
    
    $customHashed = password_hash($raw_custom_password, PASSWORD_DEFAULT);
    mysqli_query($conn, "UPDATE `users` SET `password` = '$customHashed' WHERE `id` = $uId");
    echo "<script>alert('Password successfully changed and updated for this profile!'); window.location.href='manage_sellers.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global User Control Matrix - TradeVibe</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body id="bckgrnd">

    <header>
        <div class="navbar">
            <div class="brand-logo-zone" onclick="window.location.href='index.php';">
                <svg class="brand-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="16 3 21 3 21 8"></polyline>
                    <line x1="4" y1="20" x2="21" y2="4"></line>
                    <polyline points="21 16 21 21 16 21"></polyline>
                    <line x1="15" y1="15" x2="21" y2="21"></line>
                    <line x1="4" y1="4" x2="9" y2="9"></line>
                </svg>
                <span class="brand-name-text">Trade<span class="brand-accent-text">Vibe</span><span style="font-size:14px; color:#718096; font-weight:500; margin-left:10px;">(Root Control Console)</span></span>
            </div>
            <ul class="btn-list">
                <li><a href="index.php" class="btn btn-secondary">STOREFRONT</a></li>
                <li><a href="logout.php" class="btn btn-danger">LOG OUT</a></li>
            </ul>
        </div>
    </header>

    <div class="matrix-container">
        <div class="matrix-card">
            <h2>Global Registered Users Matrix</h2>
            <p>Managing system sellers, customers, and active account verification status logs. Deleting a seller account will automatically cascade and delete all products they have published inside the storefront inventory.</p>
            
            <table class="matrix-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Account Type</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Verification Status</th>
                        <th>Manual Approval</th>
                        <th>Reset Password</th>
                        <th>Danger Zone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Селектираме апсолутно СИТЕ корисници (и продавачи и купувачи) за рачна верификација од Root
                    $query = "SELECT * FROM `users` WHERE `role` != 'root' ORDER BY `id` ASC";
                    $res = mysqli_query($conn, $query);
                    
                    if (mysqli_num_rows($res) === 0):
                        echo "<tr><td colspan='9' style='color:gray;'>No registered users found inside the system infrastructure network.</td></tr>";
                    else:
                        while ($user = mysqli_fetch_assoc($res)):
                            $badgeClass = ($user['role'] === 'admin') ? 'role-seller' : 'role-customer';
                            $roleLabel = ($user['role'] === 'admin') ? 'Seller' : 'Customer';
                            
                            $statusClass = (intval($user['is_verified']) === 1) ? 'status-active' : 'status-pending';
                            $statusLabel = (intval($user['is_verified']) === 1) ? 'Verified' : 'Unverified';
                    ?>
                        <tr>
                            <td><strong>#<?php echo $user['id']; ?></strong></td>
                            <td><span class="role-badge <?php echo $badgeClass; ?>"><?php echo $roleLabel; ?></span></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></td>
                            <td style="color:#4a5568;"><?php echo htmlspecialchars($user['email']); ?></td>
                            
                            <!-- СТАТУС НА ВЕРИФИКАЦИЈА -->
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span></td>
                            
                            <!-- КОПЧЕ ЗА МАНУЕЛНА ВЕРИФИКАЦИЈА ОД СТРАНА НА ROOT -->
                            <td>
                                <?php if (intval($user['is_verified']) !== 1): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to manually verify and activate this account?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="approve_verify_user" class="matrix-btn btn-verify-action">✔ Verify Account</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color:#a0aec0; font-size:12px; font-style:italic;">Already Active</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- РЕСЕТИРАЊЕ ЛОЗИНКА -->
                            <td>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to change this profile\'s password to the value specified inside the input box?');" style="display: flex; gap: 5px; justify-content: center; align-items: center;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="text" name="custom_password" placeholder="Type new password" required style="width: 120px; padding: 6px 10px; border: 1px solid #cbd5e0; border-radius: 6px; font-size: 12px; box-sizing: border-box; outline: none;">
                                    <button type="submit" name="reset_pwd" class="matrix-btn btn-reset" style="padding: 6px 10px; white-space: nowrap;">Change</button>
                                </form>
                            </td>
                            
                            
                            <!-- БРИШЕЊЕ ПРОФИЛ -->
                            <td>
                                <form method="POST" onsubmit="return confirm('CRITICAL WARNING: Are you sure you want to completely delete this seller account? All their products will be wiped out!');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="matrix-btn btn-delete">Delete Account</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="site-footer">
        <div class="footer-navbar">
            <span class="footer-title">TradeVibe Infrastructure Ecosystem Matrix Control Logs</span>
            <p class="footer-author">by Blagoja Sarkisjan</p>
        </div>
    </footer>

</body>
</html>
