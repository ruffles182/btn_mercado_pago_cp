<?php
header("Access-Control-Allow-Origin: https://tupaginadetienda.com.mx");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $input = file_get_contents('php://input');
    if ($input === false) {
        throw new Exception('Error reading input');
    }

    $cart = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decoding JSON: ' . json_last_error_msg());
    }

    $items = [];
    foreach ($cart['items'] as $item) {
        $items[] = [
            'title' => $item['title'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['price'] / 100
        ];
    }

    $preferenceData = [
        'items' => $items
    ];

    $accessToken = '<<ACCES-TOKEN>>';  // <-- Acces token de Mercado pago
    $url = 'https://api.mercadopago.com/checkout/preferences';

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n" .
                         "Authorization: Bearer $accessToken\r\n",
            'method'  => 'POST',
            'content' => json_encode($preferenceData),
        ],
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        throw new Exception('Error accessing MercadoPago API');
    }

    $response = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decoding response JSON: ' . json_last_error_msg());
    }

    header('Content-Type: application/json');
    echo json_encode(['preference_id' => $response['id']]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>