# ChatBot e Mensageria

Projeto exemplo: sistema de mensageria em tempo real com integração de chatbot.

Estrutura do repositório:

- `backend/` - código PHP (Slim + Ratchet) com handlers REST, servidor WebSocket e integração do bot.
- `frontend/` - app React de exemplo (telas minimalistas: login, lista de conversas, chat).
- `openapi.json` - documentação OpenAPI minimal das rotas REST.
- `backend/sql/schema.sql` - esquema MySQL para iniciar o banco.

Rápido guia de desenvolvimento

1) Backend (PHP — Slim + Ratchet)

- Instalar dependências (no diretório `backend`):

```bash
cd backend
composer install
cp .env.example .env
# Edite .env com as credenciais MySQL e o JWT_SECRET
```

- Criar o banco e tabelas:

```bash
mysql -u root -p < backend/sql/schema.sql
```

- Iniciar o servidor HTTP (desenvolvimento):

```bash
php -S 0.0.0.0:8000 -t public
```

- Iniciar o servidor WebSocket (Ratchet):

```bash
php backend/ws-server.php
```

Observação: o `ws-server.php` abre um WebSocket em `ws://0.0.0.0:8080`.

2) Frontend (React)

- Instalar e iniciar o frontend (diretório `frontend`):

```bash
cd frontend
npm install
npm start
```

3) Fluxo de mensagens (conceito)

- O frontend carrega histórico via REST (`GET /api/conversations/{id}/messages`).
- Quando o usuário envia uma mensagem:
	- Persiste via REST (`POST /api/messages`).
	- Envia via WebSocket para entrega em tempo real para participantes.
- O servidor WebSocket transmite a mensagem para clientes conectados e, se o chatbot estiver habilitado para a conversa, gera uma resposta automática e a devolve via WS.

4) Documentação e arquivos úteis

- `openapi.json`: especificação básica das rotas REST (registro, login, conversas, histórico, messages).
- `backend/sql/schema.sql`: tabelas: `users`, `conversations`, `conversation_users`, `messages`, `conversation_bot_settings`.

5) Recomendações de segurança

- Use HTTPS / WSS em produção.
- Proteja rotas REST com JWT; no WebSocket valide o token no handshake. Recomendado: envie o JWT como subprotocol (segunda opção do construtor `new WebSocket(url, token)`) em vez de querystring.
- Não exponha a chave da API de IA no frontend; mantenha em `.env` do backend.
- Implemente rate limiting para endpoints que chamam APIs de IA.

6) Próximos passos possíveis

- Adicionar controle fino de quem está inscrito em cada conversa no servidor WebSocket (canal por conversation_id).
- Usar autenticação robusta para validar token JWT no handshake do WS.
- Persistir mensagens do bot automaticamente no banco quando o bot enviar resposta.

Arquivos chave (exemplos):

- `backend/src/Api.php` — rotas REST (register/login/conversations/messages/bot)
- `backend/ws-server.php` — servidor Ratchet que registra `App\\WebSocket\\ChatServer`
- `backend/src/WebSocket/ChatServer.php` — handler WS (onOpen/onMessage/onClose)
- `backend/src/Bot/ChatBot.php` — integração com API de IA (OpenAI) ou heurística
- `frontend/src/components/ChatWindow.js` — exemplo de uso WS + persistência REST

Se quiser, eu posso:
- Adicionar validação do JWT no handshake WebSocket (ex.: validar `?token=`) e exemplo de código.
- Gerar um `docker-compose` apenas para facilitar testes (mas você pediu sem Docker).
- Implementar listagem de conversas e telas de autenticação completas no frontend.

--------
Feito por um assistente — se quiser que eu aprofunde em alguma parte (ex.: validar JWT no WS, end-to-end local), diga qual e eu continuo.