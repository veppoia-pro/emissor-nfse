<?php
/**
 * EMISSOR NFSe - GUARDA DE BAGAGENS
 * ✅ COM SISTEMA DE LOG COMPLETO + BANCO DE DADOS
 * 
 * 🎯 Código NBS: 106029000 (Testado e funcionando)
 * 💾 Banco: configurado em config.local.php - Tabela: nfseNacionalj
 * 📝 Logs: pasta logs/
 * 
 * Desenvolvido por: Veppo
 * Data: 16/12/2025
 */

// Define as variáveis OpenSSL automaticamente
putenv('OPENSSL_CONF=' . __DIR__ . '/openssl_legacy.cnf');
putenv('OPENSSL_ENABLE_SHA1_SIGNATURES=1');

error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/Sao_Paulo');
require __DIR__ . '/vendor/autoload.php';

// ✅ INCLUI O SISTEMA DE LOG COMPLETO
require_once __DIR__ . '/NFSeLogger.php';

// ==========================================
// 🔑 CÓDIGOS OBRIGATÓRIOS (NÃO ALTERAR)
// ==========================================
define('CODIGO_NBS_VALIDO', '106029000');           // ✅ Código NBS testado e funcionando
define('CODIGO_TRIBUTACAO', '110401');              // ✅ LC 116/2003 - Armazenamento
define('CODIGO_MUNICIPIO', '4314902');              // ✅ Porto Alegre
define('CEP_VALIDO', '90010000');                   // ✅ CEP válido de Porto Alegre

// ==========================================
// 🎯 DADOS QUE VOCÊ PODE ALTERAR
// ==========================================
$dadosVariaveis = [
    'tomador_nao_identificado' => true,
    'valor_servico' => '10.00',
    'descricao_servico' => 'Guarda e depósito de bagagens e volumes pessoais em rodoviária.',
    'telefone_prestador' => '5132100101',
];

