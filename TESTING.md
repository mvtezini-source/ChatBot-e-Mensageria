# Teste do Sistema de Mensageria em Tempo Real

## Status de Execução

✅ **Backend (PHP + Slim)**: Rodando em `http://localhost:8000`
✅ **WebSocket (Ratchet)**: Rodando em `ws://localhost:8080`
✅ **Frontend (React)**: Rodando em `http://localhost:3000`

## Pré-requisitos Atendidos

- [x] PHP 8.3+ instalado
- [x] Composer instalado e dependências instaladas
- [x] Node/NPM instalado e dependências instaladas
- [x] Servidores HTTP e WebSocket iniciados
- [ ] MySQL instalado e banco criado (**PENDENTE** — você precisa fazer manualmente)

## Próximos Passos

### 1. Configurar e Criar Banco de Dados

Se você tiver MySQL/MariaDB disponível em sua máquina local:

```bash
# Copie o arquivo .env.example para .env e ajuste as credenciais
cd backend
cp .env.example .env

# Edite .env com suas credenciais MySQL:
# DB_HOST=127.0.0.1
# DB_USERNAME=root (ou seu usuário)
# DB_PASSWORD=sua_senha
# DB_DATABASE=chat

# Crie o banco e as tabelas
mysql -u root -p < backend/sql/schema.sql

# Popule com dados de teste (OPCIONAL)
mysql -u root -p < backend/sql/seed-test-data.sql
```

### 2. Criar Dados de Teste (via CLI PHP)

Se o MySQL estiver acessível, use o script de setup automático:

```bash
cd backend
php setup-test-data.php
```

**Credenciais de teste criadas:**
- **Email**: alice@test.local | **Senha**: password123
- **Email**: bob@test.local | **Senha**: password123

### 3. Acessar o Sistema

No navegador:

1. Acesse `http://localhost:3000`
2. Você verá duas opções:
   - **Login**: Use uma das credenciais de teste acima
   - **Registrar**: Crie um novo usuário

### 4. Testar Fluxo Completo

#### Via UI (React)

1. Faça login com `alice@test.local` / `password123`
2. Veja a lista de conversas (se dados de teste foram criados)
3. Selecione uma conversa
4. Envie uma mensagem (ex.: "Oi, qual o horário?")
5. O bot responderá automaticamente (se habilitado na conversa)

#### Via Linha de Comando (cURL)

**Registrar novo usuário:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Teste",
    "email": "teste@local",
    "password": "senha123"
  }'
```

**Fazer login:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teste@local",
    "password": "senha123"
  }'
# Resposta: {"token":"<JWT>"}
```

**Copie o token JWT da resposta e use nos comandos abaixo.**

**Listar conversas:**
```bash
curl -H "Authorization: Bearer <JWT>" \
  http://localhost:8000/api/conversations
```

**Obter histórico de mensagens:**
```bash
curl -H "Authorization: Bearer <JWT>" \
  http://localhost:8000/api/conversations/1/messages
```

**Enviar mensagem (persistência REST):**
```bash
curl -X POST http://localhost:8000/api/conversations/1/messages \
  -H "Authorization: Bearer <JWT>" \
  -H "Content-Type: application/json" \
  -d '{
    "conversation_id": 1,
    "content": "Olá bot!"
  }'
```

### 5. Testar WebSocket em Tempo Real

No console do navegador (F12):

```javascript
// Usar token como subprotocol (mais seguro)
const token = '<JWT>';
const ws = new WebSocket('ws://localhost:8080', token);

ws.onopen = () => {
  console.log('✓ Conectado ao WebSocket');
  
  // Inscrever na conversa 1
  ws.send(JSON.stringify({
    type: 'subscribe',
    conversation_id: 1
  }));
  
  // Enviar mensagem
  ws.send(JSON.stringify({
    type: 'message',
    conversation_id: 1,
    from: 'Alice',
    content: 'Oi bot, qual é o horário?'
  }));
};

ws.onmessage = (event) => {
  const data = JSON.parse(event.data);
  console.log('Mensagem recebida:', data);
};

ws.onerror = (e) => console.error('Erro WS:', e);
ws.onclose = () => console.log('WebSocket fechado');
```

## Configuração do Bot (Chatbot Inteligente)

### Modo Heurístico (Padrão)

O bot responde com respostas pré-configuradas baseadas em palavras-chave.

Em `backend/.env`:
```
BOT_MODE=heuristic
```

### Modo AI (OpenAI)

Para usar GPT:

1. Adicione sua chave da OpenAI em `backend/.env`:
```
OPENAI_API_KEY=sk-...
BOT_MODE=ai
```

2. O bot agora chama `gpt-3.5-turbo` para gerar respostas.

### Desabilitar Bot por Conversa

O bot responde por padrão. Para desabilitar em uma conversa específica:

```sql
UPDATE conversation_bot_settings 
SET bot_enabled = 0 
WHERE conversation_id = 1;
```

## Troubleshooting

### Erro: "Connection refused" na porta 8000 ou 8080

- Verifique se os servidores estão rodando nos terminais de background.
- Use `lsof -i :8000` e `lsof -i :8080` para verificar.

### Erro: "SQLSTATE[HY000]: General error: 2002"

- MySQL não está rodando ou as credenciais em `.env` estão incorretas.
- Verifique: `mysql -u root -p -e "SELECT 1;"`

### Frontend em branco

- Verifique o console do navegador (F12) para erros.
- Certifique-se de que o backend HTTP está acessível em `http://localhost:8000/api/login`.

### Bot não responde

- Verifique em `backend/.env` o valor de `BOT_MODE` (deve ser `heuristic` ou `ai`).
- Se usar `ai`, confirme que `OPENAI_API_KEY` é válida.
- Verifique se o `conversation_bot_settings` tem `bot_enabled=1` para a conversa.

## Arquivos Principais

- `backend/src/Api.php` — Rotas REST
- `backend/src/WebSocket/ChatServer.php` — Handler WebSocket
- `backend/src/Bot/ChatBot.php` — Lógica do bot
- `frontend/src/App.js` — Componente raiz
- `frontend/src/components/ChatWindow.js` — Interface de chat
- `backend/sql/schema.sql` — Esquema do banco
- `backend/sql/seed-test-data.sql` — Dados de teste (opcional)
- `openapi.json` — Documentação OpenAPI das rotas

## Checklist de Teste End-to-End

- [ ] 1. Registrar novo usuário via UI ou API
- [ ] 2. Fazer login e receber JWT
- [ ] 3. Conectar ao WebSocket (WS)
- [ ] 4. Carregar histórico de mensagens (REST)
- [ ] 5. Enviar mensagem via WS e receber em tempo real
- [ ] 6. Verificar persistência da mensagem no DB
- [ ] 7. Bot responde automaticamente com heurística
- [ ] 8. Desabilitar bot para uma conversa e verificar que não responde

## Documentação Adicional

- `README.md` — Overview do projeto
- `openapi.json` — Documentação OpenAPI (use Swagger UI ou Redoc online)
- `backend/src/settings.php` — Configurações do sistema
- `backend/.env.example` — Variáveis de ambiente disponíveis
