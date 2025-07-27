<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/process') {
    $request = file_get_contents('php://input');
    $request = json_decode($request, true);
    $request['body'] = json_decode($request['body'], true);
    $request["body"]["requestedAt"] = gmdate('Y-m-d\TH:i:s.000\Z');

    $payments = [
        "http://payment-processor-default:8080",
        "http://payment-processor-fallback:8080",
    ];
    $maxTentativas = 5;

    $sucesso = false;
    try {
        foreach ($payments as $idx => $paymentUrl) {
            for ($tentativas = 1; $tentativas <= $maxTentativas; $tentativas++) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $paymentUrl . "/payments");
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request["body"]));
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200) {
                    $sucesso = true;
                    break 2; // Sai dos dois loops
                }
            }
        }
        if (!$sucesso) {
            file_put_contents('requests.log', "Falha ao processar pagamento após $maxTentativas tentativas em cada processador." . PHP_EOL, FILE_APPEND);
        }
    } catch (Throwable $e) {
        file_put_contents('requests.log', "Erro ao processar pagamento: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
} else {
    http_response_code(404);
    echo "Not Found";
}
