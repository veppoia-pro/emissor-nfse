# NFSe Nacional — Guarda de Bagagens

Emissor PHP de NFSe Nacional para guarda e depósito de bagagens, com logs em arquivo e persistência em MySQL.

## Preparação

1. Instale uma versão do PHP compatível com `composer.lock` e o Composer. As dependências travadas exigem PHP 8.2 ou superior.
2. Execute `composer install` para instalar as dependências.
3. Copie `config.local.example.php` para `config.local.php` e preencha a conexão MySQL, o caminho e a senha do certificado A1.
4. Disponibilize o certificado em `certificados/` ou informe seu caminho completo na configuração.
5. Disponibilize a base MySQL e a tabela `nfseNacionalj` usadas pelo sistema. Este projeto não contém migração de banco.
6. Garanta permissão de escrita em `logs/` e no diretório do projeto.

As chaves de `config.local.php` também podem ser definidas como variáveis de ambiente, que têm prioridade. O arquivo `.env` não é carregado automaticamente.

## Uso

```sh
php instalar.php
php testar_banco.php
php emissor_nfse.php
php consultar_logs.php
```

Revise os dados em `emissor_nfse.php` antes de emitir. O emissor está configurado para homologação (`tpamb` e `tpAmb` iguais a `2`). Executá-lo envia uma DPS ao serviço de NFSe.

O arquivo `ultimo_numero_dps.txt` guarda a numeração local. Em uma instalação existente, preserve esse arquivo; em uma nova instalação, configure a sequência adequada antes da primeira emissão.

## Arquivos locais

Certificados, chaves privadas, configuração com senhas, XMLs emitidos, logs, cópias de trabalho e dependências não são versionados. Os arquivos existentes continuam no ambiente local.

Consulte também `DOCUMENTACAO_NFSE_BAGAGENS.md` para o histórico de parâmetros do projeto.
