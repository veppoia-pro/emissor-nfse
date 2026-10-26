<?php
/**
 * TESTE DE CONEXÃO COM BANCO DE DADOS
 * 
 * Testa conectividade e funcionalidades do banco
 * 
 * Desenvolvido por: Veppo
 */

require_once __DIR__ . '/NFSeDatabase.php';

echo "🧪 TESTE DE CONEXÃO COM BANCO DE DADOS\n";
echo "======================================\n";
echo "🖥️  Servidor: configurado em config.local.php\n";
echo "🗄️  Base: NFSE\n";
echo "📋 Tabela: nfseNacionalj\n";
echo "👤 Usuário: projeto\n";
echo "======================================\n\n";

try {
    echo "🔌 Testando conexão...\n";
    $db = new NFSeDatabase();
    echo "✅ Conexão estabelecida com sucesso!\n\n";
    
    echo "📊 Testando consultas...\n";
    
    // Teste 1: Estatísticas de hoje
    echo "1️⃣ Estatísticas de hoje:\n";
    $stats = $db->estatisticasDia();
    echo "   📈 Total: {$stats['total']}\n";
    echo "   ✅ Sucessos: {$stats['sucessos']}\n";
    echo "   ❌ Erros: {$stats['erros']}\n";
    echo "   💰 Valor: R$ " . number_format($stats['valor_total'], 2, ',', '.') . "\n\n";
    
    // Teste 2: Últimos registros
    echo "2️⃣ Últimos 5 registros:\n";
    $nfses = $db->listarPorPeriodo(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));
    
    if (empty($nfses)) {
        echo "   ❌ Nenhum registro encontrado nos últimos 7 dias\n";
    } else {
        foreach (array_slice($nfses, 0, 5) as $nfse) {
            echo "   🆔 ID: {$nfse['id']} | Status: {$nfse['status']} | Data: {$nfse['created_at']}\n";
        }
    }
    echo "\n";
    
    // Teste 3: Estrutura da tabela
    echo "3️⃣ Testando estrutura da tabela...\n";
    $pdo = new PDO(nfseDatabaseDsn(), nfseConfig('NFSE_DB_USER'), nfseConfig('NFSE_DB_PASSWORD'));
    $stmt = $pdo->query("DESCRIBE nfseNacionalj");
    $colunas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   📋 Colunas encontradas: " . count($colunas) . "\n";
    echo "   ✅ Tabela nfseNacionalj existe e está acessível\n\n";
    
    echo "🎉 TODOS OS TESTES PASSARAM!\n";
    echo "🚀 Sistema pronto para uso!\n\n";
    
    echo "🔧 PRÓXIMOS PASSOS:\n";
    echo "1. Execute: php emissor_nfse.php\n";
    echo "2. Consulte: php consultar_logs.php\n";
    echo "3. Verifique a pasta logs/ e a tabela no banco\n";
    
} catch (Exception $e) {
    echo "❌ ERRO NO TESTE: " . $e->getMessage() . "\n\n";
    
    echo "🔧 POSSÍVEIS SOLUÇÕES:\n";
    echo "1. Verifique se o servidor MySQL está rodando\n";
    echo "3. Teste conectividade: ping no servidor configurado em NFSE_DB_HOST\n";
    echo "4. Verifique permissões do usuário 'projeto'\n";
    echo "5. Confirme se a base 'NFSE' existe\n";
    echo "6. Verifique se a tabela 'nfseNacionalj' existe\n\n";
    
    echo "📋 COMANDO SQL PARA CRIAR USUÁRIO (se necessário):\n";
    echo "GRANT ALL PRIVILEGES ON NFSE.* TO 'projeto'@'%';\n";
    echo "FLUSH PRIVILEGES;\n";
}
// Adicione este teste no final do testar_banco.php

echo "4️⃣ Testando busca por ID DPS...\n";
$ultimosIdDps = $db->listarUltimosIdDps(3);

if (empty($ultimosIdDps)) {
    echo "   ❌ Nenhum ID DPS encontrado\n";
} else {
    foreach ($ultimosIdDps as $registro) {
        echo "   🆔 ID DPS: {$registro['id_dps']}\n";
        echo "   📄 Status: {$registro['status']}\n";
        echo "   📅 Data: {$registro['created_at']}\n";
        echo "   ──────────────────────────\n";
    }
}