<?php
// test_groq_api.php

// Retrieve the API key from environment variables
$apiKey = getenv('API');
$apiUrl = 'https://api.groq.com/openai/v1/chat/completions'; // Groq chat completions endpoint

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prompt = $_POST['prompt'] ?? '';

    if (!empty($prompt)) {
        // Prepare the data for the API request (Groq chat completions format)
        $data = [
            'model' => "llama-3.1-8b-instant", // Updated to a supported model
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 1024,
        ];

        // Initialize cURL session
        $ch = curl_init($apiUrl);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ]);

        // Execute the request
        $response = curl_exec($ch);

        // Close cURL session
        curl_close($ch);

        // Decode the response
        $result = json_decode($response, true);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groq API Test</title>
</head>
<body>
    <h1>Test Groq API Connection</h1>
    <form method="post">
        <label for="prompt">Enter your prompt:</label><br>
        <textarea id="prompt" name="prompt" rows="4" cols="50" required></textarea><br>
        <button type="submit">Get Answer</button>
    </form>

    <?php if (isset($result) && !empty($result)): ?>
        <h2>API Response:</h2>
        <pre><?php
            if (isset($result['choices'][0]['message']['content'])) {
                echo htmlspecialchars($result['choices'][0]['message']['content']);
            } else {
                print_r($result);
            }
        ?></pre>
    <?php endif; ?>
</body>
</html>
