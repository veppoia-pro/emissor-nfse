<?php
/**
 * CONSULTOR DE LOGS E BANCO DE DADOS NFSe
 * 
 * Script para consultar logs em arquivo e dados no banco
 * 
 * Desenvolvido por: Veppo
 */

require_once __DIR__ . '/NFSeLogger.php';

echo "🔍 CONSULTOR DE LOGS E BANCO NFSe\n";
echo "=================================\n\n";

try {
    $logger = new NFSeLogger();
    
    // Lista todos os logs
    echo "📋 ARQUIVOS DE LOG DISPONÍVEIS:\n";
    $logger->listarLogs();
    
    // Relatório de hoje
    echo "📊 RELATÓRIO DE HOJE:\n";
    $logger->gerarRelatorioCombinado();
    
    // Relatório de ontem (se existir)
    $ontem = date('Y-m-d', strtotime('-1 day'));
    echo "�� RELATÓRIO DE ONTEM ({$ontem}):\n";
    $logger->gerarRelatorioCombinado($ontem);
    
    // Menu interativo
    echo "\n🎯 OPÇÕES DISPONÍVEIS:\n";
    echo "1. Consultar data específica\n";
    echo "2. Buscar por chave de acesso\n";
    echo "3. Listar últimas 10 NFSes\n";
    echo "4. Sair\n\n";
    
    echo "Digite sua opção: ";
    $opcao = trim(fgets(STDIN));
    
    switch ($opcao) {
        case '1':
            echo "Digite a data (YYYY-MM-DD): ";
            $data = trim(fgets(STDIN));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                $logger->gerarRelatorioCombinado($data);
            } else {
                echo "❌ Formato de data inválido!\n";
            }
            break;
            
        case '2':
            echo "Digite a chave de acesso: ";
            $chave = trim(fgets(STDIN));
            if (strlen($chave) > 10) {
                // Busca no banco
                $db = new NFSeDatabase();
                $nfse = $db->buscarPorChave($chave);
                if ($nfse) {
                    echo "\n✅ NFSe encontrada:\n";
                    echo "ID: {$nfse['id']}\n";
                    echo "Número: {$nfse['nfse_numero']}\n";
                    echo "Cliente: {$nfse['toma_razao_social']}\n";
                    echo "Valor: R$ " . number_format($nfse['serv_valor_bruto'], 2, ',', '.') . "\n";
                    echo "Status: {$nfse['status']}\n";
                    echo "Data: {$nfse['created_at']}\n";
                } else {
                    echo "❌ NFSe não encontrada!\n";
                }
            } else {
                echo "❌ Chave de acesso inválida!\n";
            }
            break;
            
        case '3':
            $db = new NFSeDatabase();
            $hoje = date('Y-m-d');
            $nfses = $db->listarPorPeriodo($hoje, $hoje);
            
            echo "\n📋 ÚLTIMAS NFSes DE HOJE:\n";
            echo "========================\n";
            
            if (empty($nfses)) {
                echo "❌ Nenhuma NFSe encontrada hoje.\n";
            } else {
                foreach (array_slice($nfses, 0, 10) as $nfse) {
                    echo "🆔 ID: {$nfse['id']} | ";
                    echo "📄 Nº: {$nfse['nfse_numero']} | ";
                    echo "👤 {$nfse['toma_razao_social']} | ";
                    echo "💰 R$ " . number_format($nfse['serv_valor_bruto'], 2, ',', '.') . " | ";
                    echo "📅 {$nfse['created_at']}\n";
                }
            }
            break;
            
        case '4':
            echo "👋 Até logo!\n";
            break;
            
        default:
            echo "❌ Opção inválida!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}