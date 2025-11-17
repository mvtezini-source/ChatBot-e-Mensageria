<?php
/**
 * SQLite Database Setup (Fallback para MySQL)
 * Cria um banco SQLite local para teste sem MySQL instalado
 */

$dbPath = __DIR__ . '/chat.db';

// Remover BD anterior se existir (para reset)
if (file_exists($dbPath) && isset($argv[1]) && $argv[1] === '--reset') {
    unlink($dbPath);
    echo "[Setup] Banco anterior removido.\n";
}

// Criar/conectar ao banco SQLite
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "[Setup] Conectado ao SQLite em: $dbPath\n";
} catch (Exception $e) {
    die("[ERRO] Falha ao conectar SQLite: " . $e->getMessage() . "\n");
}

// Criar tabelas
echo "[Setup] Criando tabelas...\n";

$pdo->exec('
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
');

$pdo->exec('
CREATE TABLE IF NOT EXISTS conversations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT,
  is_group INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
');

$pdo->exec('
CREATE TABLE IF NOT EXISTS conversation_users (
  conversation_id INTEGER,
  user_id INTEGER,
  PRIMARY KEY(conversation_id, user_id)
)
');

$pdo->exec('
CREATE TABLE IF NOT EXISTS messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  conversation_id INTEGER NOT NULL,
  user_id INTEGER,
  content TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
');

$pdo->exec('
CREATE TABLE IF NOT EXISTS conversation_bot_settings (
  conversation_id INTEGER PRIMARY KEY,
  bot_enabled INTEGER DEFAULT 1
)
');

echo "  ✓ Tabelas criadas/verificadas.\n";

// Criar usuários de teste
echo "[Setup] Criando usuários de teste...\n";

$testUsers = [
    ['name' => 'Alice', 'email' => 'alice@test.local', 'password' => 'password123'],
    ['name' => 'Bob', 'email' => 'bob@test.local', 'password' => 'password123'],
];

$userIds = [];
foreach ($testUsers as $u) {
    try {
        $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)');
        $hash = password_hash($u['password'], PASSWORD_DEFAULT);
        $stmt->execute([$u['name'], $u['email'], $hash]);
        $id = $pdo->lastInsertId();
        $userIds[$u['email']] = $id;
        echo "  ✓ Usuário '{$u['name']}' (ID: $id) criado.\n";
    } catch (PDOException $e) {
        echo "  ⊘ Usuário {$u['email']} já existe.\n";
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$u['email']]);
        $row = $stmt->fetch();
        if ($row) $userIds[$u['email']] = $row['id'];
    }
}

// Criar conversa de teste
echo "[Setup] Criando conversas de teste...\n";

$testConversations = [
    ['title' => 'Chat com Bot', 'is_group' => 0],
    ['title' => 'Grupo Geral', 'is_group' => 1],
];

$conversationIds = [];
foreach ($testConversations as $c) {
    try {
        $stmt = $pdo->prepare('INSERT INTO conversations (title,is_group) VALUES (?,?)');
        $stmt->execute([$c['title'], $c['is_group']]);
        $id = $pdo->lastInsertId();
        $conversationIds[$c['title']] = $id;
        echo "  ✓ Conversa '{$c['title']}' (ID: $id) criada.\n";
    } catch (PDOException $e) {
        echo "  ⊘ Conversa já existe.\n";
    }
}

// Adicionar memberships
echo "[Setup] Adicionando usuários às conversas...\n";

$membershipMap = [
    'Chat com Bot' => ['alice@test.local', 'bob@test.local'],
    'Grupo Geral' => ['alice@test.local', 'bob@test.local'],
];

foreach ($membershipMap as $convTitle => $emails) {
    $convId = $conversationIds[$convTitle] ?? null;
    if (!$convId) continue;
    foreach ($emails as $email) {
        $userId = $userIds[$email] ?? null;
        if (!$userId) continue;
        try {
            $stmt = $pdo->prepare('INSERT INTO conversation_users (conversation_id,user_id) VALUES (?,?)');
            $stmt->execute([$convId, $userId]);
            echo "  ✓ {$email} adicionado(a) a '{$convTitle}'.\n";
        } catch (PDOException $e) {
            echo "  ⊘ {$email} já era membro.\n";
        }
    }
}

// Ativar bot
echo "[Setup] Ativando bot para conversas...\n";

foreach ($conversationIds as $id) {
    try {
        $stmt = $pdo->prepare('INSERT INTO conversation_bot_settings (conversation_id, bot_enabled) VALUES (?,?)');
        $stmt->execute([$id, 1]);
        echo "  ✓ Bot ativado para conversa $id.\n";
    } catch (PDOException $e) {
        echo "  ⊘ Bot já estava configurado.\n";
    }
}

// Salvar IDs em arquivo para uso posterior
file_put_contents(__DIR__ . '/test-data.json', json_encode([
    'users' => $userIds,
    'conversations' => $conversationIds,
    'test_credentials' => [
        'email' => 'alice@test.local',
        'password' => 'password123'
    ]
], JSON_PRETTY_PRINT));

echo "\n" . str_repeat("=", 60) . "\n";
echo "[SUCESSO] Setup SQLite completo!\n";
echo str_repeat("=", 60) . "\n\n";

echo "Credenciais de teste:\n";
echo "  Email: alice@test.local\n";
echo "  Senha: password123\n\n";

echo "Conversas:\n";
foreach ($conversationIds as $title => $id) {
    echo "  '$title' (ID: $id)\n";
}

echo "\nBanco de dados: $dbPath\n";
?>
