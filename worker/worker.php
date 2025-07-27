<?php
// worker.php

$redis = new Redis();
try {
    $redis->connect('redis', 6379);
} catch (Exception $e) {
    die("Erro conexão Redis: " . $e->getMessage() . PHP_EOL);
}

$dsn = 'pgsql:host=postgres;port=5432;dbname=seubanco';
$user = 'seuusuario';
$password = 'suasenha';

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("Erro conexão PostgreSQL: " . $e->getMessage() . PHP_EOL);
}

$queueName = 'fila:payments';

echo "Worker iniciado, escutando fila Redis '$queueName'...\n";

while (true) {
    // BLPOP com timeout 5 segundos
    $data = $redis->blPop([$queueName], 5);

    if ($data) {
        $message = $data[1];
        echo "Mensagem recebida: $message\n";

        $dados = json_decode($message, true);
        if (!$dados) {
            echo "Mensagem inválida: não é JSON\n";
            continue;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO tabela_exemplo (campo1, campo2) VALUES (:campo1, :campo2)");
            $stmt->execute([
                ':campo1' => $dados['campo1'] ?? null,
                ':campo2' => $dados['campo2'] ?? null,
            ]);
            echo "Dados inseridos no banco com sucesso.\n";
        } catch (PDOException $e) {
            echo "Erro ao inserir no banco: " . $e->getMessage() . "\n";
        }
    }
}
