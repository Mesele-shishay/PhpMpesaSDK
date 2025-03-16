<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MesaSDK\PhpMpesa\Mpesa;

// Initialize the Mpesa class with your configuration
$mpesa = new Mpesa([
    'consumer_key' => 'your_consumer_key',
    'consumer_secret' => 'your_consumer_secret',
    'environment' => 'sandbox', // or 'production'
    'shortcode' => '174379',
    'key' => 'your_passkey'
]);

// Example validation endpoint
function handleValidation()
{
    global $mpesa;

    // Get the request body
    $request = json_decode(file_get_contents('php://input'), true);

    if (!$request) {
        http_response_code(400);
        echo json_encode([
            'ResultCode' => 'C2B00016',
            'ResultDesc' => 'Invalid request body'
        ]);
        return;
    }

    try {
        // Process the validation request
        $response = $mpesa->handleValidation($request);

        // Send the response
        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'ResultCode' => 'C2B00016',
            'ResultDesc' => 'Internal server error: ' . $e->getMessage()
        ]);
    }
}

// Example confirmation endpoint
function handleConfirmation()
{
    global $mpesa;

    // Get the request body
    $request = json_decode(file_get_contents('php://input'), true);

    if (!$request) {
        http_response_code(400);
        echo json_encode([
            'ResultCode' => 'C2B00016',
            'ResultDesc' => 'Invalid request body'
        ]);
        return;
    }

    try {
        // Process the confirmation request
        $response = $mpesa->handleConfirmation($request);

        // Send the response
        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'ResultCode' => 'C2B00016',
            'ResultDesc' => 'Internal server error: ' . $e->getMessage()
        ]);
    }
}

// Route the request based on the endpoint
$requestUri = $_SERVER['REQUEST_URI'];

if (strpos($requestUri, '/validate') !== false) {
    handleValidation();
} elseif (strpos($requestUri, '/confirm') !== false) {
    handleConfirmation();
} else {
    http_response_code(404);
    echo json_encode([
        'ResultCode' => 'C2B00016',
        'ResultDesc' => 'Endpoint not found'
    ]);
}