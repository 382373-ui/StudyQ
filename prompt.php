<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// --- 1. NEW CHAT / RESTART LOGIC ---
if (isset($_GET['new_trade'])) {
    unset($_SESSION['chat_history']);
    unset($_SESSION['active_subject']);
    header("Location: prompt.php");
    exit;
}

// Fetch live balance from the users table (Ledger Sync)
$stmt = $pdo->prepare("SELECT token_balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_balance = $stmt->fetchColumn() ?: 0.00;

// Initialize chat history and subject
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}
$active_subject = $_SESSION['active_subject'] ?? "";
$error = "";

// --- 2. THE CHAT TRADE EXECUTION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prompt_text'])) {
    
    // STOP TRADE IF LIQUIDITY IS NEGATIVE
    if ($current_balance <= 0) {
        $error = "Insufficient Liquidity: Please initialize more assets to continue trading.";
    } else {
        $user_message = trim($_POST["prompt_text"]);
        
        if (empty($active_subject)) {
            $active_subject = $_POST['subject'] ?? 'General';
            $_SESSION['active_subject'] = $active_subject;
        }

        if (!empty($user_message)) {
            // MARKET VALIDATION: Regex for Blue Chip Verbs
            $rebateValue = 0;
            $blueChipRegex = '/\b(analyze|deconstruct|synthesize|critique|evaluate|benchmark|optimize)\b/i';
            if (preg_match($blueChipRegex, $user_message)) {
                $rebateValue = 2; // Tier 3 Reward
            }

            // --- THE TRADE (Groq API Call) ---
            $apiKey = getenv("API");
            $apiUrl = "https://api.groq.com/openai/v1/chat/completions";

            // SYSTEM PROMPT: Enforcing the Subject Lock
            // Capture new inputs
            $q_type = $_POST['question_type'] ?? 'General Inquiry';
            $output_pref = $_POST['format_pref'] ?? 'Standard';

            $messages = [
                ["role" => "system", "content" => "You are the StudyQ Tutor. 
                Subject: $active_subject. 
                Task Type: $q_type. 
                Output Format: $output_pref. 
                Instructions: Use Markdown. Highlight keywords in **bold**. If the user asks for 'Step-by-step', use numbered lists."]
            ];            foreach ($_SESSION['chat_history'] as $msg) {
                $messages[] = ["role" => ($msg['sender'] == 'user' ? 'user' : 'assistant'), "content" => $msg['text']];
            }
            $messages[] = ["role" => "user", "content" => $user_message];

            $data = ["model" => "llama-3.1-8b-instant", "messages" => $messages, "max_tokens" => 1024];

            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer " . $apiKey]);
            $response = curl_exec($ch);
            $result = json_decode($response, true);
            curl_close($ch);

            if (isset($result["choices"][0]["message"]["content"])) {
                $ai_reply = $result["choices"][0]["message"]["content"];
                
                $_SESSION['chat_history'][] = ["sender" => "user", "text" => $user_message];
                $_SESSION['chat_history'][] = ["sender" => "assistant", "text" => $ai_reply];

                // THE LEDGER: Gas Fee Protocol
                $netCost = 5 - $rebateValue; 
                $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, metadata) VALUES (?, 'GAS_FEE', ?, ?)");
                $meta = json_encode(['subject' => $active_subject, 'rebate' => $rebateValue]);
                $stmt->execute([$_SESSION['user_id'], -$netCost, $meta]);

                header("Location: prompt.php");
                exit;
            } else {
                $error = "Asset Recovery Failed.";
            }
        }
    }
}

include 'header.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>StudyQ | AI Command</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .chat-box { height: 450px; overflow-y: auto; border: 1px solid var(--border-soft); padding: 1.5rem; border-radius: 12px; background: #fff; margin-bottom: 1rem; display: flex; flex-direction: column; gap: 1rem; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
        .msg { max-width: 85%; padding: 1rem; border-radius: 12px; font-size: 0.95rem; }
        .msg.user { align-self: flex-end; background: var(--primary); color: white; border-bottom-right-radius: 2px; }
        .msg.ai { align-self: flex-start; background: #f8fafc; color: #1e293b; border: 1px solid #e2e8f0; border-bottom-left-radius: 2px; }
        .subject-badge { background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="container" style="max-width: 900px; margin-top: 50px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h2 style="margin: 0;">Trading Floor</h2>
            <?php if ($active_subject): ?>
                <span class="subject-badge">Market: <?php echo $active_subject; ?></span>
            <?php endif; ?>
        </div>
        <a href="prompt.php?new_trade=1" style="color: #ef4444; font-weight: 700; font-size: 0.8rem; text-decoration: none; border: 1px solid #fee2e2; padding: 8px 16px; border-radius: 8px;">RESTART CHAT</a>
    </div>

    <div class="chat-box" id="chatbox">
        <?php if (empty($_SESSION['chat_history'])): ?>
            <div style="text-align: center; color: #94a3b8; margin-top: 150px;">
                <p>Select an academic field and execute your first trade.</p>
            </div>
        <?php else: ?>
            <?php foreach ($_SESSION['chat_history'] as $msg): ?>
                <div class="msg <?php echo $msg['sender']; ?>">
                    <?php echo nl2br(htmlspecialchars($msg['text'])); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form method="POST" class="trade-form">
        <?php if (empty($active_subject)): ?>
            <select name="subject" required>
                <option value="Math">Math</option>
                <option value="Science">Science (Bio/Chem/Physics)</option>
                <option value="English">English / Literature</option>
                <option value="History">History / Social Studies</option>
                <option value="Languages">Languages (Spanish, French, etc.)</option>
                <option value="CS">Computer Science / Coding</option>
            </select>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
            <select name="question_type">
                <option value="Definition">Definition / Concept</option>
                <option value="Homework">Homework Problem</option>
                <option value="Essay">Essay / Explanation</option>
                <option value="Quiz">Practice Quiz</option>
            </select>

            <select name="format_pref">
                <option value="Short">Short Answer</option>
                <option value="Detailed">Step-by-Step</option>
                <option value="Examples">Include Examples</option>
                <option value="MultipleChoice">Quiz Format (MCQ)</option>
            </select>
        </div>

        <textarea name="prompt_text" placeholder="Analyze... / Evaluate..." required></textarea>

        <button type="submit">Execute Trade</button>
    </form>

    <?php if (!empty($error)): ?>
        <div class="error" style="margin-top: 1rem; background: #fef2f2; color: #b91c1c; padding: 1rem; border-radius: 8px; border: 1px solid #fecaca;"><?php echo $error; ?></div>
    <?php endif; ?>
</div>

<script>
    const objDiv = document.getElementById("chatbox");
    objDiv.scrollTop = objDiv.scrollHeight;
</script>

</body>
</html>