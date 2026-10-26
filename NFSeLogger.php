<?php
/**
 * SISTEMA DE LOG PARA NFSe - LOGS + BANCO DE DADOS
 *
 * Registra em arquivos de log E no banco de dados MySQL
 *
 * Desenvolvido por: Veppo
 */

require_once __DIR__ . '/NFSeDatabase.php';

class NFSeLogger {
    private $logDir;
    private $logFile;
    private $database;
    private $maxFileSize = 10485760; // 10MB

    public function __construct() {
        $this->logDir = __DIR__ . '/logs';
        $this->createLogDirectory();
        $this->setLogFile();

        // Inicializa conexão com banco
        try {
            $this->database = new NFSeDatabase();
        } catch (Exception $e) {
            echo "⚠️ Aviso: Banco indisponível, usando apenas logs em arquivo\n";
            echo "   Erro: " . $e->getMessage() . "\n";
            $this->database = null;
        }
    }

    private function createLogDirectory() {
        if (!is_dir($this->logDir)) {
            if (!mkdir($this->logDir, 0755, true)) {
                throw new Exception("❌ Não foi possível criar a pasta logs: {$this->logDir}");
            }
            echo "📁 Pasta logs criada: {$this->logDir}\n";
        }

        if (!is_writable($this->logDir)) {
            throw new Exception("❌ Pasta logs sem permissão de escrita: {$this->logDir}");
        }
    }

