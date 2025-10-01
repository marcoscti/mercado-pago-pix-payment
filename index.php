<?php
require_once 'MercadoPagoPix.php';

// Corrige Content-Type
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
$hp = new MercadoPagoPix();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura e decodifica o JSON enviado pelo fetch
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Nenhum dado JSON recebido ou formato inválido'
        ]);
        exit;
    }

    echo $hp->processPixDirect([
        'value' => $input['value'],
        'email' => $input['email'],
        'first_name' => 'Name',
        'last_name' => 'Lastname',
        'cpf' => '00000000000'
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['paymentid'])) {
    echo $hp->getPaymentStatusDirect($_GET['paymentid']);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Argumento inválido! '
    ]);
}
