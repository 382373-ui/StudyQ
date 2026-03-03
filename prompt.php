<?php
session_start();

/*
|--------------------------------------------------------------------------
| 1. Check if form was submitted
|--------------------------------------------------------------------------
*/
$responseText = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get user input safely
    $prompt = trim($_POST["prompt_text"]);

    if (!empty($prompt)) {

        // Your existing API connection settings
        $apiKey = getenv("API");
        $apiUrl = "https://api.groq.com/openai/v1/chat/completions";

        // Prepare request data (same structure as your test file)
        $data = [
            "model" => "llama-3.1-8b-instant",
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ],
            "max_tokens" => 1024
        ];

        // Initialize cURL
        $ch = curl_init($apiUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apiKey,
        ]);

        // Execute request
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = "API Request Error: " . curl_error($ch);
        }

        curl_close($ch);

        // Decode JSON response
        $result = json_decode($response, true);

        // Extract AI message safely
        if (isset($result["choices"][0]["message"]["content"])) {
            $responseText = $result["choices"][0]["message"]["content"];
        } else {
            $error = "Invalid response from API.";
        }
    } else {
        $error = "Please enter a prompt.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ask StudyQ AI</title>
    <style>
        body {
            font-family: Arial;
            max-width: 700px;
            margin: 40px auto;
        }
        textarea {
            width: 100%;
            height: 120px;
        }
        button {
            padding: 10px 15px;
            margin-top: 10px;
        }
        .response {
            margin-top: 20px;
            padding: 15px;
            background: #f2f2f2;
        }
        .error {
            color: red;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<h2>Ask the AI</h2>

<form method="POST">
    <textarea name="prompt_text" placeholder="Type your question here..." required></textarea>
    <br>
    <button type="submit">Submit</button>
</form>

<?php if (!empty($responseText)): ?>
    <div class="response">
        <strong>AI Response:</strong>
        <p><?php echo htmlspecialchars($responseText); ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="error">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

</body>
</html>