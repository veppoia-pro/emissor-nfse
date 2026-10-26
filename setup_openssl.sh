#!/bin/bash
export OPENSSL_CONF=/samba/share/privado/projetos/php/teste01/openssl_legacy.cnf
export OPENSSL_ENABLE_SHA1_SIGNATURES=1
echo "✅ Variáveis OpenSSL configuradas!"
echo "OPENSSL_CONF: $OPENSSL_CONF"
echo "OPENSSL_ENABLE_SHA1_SIGNATURES: $OPENSSL_ENABLE_SHA1_SIGNATURES"
