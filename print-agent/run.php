<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use App\Services\ImpresionService;
use App\Support\ImpresoraDestino;

$envFile = __DIR__.'/.env';
if (! is_file($envFile)) {
    fwrite(STDERR, "Falta print-agent/.env. Copie .env.example y pegue AGENT_TOKEN.\n");
    exit(1);
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $env[trim($key)] = trim($value, " \t\"'");
}

$api = rtrim($env['API_URL'] ?? 'http://127.0.0.1:8000/api', '/');
$token = $env['AGENT_TOKEN'] ?? '';
$interval = max(1, (int) ($env['POLL_INTERVAL'] ?? 3));

if ($token === '') {
    fwrite(STDERR, "AGENT_TOKEN vacío.\n");
    exit(1);
}

$impresion = new ImpresionService();

$call = function (string $method, string $path, ?array $body = null) use ($api, $token): array {
    $ch = curl_init($api.$path);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Print-Agent-Token: '.$token,
    ];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body === null ? null : json_encode($body),
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        throw new RuntimeException($error ?: 'No se pudo contactar la API.');
    }
    $json = json_decode($raw, true);

    return [$code, is_array($json) ? $json : []];
};

echo "Agente de impresión escuchando {$api}\n";

while (true) {
    try {
        [, $heartbeat] = $call('GET', '/print-agent/heartbeat');
        $nombre = $heartbeat['agente']['nombre'] ?? 'agente';
        [$code, $pending] = $call('GET', '/print-agent/jobs/pending');
        if ($code === 401) {
            fwrite(STDERR, "Token inválido.\n");
            exit(1);
        }
        foreach ($pending['data'] ?? [] as $job) {
            $snap = is_array($job['impresora'] ?? null) ? $job['impresora'] : [];
            $destino = new ImpresoraDestino(
                $snap['nombre_sistema'] ?? null,
                $snap['ip'] ?? null,
                (int) ($snap['puerto'] ?? 9100),
                (int) ($snap['ancho_papel'] ?? 80),
                $snap['nombre'] ?? null,
            );
            $resultado = $impresion->imprimir($destino, (string) ($job['contenido'] ?? ''));
            if ($resultado['success'] ?? false) {
                $call('POST', '/print-agent/jobs/'.$job['id'].'/complete');
                echo "Impreso job {$job['id']} ({$nombre})\n";
            } else {
                $call('POST', '/print-agent/jobs/'.$job['id'].'/fail', [
                    'error' => $resultado['error'] ?? 'Error al imprimir.',
                ]);
                echo "Fallo job {$job['id']}: ".($resultado['error'] ?? '')."\n";
            }
        }
    } catch (Throwable $e) {
        fwrite(STDERR, $e->getMessage()."\n");
    }

    sleep($interval);
}
