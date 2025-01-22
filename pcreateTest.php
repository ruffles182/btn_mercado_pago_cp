<?php
// Configuración de encabezados
header("Access-Control-Allow-Origin: https://tupaginadetienda.com");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


// Capturar el JSON enviado desde el frontend de Shopify
$jsonInput = file_get_contents('php://input'); // Recibe el JSON desde la solicitud POST

// Decodificar el JSON recibido
$shopifyCart = json_decode($jsonInput, true);

// Verificar si los datos fueron recibidos correctamente
if ($shopifyCart && isset($shopifyCart['items']) && is_array($shopifyCart['items'])) {
    // Crear el arreglo que se enviará a MercadoPago
    $mercadoPagoPreference = [
        'items' => [],
        'back_urls' => [
            'success' => 'https://tusitio.com/success',
            'failure' => 'https://tusitio.com/failure',
            'pending' => 'https://tusitio.com/pending',
        ],
        'notification_url' => 'https://tusitio.com/notifications',
    ];

    // Mapeo de los items del carrito de Shopify para que coincidan con el formato requerido por MercadoPago
    foreach ($shopifyCart['items'] as $item) {
        $mercadoPagoPreference['items'][] = [
            'title' => $item['title'],
            'quantity' => $item['quantity'],
            'unit_price' => (float) $item['price'], // Asegúrate de convertir el precio a un valor numérico
        ];
    }

    // Autenticación con MercadoPago
    require 'vendor/autoload.php'; // Asegúrate de tener el SDK de MercadoPago instalado

    MercadoPago\SDK::setAccessToken('<<ACCES-TOKEN>>'); // Reemplaza con tu token de acceso

    try {
        // Crear la preferencia de pago en MercadoPago
        $preference = new MercadoPago\Preference();
        $preference->items = $mercadoPagoPreference['items'];
        $preference->back_urls = $mercadoPagoPreference['back_urls'];
        $preference->notification_url = $mercadoPagoPreference['notification_url'];
        $preference->save(); // Guardar la preferencia en MercadoPago

        // Responder con el preference_id y el link para redirigir
        $response = [
            'preference_id' => $preference->id,
            'init_point' => $preference->init_point // URL de inicio para el pago
        ];

        echo json_encode($response); // Responder al cliente con los datos necesarios

    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al crear la preferencia en MercadoPago', 'details' => $e->getMessage()]);
    }

} else {
    // En caso de error, devolver un mensaje de error
    echo json_encode(['error' => 'Datos del carrito no válidos o incompletos.']);
}
?>
