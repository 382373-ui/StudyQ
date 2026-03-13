<?php
session_start();
require 'db.php';

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: profile.php");
            exit;
        } else {
            // Updated to set a variable instead of die()
            $error_message = "Invalid credentials. Slippage detected.";
        }
    } catch (PDOException $e) {
        $error_message = "Connection failed. Please check the ledger.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyQ | Secure Login</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="container">
    <h1>StudyQ Login</h1>

    <?php if ($error_message): ?>
        <div class="error">
            <strong>Trade Error:</strong> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <input type="email" name="email" placeholder="Institutional Email" required>
        </div>
        
        <div class="input-group">
            <input type="password" name="password" placeholder="Access Key" required>
        </div>

        <button type="submit">Execute Login</button>
    </form>

    <a href="register.php">New Entity? Create Account</a>
</div>

</body>
</html>