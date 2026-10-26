<?php
/**
 * CONEXÃO COM BANCO DE DADOS NFSe
 * 
 * Servidor: configurado em config.local.php
 * Base: NFSE
 * Tabela: nfseNacionalj
 * Usuário: projeto
 * Credenciais: config.local.php ou variaveis de ambiente
 * 
 * Desenvolvido por: Veppo
 */

require_once __DIR__ . '/config.php';

class NFSeDatabase {
    private $pdo;
    private $host;
    private $dbname;
    private $username;
    private $password;
    
    public function __construct() {
        $this->host = nfseConfig('NFSE_DB_HOST', '127.0.0.1');
        $this->dbname = nfseConfig('NFSE_DB_NAME', 'NFSE');
        $this->username = nfseConfig('NFSE_DB_USER');
        $this->password = nfseConfig('NFSE_DB_PASSWORD');
        $this->conectar();
    }
    
    /**
     * Estabelece conexão com o banco
     */
    private function conectar() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 30
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            echo "🔗 Conectado ao banco: {$this->host}/{$this->dbname}\n";
            
        } catch (PDOException $e) {
            throw new Exception("❌ Erro de conexão com banco: " . $e->getMessage());
        }
    }
    
    /**
     * Insere NFSe no banco de dados
     */
    public function inserirNFSe($dadosNFSe) {
        try {
            $sql = "INSERT INTO nfseNacionalj (
                id_dps, chave_acesso, nfse_numero, nfse_cod_verificacao, 
                nfse_data_emissao, prest_cnpj, prest_razao_social, 
                prest_end_logradouro, prest_end_numero, prest_end_bairro,
                prest_end_cod_municipio, prest_end_municipio, prest_end_uf, 
                prest_end_cep, prest_contato_telefone, prest_contato_email,
                toma_cpf, toma_razao_social, toma_end_logradouro, 
                toma_end_numero, toma_end_bairro, toma_end_cod_municipio,
                toma_end_municipio, toma_end_uf, toma_end_cep,
                serv_discriminacao, serv_codigo_tributacao_municipio,
                serv_municipio_prestacao_cod, serv_municipio_prestacao_nome,
                serv_valor_bruto, serv_valor_liquido, serv_valor_iss,
                serv_aliquota_iss, serv_iss_retido, serv_valor_base_calculo,
                serv_valor_pis, serv_aliquota_pis, serv_valor_cofins,
                serv_aliquota_cofins, orgao_gerador_cod_municipio,
                orgao_gerador_uf, nfse_xml, status, processamento_datahora,
                created_at, updated_at
            ) VALUES (
                :id_dps, :chave_acesso, :nfse_numero, :nfse_cod_verificacao,
                :nfse_data_emissao, :prest_cnpj, :prest_razao_social,
                :prest_end_logradouro, :prest_end_numero, :prest_end_bairro,
                :prest_end_cod_municipio, :prest_end_municipio, :prest_end_uf,
                :prest_end_cep, :prest_contato_telefone, :prest_contato_email,
                :toma_cpf, :toma_razao_social, :toma_end_logradouro,
                :toma_end_numero, :toma_end_bairro, :toma_end_cod_municipio,
                :toma_end_municipio, :toma_end_uf, :toma_end_cep,
                :serv_discriminacao, :serv_codigo_tributacao_municipio,
                :serv_municipio_prestacao_cod, :serv_municipio_prestacao_nome,
                :serv_valor_bruto, :serv_valor_liquido, :serv_valor_iss,
                :serv_aliquota_iss, :serv_iss_retido, :serv_valor_base_calculo,
                :serv_valor_pis, :serv_aliquota_pis, :serv_valor_cofins,
                :serv_aliquota_cofins, :orgao_gerador_cod_municipio,
                :orgao_gerador_uf, :nfse_xml, :status, :processamento_datahora,
                :created_at, :updated_at
            )";
            
            $stmt = $this->pdo->prepare($sql);
            $resultado = $stmt->execute($dadosNFSe);
            
            if ($resultado) {
                $id = $this->pdo->lastInsertId();
                echo "💾 NFSe inserida no banco - ID: {$id}\n";
                return $id;
            }
            
            return false;
            
        } catch (PDOException $e) {
            throw new Exception("❌ Erro ao inserir NFSe no banco: " . $e->getMessage());
        }
    }
    
    /**
     * Registra erro no banco
     */
    public function registrarErro($dadosErro) {
        try {
            $sql = "INSERT INTO nfseNacionalj (
                id_dps, prest_cnpj, toma_cpf, toma_razao_social,
                serv_discriminacao, serv_valor_bruto, status,
                processamento_datahora, erros, created_at, updated_at
            ) VALUES (
                :id_dps, :prest_cnpj, :toma_cpf, :toma_razao_social,
                :serv_discriminacao, :serv_valor_bruto, :status,
                :processamento_datahora, :erros, :created_at, :updated_at
            )";
            
            $stmt = $this->pdo->prepare($sql);
            $resultado = $stmt->execute($dadosErro);
            
            if ($resultado) {
                $id = $this->pdo->lastInsertId();
                echo "💾 Erro registrado no banco - ID: {$id}\n";
                return $id;
            }
            
            return false;
            
        } catch (PDOException $e) {
            throw new Exception("❌ Erro ao registrar erro no banco: " . $e->getMessage());
        }
    }


    /**
 * Busca NFSe por ID DPS
 */
