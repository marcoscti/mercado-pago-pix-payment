<?php
class MercadoPagoPix
{
    private $accessToken;

    public function __construct()
    {
        $this->accessToken = "APP_USR-5308731074929817-022510-5c886ef37791bc5a395b389f68221d36-2287791141";
        // Configurar timezone e locale para Brasil
        date_default_timezone_set('America/Sao_Paulo');

        // Configurações adicionais para evitar warnings
        if (!defined('MP_SKIP_VALIDATION')) {
            define('MP_SKIP_VALIDATION', true);
        }
    }
    /**
     * Processar PIX DIRETO na aplicação (sem redirecionamento)
     *@format ['value'=>1.0, 'email'=>'email@email.com', 'first_name'=>'Name', 'last_name'=>'Lastname', 'cpf'=>'00000000000']
     */
    public function processPixDirect($dados)
    {
        try {
           // Preparar dados do pagamento PIX
            $paymentData = [
                'transaction_amount' => (float)$dados['value'],
                'description' => 'Doação PIX Hardtale',
                'payment_method_id' => 'pix',
                'external_reference' => "Donate",
                'notification_url' => 'https://hardtale.com.br/api/payment/webhook',
                'statement_descriptor' => 'Hardtale',
                'binary_mode' => true
            ];

            // Configurar pagador
            if ($dados) {
                $paymentData['payer'] = [
                    'email' => $dados['email'],
                    'first_name' => $dados['first_name'],
                    'last_name' => $dados['last_name'],
                    'identification' => [
                        'type' => 'CPF',
                        'number' => $dados['cpf']
                    ]
                ];
            }
           
            // Fazer requisição direta para a API do MercadoPago
            $url = 'https://api.mercadopago.com/v1/payments';
            $headers = [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
                'User-Agent: *',
                'X-Idempotency-Key: ' . uniqid('pix_', true)
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception("Erro cURL: " . $error);
            }

            $responseData = json_decode($response, true);

            if ($httpCode !== 201 && $httpCode !== 200) {
                $errorMsg = isset($responseData['message']) ? $responseData['message'] : 'Erro desconhecido';
                $errorDetail = isset($responseData['error']) ? $responseData['error'] : '';
                throw new \Exception("API retornou HTTP $httpCode: $errorMsg $errorDetail");
            }

            if (!$responseData || !isset($responseData['id'])) {
                throw new \Exception("Resposta inválida da API: " . $response);
            }

            // Pagamento PIX criado com sucesso
            $paymentId = $responseData['id'];
            $status = $responseData['status'];


            // Extrair dados do PIX
            $qrCode = '';
            $qrCodeBase64 = '';

            if (
                isset($responseData['point_of_interaction']) &&
                isset($responseData['point_of_interaction']['transaction_data'])
            ) {
                $qrCode = $responseData['point_of_interaction']['transaction_data']['qr_code'] ?? '';
                $qrCodeBase64 = $responseData['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '';
            }

            return json_encode([
                'success' => true,
                'payment_id' => $paymentId,
                'status' => $status,
                'status_detail' => $responseData['status_detail'] ?? 'pending',
                'qr_code' => $qrCode,
                'qr_code_base64' => $qrCodeBase64,
                'external_reference' => $responseData['external_reference'],
                'payer_info' => [
                    'email' => $responseData['binary_mode']['payer']['email'] ?? '',
                    'name' => (string)($responseData['binary_mode']['payer']['first_name'] ?? '') . ' ' . (string)($responseData['payer']['last_name'] ?? '')
                ]
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'method' => 'processPixDirect'
                ]
            ]);
        }
    }
    /**
     * Verificar status do pagamento usando API REST (sem SDK)
     */
    public function getPaymentStatusDirect($paymentId)
    {
        try {
            // Fazer requisição direta para a API do MercadoPago
            $url = "https://api.mercadopago.com/v1/payments/$paymentId";
            $headers = [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
                'User-Agent: *'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception("Erro cURL: " . $error);
            }

            if ($httpCode !== 200) {
                throw new \Exception("API retornou HTTP $httpCode");
            }

            $responseData = json_decode($response, true);

            if (!$responseData || !isset($responseData['id'])) {
                throw new \Exception("Resposta inválida da API");
            }

            return json_encode([
                'success' => true,
                'payment_id' => $responseData['id'],
                'status' => $responseData['status'],
                'status_detail' => $responseData['status_detail'] ?? '',
                'external_reference' => $responseData['external_reference'] ?? ''
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
