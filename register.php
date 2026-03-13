<?php
session_start();
require 'db.php'; // Include your database connection

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        // Prepare the insertion for the new entity
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);
        
        // Success: The 'after_user_signup' Trigger in MySQL will now handle the 50.00 token IPO
        $_SESSION['message'] = "IPO Complete. 50 Tokens credited to your ledger.";
        header("Location: login.php");
        exit;
    } catch (PDOException $e) {
        // Handle duplicate emails or ledger errors gracefully
        if ($e->getCode() == 23000) {
            $error_message = "Entity already exists. Please login to the exchange.";
        } else {
            $error_message = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyQ | Create Entity</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="container">
    <h1>New Entity Signup</h1>

    <?php if ($error_message): ?>
        <div class="error">
            <strong>Validation Error:</strong> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <input type="text" name="username" placeholder="Username (Entity Name)" required>
        </div>

        <div class="input-group">
            <input type="email" name="email" placeholder="Institutional Email" required>
        </div>
        
        <div class="input-group">
            <input type="password" name="password" placeholder="Set Access Key" required>
        </div>

        <button type="submit">Initialize IPO</button>
    </form>

    <a href="login.php">Already registered? Return to Floor</a>
</div>

</body>
</html>