function emitirNFSeCodigosCorretos($dados, $logger) {
    $logger->logInicio($dados);

    try {
        echo "🎒 EMITINDO NFSe (TOMADOR NÃO IDENTIFICADO)\n";
        echo "====================================\n";
        echo "💰 Valor: R\$ {$dados['valor_servico']}\n";
        echo "🔑 Código NBS: " . CODIGO_NBS_VALIDO . "\n";
        echo "🏛️ Código Tributação: " . CODIGO_TRIBUTACAO . "\n";
        echo "🏢 Município: " . CODIGO_MUNICIPIO . "\n";
        echo "====================================\n\n";

        // Carregamento do Certificado
        $certPath = nfseConfig('NFSE_CERT_PATH');
        $certPassword = nfseConfig('NFSE_CERT_PASSWORD');
        $certContent = file_get_contents($certPath);
        $cert = \NFePHP\Common\Certificate::readPfx($certContent, $certPassword);

        // Configuração
        $config = new stdClass();
        $config->tpamb = 2; // 2 = Homologação, 1 = Produção
        $configJson = json_encode($config);
        $tools = new \Hadder\NfseNacional\Tools($configJson, $cert);

        // Controle de numeração
        $numeroArquivo = 'ultimo_numero_dps.txt';
        $ultimoNumero = file_exists($numeroArquivo) ? (int)file_get_contents($numeroArquivo) : 200;
        $proximoNumero = $ultimoNumero + 1;
        file_put_contents($numeroArquivo, $proximoNumero);

        // Normaliza valor
        $valorServico = (float) str_replace(',', '.', (string)$dados['valor_servico']);

        // ==========================================
        // 🏗️ MONTAGEM DO DPS (TOMADOR NÃO IDENTIFICADO)
        // ==========================================
        $std = new stdClass();
        $std->infDPS = new stdClass();

        // Dados básicos
        $std->infDPS->tpAmb = 2;
        $std->infDPS->dhEmi = (new DateTime())->format('Y-m-d\TH:i:sP');
        $std->infDPS->verAplic = 'V1.0';
        $std->infDPS->serie = 1;
        $std->infDPS->nDPS = $proximoNumero;
        $std->infDPS->dCompet = (new DateTime())->format('Y-m-d');
        $std->infDPS->tpEmit = 1;
        $std->infDPS->cLocEmi = CODIGO_MUNICIPIO;

        // Prestador
        $std->infDPS->prest = new stdClass();
        $std->infDPS->prest->CNPJ = '92660760000143';
        $std->infDPS->prest->fone = $dados['telefone_prestador'];
        $std->infDPS->prest->regTrib = new stdClass();
        $std->infDPS->prest->regTrib->opSimpNac = 1;
        $std->infDPS->prest->regTrib->regEspTrib = 0;

        // Tomador (SIMPLIFICADO / NÃO IDENTIFICADO)
        $std->infDPS->toma = new stdClass();
        $std->infDPS->toma->cNaoNIF = 1;
        $std->infDPS->toma->xNome = 'Tomador Não Identificado';
        // Não informar CPF/CNPJ e não informar endereço do tomador

        // Serviço
        $std->infDPS->serv = new stdClass();
        $std->infDPS->serv->locPrest = new stdClass();
        $std->infDPS->serv->locPrest->cLocPrestacao = CODIGO_MUNICIPIO;

        $std->infDPS->serv->cServ = new stdClass();
        $std->infDPS->serv->cServ->cTribNac = CODIGO_TRIBUTACAO;
        $std->infDPS->serv->cServ->xDescServ = $dados['descricao_servico'];
        $std->infDPS->serv->cServ->cNBS = CODIGO_NBS_VALIDO;

        // Valores
        $std->infDPS->valores = new stdClass();
        $std->infDPS->valores->vServPrest = new stdClass();
        $std->infDPS->valores->vServPrest->vServ = number_format($valorServico, 2, '.', '');

        // Tributos
        $std->infDPS->valores->trib = new stdClass();
        $std->infDPS->valores->trib->tribMun = new stdClass();
        $std->infDPS->valores->trib->tribMun->tribISSQN = 1;

        // ATENÇÃO: aqui é um ponto de validação comum.
        // Você usava 1. Se der rejeição, teste o enum de "não retido" conforme manual/retorno.
        $std->infDPS->valores->trib->tribMun->tpRetISSQN = 1;

        // Tributos federais
        $std->infDPS->valores->trib->tribFed = new stdClass();
        $std->infDPS->valores->trib->tribFed->piscofins = new stdClass();
        $std->infDPS->valores->trib->tribFed->piscofins->CST = '01';
        $std->infDPS->valores->trib->tribFed->piscofins->vBCPisCofins = number_format($valorServico, 2, '.', '');
        $std->infDPS->valores->trib->tribFed->piscofins->pAliqPis = '0.65';
        $std->infDPS->valores->trib->tribFed->piscofins->pAliqCofins = '3.00';
        $std->infDPS->valores->trib->tribFed->piscofins->vPis = number_format($valorServico * 0.0065, 2, '.', '');
        $std->infDPS->valores->trib->tribFed->piscofins->vCofins = number_format($valorServico * 0.03, 2, '.', '');
        $std->infDPS->valores->trib->tribFed->piscofins->tpRetPisCofins = 2;

        // Total de tributos
        $std->infDPS->valores->trib->totTrib = new stdClass();
        $std->infDPS->valores->trib->totTrib->pTotTrib = new stdClass();
        $std->infDPS->valores->trib->totTrib->pTotTrib->pTotTribFed = '3.65';
        $std->infDPS->valores->trib->totTrib->pTotTrib->pTotTribEst = '0.00';
        $std->infDPS->valores->trib->totTrib->pTotTrib->pTotTribMun = '5.00';

        echo "🔧 Gerando XML...\n";
        $dps = new \Hadder\NfseNacional\Dps($std);
        $xmlOriginal = $dps->render();

        // Recomendação: não forçar versão no str_replace sem necessidade
        $xmlEnviar = $xmlOriginal;

        echo "🚀 Enviando DPS...\n";
        $response = $tools->enviaDps($xmlEnviar);

        if (isset($response['nfseXmlGZipB64'])) {
            $xml = gzdecode(base64_decode($response['nfseXmlGZipB64']));

            echo "🎉 NFSe EMITIDA COM SUCESSO!\n";
            echo "🆔 ID DPS (API): " . ($response['idDps'] ?? '') . "\n";
            echo "🔑 Chave de Acesso: " . ($response['chaveAcesso'] ?? '') . "\n";
            echo "📄 Número DPS (Sequencial): {$proximoNumero}\n";

            $nomeArquivo = "nfse_tomador_nao_identificado_" . date('Y-m-d_H-i-s') . "_dps{$proximoNumero}.xml";
            file_put_contents($nomeArquivo, $xml);
            echo "💾 NFSe salva em: {$nomeArquivo}\n";

            $resultado = [
                'sucesso' => true,
                'arquivo' => $nomeArquivo,
                'chave' => $response['chaveAcesso'] ?? null
            ];

            $logger->logSucesso($dados, $resultado, $xml, $response);
            return $resultado;
        }

        echo "❌ Erro na emissão:\n";
        $erros = $response['erros'] ?? ['Erro desconhecido'];

        if (isset($response['erros']) && is_array($response['erros'])) {
            foreach ($response['erros'] as $erro) {
                $codigo = $erro['Codigo'] ?? '';
                $desc   = $erro['Descricao'] ?? json_encode($erro, JSON_UNESCAPED_UNICODE);
                echo "🔴 {$codigo}: {$desc}\n";
            }
        } else {
            echo "🔴 " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";
        }

        $logger->logErro($dados, $erros, null, $response);
        return ['sucesso' => false, 'erros' => $erros];

    } catch (Exception $e) {
        echo "❌ Erro: " . $e->getMessage() . "\n";
        $logger->logErro($dados, $e->getMessage(), $e->getTraceAsString(), null);
        return ['sucesso' => false, 'erro' => $e->getMessage()];
    }
}

// ==========================================
// 🚀 EXECUTAR EMISSÃO COM SISTEMA COMPLETO
// ==========================================
echo "🎯 EMISSOR NFSe - SISTEMA COMPLETO\n";
echo "==================================\n";
echo "📝 Logs: pasta logs/\n";
echo "💾 Banco: configurado em config.local.php\n";
echo "👤 Usuário: projeto\n";
echo "==================================\n\n";

try {
    // ✅ INICIALIZA O SISTEMA COMPLETO
    $logger = new NFSeLogger();
    
    echo "📁 Sistema de log + banco inicializado!\n\n";
    
    // Lista logs existentes
    $logger->listarLogs();
    
    // Executa a emissão
    $resultado = emitirNFSeCodigosCorretos($dadosVariaveis, $logger);
    
    if ($resultado['sucesso']) {
        echo "\n✅ SISTEMA FUNCIONANDO COM CÓDIGOS CORRETOS!\n";
        echo "📝 Log salvo em arquivo\n";
        echo "💾 Dados salvos no banco MySQL\n";
        echo "🎒 Pronto para uso!\n";
    } else {
        echo "\n❌ Erro no sistema.\n";
        echo "📝 Erro registrado nos logs\n";
        echo "💾 Erro registrado no banco\n";
    }
    
    // Gera relatório combinado
    echo "\n";
    $logger->gerarRelatorioCombinado();
    
} catch (Exception $e) {
    echo "❌ Erro fatal: " . $e->getMessage() . "\n";
}