    private function setLogFile() {
        $today = date('Y-m-d');
        $this->logFile = $this->logDir . "/nfse_{$today}.log";

        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxFileSize) {
            $timestamp = date('H-i-s');
            $backupFile = $this->logDir . "/nfse_{$today}_{$timestamp}.log";
            rename($this->logFile, $backupFile);
            echo "🔄 Log rotacionado para: " . basename($backupFile) . "\n";
        }
    }

    // =========================
    // Helpers para TOMADOR
    // =========================
    private function isTomadorNaoIdentificado(array $dadosEmissao): bool {
        return !empty($dadosEmissao['tomador_nao_identificado']);
    }

    private function getNomeTomador(array $dadosEmissao): string {
        if ($this->isTomadorNaoIdentificado($dadosEmissao)) {
            return 'Tomador Não Identificado';
        }
        return $dadosEmissao['nome_cliente'] ?? 'N/A';
    }

    private function getCpfTomadorMasked(array $dadosEmissao): ?string {
        if ($this->isTomadorNaoIdentificado($dadosEmissao)) {
            return null;
        }
        if (!isset($dadosEmissao['cpf_cliente']) || $dadosEmissao['cpf_cliente'] === '') {
            return null;
        }
        return $this->maskCpf($dadosEmissao['cpf_cliente']);
    }

    private function getCpfTomadorRaw(array $dadosEmissao): ?string {
        if ($this->isTomadorNaoIdentificado($dadosEmissao)) {
            return null;
        }
        return $dadosEmissao['cpf_cliente'] ?? null;
    }

    /**
     * Log de início de operação
     */
    public function logInicio($dadosEmissao) {
        $dadosEmissao = is_array($dadosEmissao) ? $dadosEmissao : [];

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'nivel' => 'INFO',
            'tipo' => 'INICIO_EMISSAO',
            'dados_cliente' => [
                'tomador_nao_identificado' => $this->isTomadorNaoIdentificado($dadosEmissao),
                'nome' => $this->getNomeTomador($dadosEmissao),
                'cpf' => $this->getCpfTomadorMasked($dadosEmissao),
                'valor' => $dadosEmissao['valor_servico'] ?? null
            ],
            'configuracao' => [
                'ambiente' => 'HOMOLOGACAO',
                'codigo_nbs' => defined('CODIGO_NBS_VALIDO') ? CODIGO_NBS_VALIDO : 'N/A',
                'municipio' => defined('CODIGO_MUNICIPIO') ? CODIGO_MUNICIPIO : 'N/A',
                'pasta_logs' => $this->logDir
            ]
        ];

        $this->writeLog($logData);
        echo "📝 ℹ️ Log de INÍCIO salvo em: logs/" . basename($this->logFile) . "\n";
    }

    /**
     * Log de sucesso na emissão + Inserção no banco
     */
    public function logSucesso($dadosEmissao, $resultado, $xmlCompleto = null, $response = null) {
        $dadosEmissao = is_array($dadosEmissao) ? $dadosEmissao : [];
        $resultado = is_array($resultado) ? $resultado : [];
        $response = is_array($response) ? $response : null;

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'nivel' => 'SUCESSO',
            'tipo' => 'EMISSAO_NFSE',
            'dados_cliente' => [
                'tomador_nao_identificado' => $this->isTomadorNaoIdentificado($dadosEmissao),
                'nome' => $this->getNomeTomador($dadosEmissao),
                'cpf' => $this->getCpfTomadorMasked($dadosEmissao),
                'valor' => $dadosEmissao['valor_servico'] ?? null
            ],
            'resultado' => [
                'chave_acesso' => $resultado['chave'] ?? 'N/A',
                'arquivo_gerado' => $resultado['arquivo'] ?? 'N/A',
                'id_dps_api' => $response['idDps'] ?? 'N/A',
                'numero_dps_sequencial' => $this->extrairNumeroDps($resultado['arquivo'] ?? '')
            ],
            'banco_dados' => [
                'inserido' => false,
                'id_registro' => null
            ],
            'sistema' => [
                'memoria_utilizada' => $this->getMemoryUsage(),
                'tempo_execucao' => $this->getExecutionTime()
            ]
        ];

        // 💾 INSERÇÃO NO BANCO DE DADOS
        if ($this->database && $xmlCompleto && $response) {
            try {
                $dadosBanco = $this->prepararDadosBanco($dadosEmissao, $resultado, $xmlCompleto, $response);
                $idBanco = $this->database->inserirNFSe($dadosBanco);

                $logData['banco_dados']['inserido'] = true;
                $logData['banco_dados']['id_registro'] = $idBanco;

                echo "💾 ✅ NFSe salva no banco - ID: {$idBanco} | ID_DPS: {$response['idDps']}\n";
            } catch (Exception $e) {
                $logData['banco_dados']['erro'] = $e->getMessage();
                echo "⚠️ Erro ao salvar no banco: " . $e->getMessage() . "\n";
            }
        }

        $this->writeLog($logData);
        echo "📝 ✅ Log de SUCESSO salvo em: logs/" . basename($this->logFile) . "\n";
    }

    /**
     * Log de erro na emissão + Registro no banco
     */
    public function logErro($dadosEmissao, $erro, $stackTrace = null, $response = null) {
        $dadosEmissao = is_array($dadosEmissao) ? $dadosEmissao : [];
        $response = is_array($response) ? $response : null;

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'nivel' => 'ERRO',
            'tipo' => 'EMISSAO_NFSE',
            'dados_cliente' => [
                'tomador_nao_identificado' => $this->isTomadorNaoIdentificado($dadosEmissao),
                'nome' => $this->getNomeTomador($dadosEmissao),
                'cpf' => $this->getCpfTomadorMasked($dadosEmissao),
                'valor' => $dadosEmissao['valor_servico'] ?? null
            ],
            'erro' => [
                'mensagem' => is_array($erro) ? json_encode($erro, JSON_UNESCAPED_UNICODE) : (string)$erro,
                'stack_trace' => $stackTrace,
                'codigo_erro' => $this->extrairCodigoErro($erro)
            ],
            'banco_dados' => [
                'registrado' => false,
                'id_registro' => null
            ],
            'sistema' => [
                'memoria_utilizada' => $this->getMemoryUsage(),
                'tempo_execucao' => $this->getExecutionTime(),
                'php_version' => PHP_VERSION,
                'servidor' => $_SERVER['SERVER_NAME'] ?? 'CLI',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'localhost'
            ]
        ];

        // 💾 REGISTRO DE ERRO NO BANCO
        if ($this->database) {
            try {
                $dadosErroBanco = $this->prepararDadosErroBanco($dadosEmissao, $erro, $response);
                $idBanco = $this->database->registrarErro($dadosErroBanco);

                $logData['banco_dados']['registrado'] = true;
                $logData['banco_dados']['id_registro'] = $idBanco;

                echo "💾 ❌ Erro registrado no banco - ID: {$idBanco}\n";
            } catch (Exception $e) {
                $logData['banco_dados']['erro'] = $e->getMessage();
                echo "⚠️ Erro ao registrar erro no banco: " . $e->getMessage() . "\n";
            }
        }

        $this->writeLog($logData);
        echo "📝 ❌ Log de ERRO salvo em: logs/" . basename($this->logFile) . "\n";
    }

    /**
     * Prepara dados para inserção no banco (SUCESSO)
     */
    private function prepararDadosBanco($dadosEmissao, $resultado, $xmlCompleto, $response) {
        $tomadorNaoIdent = $this->isTomadorNaoIdentificado($dadosEmissao);

        $nfseNumero = $this->extrairNumeroNFSe($xmlCompleto);
        $codigoVerificacao = $this->extrairCodigoVerificacao($xmlCompleto);

        // CONFIGURÁVEL: dados fixos do prestador
        $prestCnpj = '92660760000143';

        return [
            'id_dps' => $response['idDps'],
            'chave_acesso' => $resultado['chave'] ?? '',
            'nfse_numero' => $nfseNumero,
            'nfse_cod_verificacao' => $codigoVerificacao,
            'nfse_data_emissao' => date('Y-m-d H:i:s'),

            'prest_cnpj' => $prestCnpj,
            'prest_razao_social' => 'VEPPO CIA LIMITADA',
            'prest_end_logradouro' => 'LG VESPASIANO JULIO VEPPO',
            'prest_end_numero' => 'S/N',
            'prest_end_bairro' => 'CENTRO',
            'prest_end_cod_municipio' => defined('CODIGO_MUNICIPIO') ? CODIGO_MUNICIPIO : null,
            'prest_end_municipio' => 'Porto Alegre',
            'prest_end_uf' => 'RS',
            'prest_end_cep' => '90035040',
            'prest_contato_telefone' => $dadosEmissao['telefone_prestador'] ?? '5132100101',
            'prest_contato_email' => 'ti@rodoviaria-poa.com.br',

            // Tomador: se não identificado, vai null e nome padrão
            'toma_cpf' => $tomadorNaoIdent ? null : ($dadosEmissao['cpf_cliente'] ?? null),
            'toma_razao_social' => $tomadorNaoIdent ? 'Tomador Não Identificado' : ($dadosEmissao['nome_cliente'] ?? null),

            // Endereço do tomador: se não identificado, zera
            'toma_end_logradouro' => $tomadorNaoIdent ? null : 'Rua dos Andradas',
            'toma_end_numero' => $tomadorNaoIdent ? null : '1234',
            'toma_end_bairro' => $tomadorNaoIdent ? null : 'Centro',
            'toma_end_cod_municipio' => $tomadorNaoIdent ? null : (defined('CODIGO_MUNICIPIO') ? CODIGO_MUNICIPIO : null),
            'toma_end_municipio' => $tomadorNaoIdent ? null : 'Porto Alegre',
            'toma_end_uf' => $tomadorNaoIdent ? null : 'RS',
            'toma_end_cep' => $tomadorNaoIdent ? null : (defined('CEP_VALIDO') ? CEP_VALIDO : null),

            'serv_discriminacao' => $dadosEmissao['descricao_servico'] ?? '',
            'serv_codigo_tributacao_municipio' => defined('CODIGO_TRIBUTACAO') ? CODIGO_TRIBUTACAO : null,
            'serv_municipio_prestacao_cod' => defined('CODIGO_MUNICIPIO') ? CODIGO_MUNICIPIO : null,
            'serv_municipio_prestacao_nome' => 'Porto Alegre',

            'serv_valor_bruto' => floatval($dadosEmissao['valor_servico'] ?? 0),
            'serv_valor_liquido' => floatval($dadosEmissao['valor_servico'] ?? 0),
            'serv_valor_iss' => floatval($dadosEmissao['valor_servico'] ?? 0) * 0.05,
            'serv_aliquota_iss' => 5.00,
            'serv_iss_retido' => 0,
            'serv_valor_base_calculo' => floatval($dadosEmissao['valor_servico'] ?? 0),

            'serv_valor_pis' => floatval($dadosEmissao['valor_servico'] ?? 0) * 0.0065,
            'serv_aliquota_pis' => 0.65,
            'serv_valor_cofins' => floatval($dadosEmissao['valor_servico'] ?? 0) * 0.03,
            'serv_aliquota_cofins' => 3.00,

            'orgao_gerador_cod_municipio' => defined('CODIGO_MUNICIPIO') ? CODIGO_MUNICIPIO : null,
            'orgao_gerador_uf' => 'RS',

            'nfse_xml' => $xmlCompleto,
            'status' => 'emitida',
            'processamento_datahora' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Prepara dados para registro de erro no banco
     */
    private function prepararDadosErroBanco($dadosEmissao, $erro, $response) {
        $tomadorNaoIdent = $this->isTomadorNaoIdentificado($dadosEmissao);

        return [
            // IMPORTANTE:
            // Se seu banco tiver id_dps NOT NULL, vai quebrar quando não existir idDps.
            // O ideal é alterar a coluna para aceitar NULL.
            'id_dps' => ($response['idDps'] ?? null),

            'prest_cnpj' => '92660760000143',
            'toma_cpf' => $tomadorNaoIdent ? null : ($dadosEmissao['cpf_cliente'] ?? null),
            'toma_razao_social' => $tomadorNaoIdent ? 'Tomador Não Identificado' : ($dadosEmissao['nome_cliente'] ?? null),

            'serv_discriminacao' => $dadosEmissao['descricao_servico'] ?? '',
            'serv_valor_bruto' => floatval($dadosEmissao['valor_servico'] ?? 0),

            'status' => 'erro',
            'processamento_datahora' => date('Y-m-d H:i:s'),
            'erros' => is_array($erro) ? json_encode($erro, JSON_UNESCAPED_UNICODE) : (string)$erro,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function extrairNumeroNFSe($xml) {
        if ($xml && preg_match('/<nNfse>(\d+)<\/nNfse>/', $xml, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function extrairCodigoVerificacao($xml) {
        if ($xml && preg_match('/<cVerif>([^<]+)<\/cVerif>/', $xml, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function writeLog($logData) {
        $separator = str_repeat('=', 80);
        $logLine = $separator . "\n" .
            json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) .
            "\n" . $separator . "\n\n";

        if (file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX) === false) {
            throw new Exception("❌ Erro ao escrever no arquivo de log: {$this->logFile}");
        }
    }

    private function maskCpf($cpf) {
        $cpf = preg_replace('/\D+/', '', (string)$cpf);
        if (strlen($cpf) === 11) {
            return substr($cpf, 0, 3) . '***' . substr($cpf, 6, 2) . '***' . substr($cpf, -2);
        }
        return $cpf;
    }

    private function extrairNumeroDps($nomeArquivo) {
        if ($nomeArquivo && preg_match('/dps(\d+)\.xml/', $nomeArquivo, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function extrairCodigoErro($erro) {
        if (is_array($erro) && isset($erro[0]['Codigo'])) {
            return $erro[0]['Codigo'];
        }
        return 'N/A';
    }

    private function getMemoryUsage() {
        return round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB';
    }

    private function getExecutionTime() {
        return round(microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true)), 3) . 's';
    }

    public function gerarRelatorioDiario($data = null) {
        $data = $data ?? date('Y-m-d');
        $logFile = $this->logDir . "/nfse_{$data}.log";

        if (!file_exists($logFile)) {
            echo "❌ Não há logs para a data: {$data}\n";
            echo "📁 Procurado em: logs/nfse_{$data}.log\n";
            return;
        }

        $conteudo = file_get_contents($logFile);
        $sucessos = substr_count($conteudo, '"nivel": "SUCESSO"');
        $erros = substr_count($conteudo, '"nivel": "ERRO"');
        $inicios = substr_count($conteudo, '"nivel": "INFO"');
        $total = $sucessos + $erros;

        echo "\n📊 RELATÓRIO DIÁRIO (LOGS) - {$data}\n";
        echo "====================================\n";
        echo "📁 Arquivo: logs/nfse_{$data}.log\n";
        echo "ℹ️  Inícios: {$inicios}\n";
        echo "✅ Sucessos: {$sucessos}\n";
        echo "❌ Erros: {$erros}\n";
        echo "📈 Total Emissões: {$total}\n";
        echo "📊 Taxa de Sucesso: " . ($total > 0 ? round(($sucessos / $total) * 100, 2) : 0) . "%\n";
        echo "💾 Tamanho do Log: " . round(filesize($logFile) / 1024, 2) . " KB\n";
        echo "====================================\n\n";
    }

    public function gerarRelatorioCombinado($data = null) {
        $data = $data ?? date('Y-m-d');

        echo "\n📊 RELATÓRIO COMBINADO - {$data}\n";
        echo "===================================\n";

        $this->gerarRelatorioDiario($data);

        if ($this->database) {
            try {
                $stats = $this->database->estatisticasDia($data);

                echo "💾 DADOS NO BANCO DE DADOS:\n";
                echo "──────────────────────────\n";
                echo "📈 Total registros: " . ($stats['total'] ?? 0) . "\n";
                echo "✅ Sucessos no banco: " . ($stats['sucessos'] ?? 0) . "\n";
                echo "❌ Erros no banco: " . ($stats['erros'] ?? 0) . "\n";

                if (!empty($stats['valor_total']) && $stats['valor_total'] > 0) {
                    echo "💰 Valor total emitido: R$ " . number_format($stats['valor_total'], 2, ',', '.') . "\n";
                }

                echo "──────────────────────────\n\n";
            } catch (Exception $e) {
                echo "⚠️ Erro ao consultar banco: " . $e->getMessage() . "\n\n";
            }
        }
    }

    public function listarLogs() {
        $arquivos = glob($this->logDir . "/nfse_*.log");

        echo "📋 LOGS NA PASTA 'logs':\n";
        echo "========================\n";
        echo "📁 Pasta: {$this->logDir}\n";
        echo "------------------------\n";

        if (empty($arquivos)) {
            echo "❌ Nenhum arquivo de log encontrado\n";
            return;
        }

        foreach ($arquivos as $arquivo) {
            $tamanho = round(filesize($arquivo) / 1024, 2);
            $nome = basename($arquivo);
            $data = filemtime($arquivo);

            echo "📄 {$nome}\n";
            echo "   💾 Tamanho: {$tamanho} KB\n";
            echo "   📅 Modificado: " . date('d/m/Y H:i:s', $data) . "\n";
            echo "   ────────────────────────────\n";
        }
        echo "\n";
    }
}