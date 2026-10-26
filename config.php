<?php
/** Configuracao local e variaveis de ambiente (ambiente tem prioridade). */
function nfseConfig($key, $default = null) {
    static $local = null;
    if ($local === null) {
        $path = __DIR__ . '/config.local.php';
        $local = is_file($path) ? require $path : [];
        if (!is_array($local)) {
            throw new RuntimeException('config.local.php deve retornar um array.');
        }
    }

    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }
    if (array_key_exists($key, $local)) {
        return $local[$key];
    }
    if ($default !== null) {
        return $default;
    }
    throw new RuntimeException('Configure ' . $key . ' em config.local.php ou no ambiente.');
}

function nfseDatabaseDsn() {
    return 'mysql:host=' . nfseConfig('NFSE_DB_HOST', '127.0.0.1')
        . ';dbname=' . nfseConfig('NFSE_DB_NAME', 'NFSE') . ';charset=utf8mb4';
}
