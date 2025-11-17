#!/bin/bash
# Demo script para testar fluxo de mensageria via API REST
# Uso: bash backend/demo-api-test.sh

set -e

echo "=========================================="
echo "  DEMO: Chat API Test"
echo "=========================================="

API_URL="http://localhost:8000"

# Cores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "\n${BLUE}1. Verificando conexão com API...${NC}"
if curl -s "$API_URL/api/login" > /dev/null 2>&1; then
  echo -e "${GREEN}✓ API está acessível${NC}"
else
  echo -e "${RED}✗ API não está respondendo. Certifique-se que php -S 0.0.0.0:8000 -t public está rodando.${NC}"
  exit 1
fi

echo -e "\n${BLUE}2. Registrando usuário de teste...${NC}"
REGISTER_RESPONSE=$(curl -s -X POST "$API_URL/api/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "TestUser",
    "email": "testuser@demo.local",
    "password": "demo123"
  }')
echo "Resposta: $REGISTER_RESPONSE"

echo -e "\n${BLUE}3. Fazendo login...${NC}"
LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/api/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@demo.local",
    "password": "demo123"
  }')
echo "Resposta: $LOGIN_RESPONSE"

# Extrair token
TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
  echo -e "${RED}✗ Falha ao obter token. Verifique se o banco de dados está acessível.${NC}"
  echo -e "${YELLOW}   Próximo passo: Configure MySQL e rode 'mysql -u root -p < backend/sql/schema.sql'${NC}"
  exit 1
fi

echo -e "${GREEN}✓ Token obtido: ${TOKEN:0:50}...${NC}"

echo -e "\n${BLUE}4. Listando conversas do usuário...${NC}"
CONVERSATIONS=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "$API_URL/api/conversations")
echo "Resposta: $CONVERSATIONS"

echo -e "\n${BLUE}5. Histórico de mensagens (conversa 1)...${NC}"
MESSAGES=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "$API_URL/api/conversations/1/messages")
echo "Resposta: $MESSAGES"

echo -e "\n${BLUE}6. Enviando mensagem via REST...${NC}"
MSG_RESPONSE=$(curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "conversation_id": 1,
    "content": "Olá, testando API!"
  }' \
  "$API_URL/api/messages")
echo "Resposta: $MSG_RESPONSE"

echo -e "\n${GREEN}=========================================="
echo -e "✓ Demo API completo!"
echo -e "==========================================${NC}"

echo -e "\n${YELLOW}Próximos passos:${NC}"
echo "  1. Abra http://localhost:3000 no navegador"
echo "  2. Registre ou faça login com as mesmas credenciais"
echo "  3. Teste o envio de mensagens em tempo real via UI"
echo ""
echo -e "${YELLOW}Observação:${NC}"
echo "  Se receber erro 'SQLSTATE[HY000]' ou '403 Forbidden':"
echo "  - Verifique se MySQL está rodando"
echo "  - Execute: mysql -u root -p < backend/sql/schema.sql"
echo "  - Execute: php backend/setup-test-data.php"
echo ""
