<?php
require_once __DIR__ . '/config.php';
/**
 * SCRIPT DE INSTALAÇÃO DO SISTEMA NFSe
 * 
 * Verifica dependências e configura o ambiente
 * 
 * Desenvolvido por: Veppo
 */

echo "🚀 INSTALAÇÃO DO SISTEMA NFSe\n";
echo "=============================\n\n";

// Verificações básicas
echo "�� VERIFICANDO AMBIENTE...\n";
echo "──────────────────────────\n";

// PHP Version
echo "🐘 PHP Version: " . PHP_VERSION;
if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo " ✅\n";
} else {
    echo " ❌ (Requer PHP 7.4+)\n";
}

// Extensions
$extensions = ['pdo', 'pdo_mysql', 'openssl', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    echo "📦 Extensão {$ext}: ";
    if (extension_loaded($ext)) {
        echo "✅\n";
    } else {
        echo "❌ (Instale php-{$ext})\n";
    }
}

// Diretórios
echo "📁 Criando diretórios...\n";
$dirs = ['logs', 'certificados'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "   📁 {$dir}/ criado ✅\n";
        } else {
            echo "   📁 {$dir}/ erro ❌\n";
        }
    } else {
        echo "   📁 {$dir}/ já existe ✅\n";
    }
}

// Permissões
echo "🔐 Verificando permissões...\n";
$writableDirs = ['logs', '.'];
foreach ($writableDirs as $dir) {
    if (is_writable($dir)) {
        echo "   📝 {$dir}/ gravável ✅\n";
    } else {
        echo "   📝 {$dir}/ sem permissão ❌\n";
    }
}

echo "\n🧪 TESTANDO CONEXÃO COM BANCO...\n";
echo "─────────────────────────────────\n";

try {
    $pdo = new PDO(nfseDatabaseDsn(), nfseConfig('NFSE_DB_USER'), nfseConfig('NFSE_DB_PASSWORD'));
    echo "✅ Conexão com banco estabelecida!\n";
    
    // Verifica tabela
    $stmt = $pdo->query("SHOW TABLES LIKE 'nfseNacionalj'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabela nfseNacionalj encontrada!\n";
    } else {
        echo "❌ Tabela nfseNacionalj não encontrada!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
}

echo "\n📋 ARQUIVOS DO SISTEMA:\n";
echo "──────────────────────\n";

$arquivos = [
    'emissor_nfse.php' => 'Arquivo principal de emissão',
    'NFSeDatabase.php' => 'Classe de banco de dados',
    'NFSeLogger.php' => 'Classe de log',
    'consultar_logs.php' => 'Consultor de logs',
    'testar_banco.php' => 'Teste de banco'
];

foreach ($arquivos as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        echo "✅ {$arquivo} - {$descricao}\n";
    } else {
        echo "❌ {$arquivo} - {$descricao} (FALTANDO)\n";
    }
}

echo "\n🎯 PRÓXIMOS PASSOS:\n";
echo "─────────────────\n";
echo "1. Execute: php testar_banco.php\n";
echo "2. Execute: php emissor_nfse.php\n";
echo "3. Consulte: php consultar_logs.php\n";
echo "4. Verifique logs em: logs/\n";
echo "5. Verifique dados no banco configurado em config.local.php\n\n";

echo "✅ INSTALAÇÃO CONCLUÍDA!\n";