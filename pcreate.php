<?php
//ya no se utiliza, se cambió a pcreateTest, tal vez deba cambiar el nombre :v

// Configuración de depuración
$enableDebug = true; // Cambiar a false para desactivar el logging
$logFile = 'pcreateLog.txt';

// Función para registrar logs
function debugLog($message) {
    global $enableDebug, $logFile;
    if ($enableDebug) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');


// Configuración de encabezados
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Configuración de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    debugLog("Iniciando procesamiento de la solicitud...");

    // Leer el cuerpo de la solicitud
    $input = file_get_contents('php://input');
    if ($input === false) {
        throw new Exception('Error al leer la entrada');
    }
    debugLog("Entrada recibida: $input");

    // Decodificar JSON
    $cart = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }
    debugLog("Carrito decodificado correctamente: " . json_encode($cart));

    // Procesar los ítems del carrito
    $items = [];
    foreach ($cart['items'] as $item) {
        $processedItem = [
            'title' => $item['title'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['price'] / 100 // Shopify prices are in cents
        ];
        $items[] = $processedItem;
        debugLog("Ítem procesado: " . json_encode($processedItem));
    }

    // Datos de la preferencia
    $preferenceData = [
        'items' => $items
    ];
    debugLog("Datos de la preferencia: " . json_encode($preferenceData));

    // Configurar API de MercadoPago
    $accessToken = 'APP_USR-2522189370558258-111615-f7404289c9447d0ecdf1b5542459675f-1146055217';
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

    // Llamada a la API de MercadoPago
    debugLog("Enviando datos a MercadoPago...");
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) {
        throw new Exception('Error al acceder a la API de MercadoPago');
    }
    debugLog("Respuesta de MercadoPago: $result");

    // Decodificar respuesta de MercadoPago
    $response = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar respuesta JSON: ' . json_last_error_msg());
    }
    debugLog("Respuesta decodificada: " . json_encode($response));

    // Enviar respuesta al cliente
    header('Content-Type: application/json');
    echo json_encode(['preference_id' => $response['id']]);

} catch (Exception $e) {
    debugLog("Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>
