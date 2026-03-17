<?php
// Ensure session is started for all pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

$current_balance = 0.00;

// Fetch live balance if user is logged in
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT token_balance FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_balance = $stmt->fetchColumn() ?: 0.00;
}
?>
<header class="main-header">
    <div class="header-content">
        <div class="logo">
            <a href="index.php">StudyQ <span class="version">AI</span></a>
        </div>

        <nav class="nav-links-global">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php">Profile</a>
                <div class="nav-balance">
                    <span class="label">Liquidity:</span>
                    <span class="value"><?php echo number_format($current_balance, 2); ?></span>
                </div>
                <a href="logout.php" class="logout-btn">Log Out</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="cta-btn">Initialize IPO</a>
            <?php endif; ?>
        </nav>
    </div>
</header>