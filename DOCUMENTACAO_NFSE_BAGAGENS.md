# �� NFSe Nacional - Guarda de Bagagens

## Documentação Completa - Configuração Final

### ✅ CONFIGURAÇÃO TESTADA E APROVADA

**Data dos testes:** 16/12/2025  
**Desenvolvedor:** Veppo  
**Atividade:** Guarda e depósito de bagagens em rodoviária

---

## 🎯 PARÂMETROS OBRIGATÓRIOS

| Campo          | Valor       | Descrição                             |
| -------------- | ----------- | ------------------------------------- |
| **Versão DPS** | `1.01`      | ⚠️ OBRIGATÓRIO - Sistema rejeita 1.00 |
| **Ambiente**   | `2`         | 2=Homologação, 1=Produção             |
| **Código NBS** | `106029000` | ✅ **NOVO CÓDIGO TESTADO E APROVADO** |
| **cTribNac**   | `110401`    | LC 116/2003 - Armazenamento           |
| **CEP**        | `90010000`  | CEP válido de Porto Alegre            |
| **Município**  | `4314902`   | Código IBGE Porto Alegre              |

---

## 🔧 CONFIGURAÇÕES TÉCNICAS

### Certificado Digital

- **Tipo:** A1 (.pfx)
- **Configuração OpenSSL:** `openssl_legacy.cnf`
- **Variável:** `OPENSSL_ENABLE_SHA1_SIGNATURES=1`

### Estrutura XML

- **Namespace:** `http://www.sped.fazenda.gov.br/nfse`
- **Versão:** `1.01` (não usar 1.00)
- **Encoding:** `UTF-8`

---

## 📊 TRIBUTOS CONFIGURADOS

### Municipal (ISSQN)

- **tribISSQN:** `1` (Tributado)
- **tpRetISSQN:** `1` (Retido pelo tomador)

### Federal (PIS/COFINS)

- **CST:** `01` (Tributado)
- **Alíquota PIS:** `0.65%`
- **Alíquota COFINS:** `3.00%`
- **tpRetPisCofins:** `2` (Retido pelo prestador)

---

## 🎒 DESCRIÇÃO DO SERVIÇO

**Descrição padrão:**
"Guarda e depósito de bagagens e volumes pessoais em rodoviária."

**Código de Tributação Nacional:** `110401`  
**Código NBS:** `106029000`

---

## 🚀 COMO USAR

### 1. Emissão Simples

```php
$resultado = emitirNFSeBagagens('5.00', 'João da Silva', '12345678901');
2. Verificar Resultado
php
Copiar

if ($resultado['sucesso']) {
    echo "NFSe emitida: " . $resultado['chaveAcesso'];
} else {
    echo "Erro: " . print_r($resultado['erros'], true);
}
📁 ARQUIVOS IMPORTANTES
emissor_nfse_bagagens_final.php - Sistema principal
ultimo_numero_dps.txt - Controle de numeração
log_nfse_bagagens.txt - Log de operações
certificados/1007009796.pfx - Certificado digital
openssl_legacy.cnf - Configuração OpenSSL
⚠️ ERROS RESOLVIDOS
Código	Descrição	Solução
RNG6110	Falha Schema XML	Usar versão 1.01
E0120	IM não deve ser informado	Remover campo IM
E0240	CEP inválido	Usar CEP válido do município
E0316	Código NBS inexistente	Usar código 106029000 (ou 999999999)
E1260	Versão expirada	Usar versão 1.01
E0207	CPF não encontrado	Usar CPF válido no cadastro da RF
🎯 PRÓXIMOS PASSOS
Testar em Produção: Alterar tpamb para 1
Integrar com Sistema: Conectar com sistema de vendas
Backup: Manter backup dos certificados
Monitoramento: Acompanhar logs de erro
📞 SUPORTE
Desenvolvedor: Veppo
Especialidade: Desenvolvimento Python e PHP
Foco: Inteligência Artificial e Automação

Documentação gerada automaticamente em 16/12/2025 EOF
```
