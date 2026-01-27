<?php

/**
 * Test OpenAI API Directly
 * Tests if the OpenAI API key works
 */

if (!isset($argv[1])) {
    echo "Usage: php test-openai-api-direct.php YOUR_OPENAI_API_KEY\n";
    echo "Example: php test-openai-api-direct.php sk-proj-...\n";
    exit(1);
}

$apiKey = $argv[1];
$apiUrl = 'https://api.openai.com/v1/chat/completions';

echo "========================================\n";
echo "OPENAI API DIRECT TEST\n";
echo "========================================\n\n";

echo "Testing API Key: " . substr($apiKey, 0, 15) . "...\n";
echo "API URL: {$apiUrl}\n\n";

$ch = curl_init($apiUrl);

$data = [
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role' => 'user', 'content' => 'Say hello in one word']
    ],
    'max_tokens' => 10,
    'temperature' => 0.7,
];

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);

echo "Sending request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: {$httpCode}\n\n";

if ($curlError) {
    echo "❌ cURL Error: {$curlError}\n\n";
    exit(1);
}

$responseData = json_decode($response, true);

if ($httpCode === 200) {
    if (isset($responseData['choices'][0]['message']['content'])) {
        $content = $responseData['choices'][0]['message']['content'];
        echo "✅ SUCCESS: API Key is valid!\n";
        echo "Response: {$content}\n\n";
        echo "The API key is working correctly.\n";
        echo "You can now configure it as backup provider.\n";
    } else {
        echo "⚠️ Unexpected response format:\n";
        echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "❌ API Request Failed\n";
    echo "Response:\n";
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    if (isset($responseData['error'])) {
        echo "Error Details:\n";
        echo "- Type: " . ($responseData['error']['type'] ?? 'N/A') . "\n";
        echo "- Message: " . ($responseData['error']['message'] ?? 'N/A') . "\n";
        echo "- Code: " . ($responseData['error']['code'] ?? 'N/A') . "\n";
    }
}

echo "\n";


