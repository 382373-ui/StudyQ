<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

try {
    // Enhanced Query: Fetching token_balance for the "Exchange" ledger
    $stmt = $pdo->prepare("
        SELECT u.username, u.token_balance, p.full_name, p.bio, p.avatar 
        FROM users u 
        LEFT JOIN profiles p ON u.id = p.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $full_name = trim($_POST['full_name']);
        $bio = trim($_POST['bio']);
        $avatar = trim($_POST['avatar']);

        // Check if profile exists to determine UPDATE or INSERT
        $checkStmt = $pdo->prepare("SELECT user_id FROM profiles WHERE user_id = ?");
        $checkStmt->execute([$_SESSION['user_id']]);
        
        if ($checkStmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE profiles SET full_name = ?, bio = ?, avatar = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $bio, $avatar, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO profiles (user_id, full_name, bio, avatar) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $full_name, $bio, $avatar]);
        }

        header("Location: profile.php");
        exit;
    }
} catch (PDOException $e) {
    die("Ledger Access Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyQ | Profile Command</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <?php include 'header.php'; ?>
</head>
<body>
    

<div class="container">
    <div class="profile-header">
        <img src="<?php echo htmlspecialchars($user_data['avatar'] ?: 'https://via.placeholder.com/80'); ?>" alt="Avatar" class="avatar-circle">
        <div>
            <h1><?php echo htmlspecialchars($user_data['username']); ?></h1>
            <div class="balance-badge">
                Liquidity: <?php echo number_format($user_data['token_balance'], 2); ?> Tokens
            </div>
        </div>
    </div>

    <form method="POST">
        <label style="font-size: 0.8rem; font-weight: 600; color: #64748b;">FULL NAME</label>
        <input type="text" name="full_name" placeholder="Entity Name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>">
        
        <label style="font-size: 0.8rem; font-weight: 600; color: #64748b;">BIOGRAPHICAL DATA</label>
        <textarea name="bio" placeholder="Analyze your objectives..."><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
        
        <label style="font-size: 0.8rem; font-weight: 600; color: #64748b;">AVATAR ENDPOINT (URL)</label>
        <input type="text" name="avatar" placeholder="https://image.url" value="<?php echo htmlspecialchars($user_data['avatar'] ?? ''); ?>">
        
        <button type="submit">Update Ledger</button>
    </form>

    <div class="nav-links">
        <a href="index.php" style="margin: 0;">← Back to Home</a>
    </div>
</div>

</body>
</html>