public function buscarPorIdDps($idDps) {
    try {
        $sql = "SELECT * FROM nfseNacionalj WHERE id_dps = :id_dps";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id_dps' => $idDps]);
        
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        throw new Exception("❌ Erro ao buscar NFSe por ID DPS: " . $e->getMessage());
    }
}

/**
 * Lista últimos ID DPS registrados
 */
public function listarUltimosIdDps($limite = 10) {
    try {
        $sql = "SELECT id_dps, chave_acesso, toma_razao_social, serv_valor_bruto, 
                       status, created_at 
                FROM nfseNacionalj 
                WHERE id_dps IS NOT NULL 
                ORDER BY created_at DESC 
                LIMIT :limite";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        throw new Exception("❌ Erro ao listar ID DPS: " . $e->getMessage());
    }
}
    
    /**
     * Busca NFSe por chave de acesso
     */
    public function buscarPorChave($chaveAcesso) {
        try {
            $sql = "SELECT * FROM nfseNacionalj WHERE chave_acesso = :chave_acesso";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['chave_acesso' => $chaveAcesso]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            throw new Exception("❌ Erro ao buscar NFSe: " . $e->getMessage());
        }
    }
    
    /**
     * Lista NFSes por período
     */
    public function listarPorPeriodo($dataInicio, $dataFim) {
        try {
            $sql = "SELECT id, chave_acesso, nfse_numero, toma_razao_social, 
                           serv_valor_bruto, status, created_at 
                    FROM nfseNacionalj 
                    WHERE DATE(created_at) BETWEEN :data_inicio AND :data_fim 
                    ORDER BY created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim
            ]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new Exception("❌ Erro ao listar NFSes: " . $e->getMessage());
        }
    }
    
    /**
     * Estatísticas do dia
     */
    public function estatisticasDia($data = null) {
        $data = $data ?? date('Y-m-d');
        
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'emitida' THEN 1 ELSE 0 END) as sucessos,
                        SUM(CASE WHEN status = 'erro' THEN 1 ELSE 0 END) as erros,
                        SUM(CASE WHEN status = 'emitida' THEN serv_valor_bruto ELSE 0 END) as valor_total
                    FROM nfseNacionalj 
                    WHERE DATE(created_at) = :data";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['data' => $data]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            throw new Exception("❌ Erro ao buscar estatísticas: " . $e->getMessage());
        }
    }
    
    /**
     * Fecha conexão
     */
    public function fecharConexao() {
        $this->pdo = null;
    }
}