#!/usr/bin/env php
<?php
/**
 * Setup CLI Script
 * Cria usuário de teste, conversa e adiciona membership para teste local rápido.
 * Uso: php setup-test-data.php
 */

require __DIR__ . '/vendor/autoload.php';

$settings = require __DIR__ . '/src/settings.php';

try {
    $pdo = \App\Db::getConnection($settings);
    echo "[Setup] Conectado ao banco de dados.\n";
} catch (\Exception $e) {
    echo "[ERRO] Conexão com banco falhou: " . $e->getMessage() . "\n";
    echo "[INFO] Certifique-se de que MySQL está rodando e .env está configurado corretamente.\n";
    exit(1);
}

// 1. Criar usuário de teste
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
        echo "  ✓ Usuário '{$u['name']}' (ID: $id) criado com email: {$u['email']}\n";
    } catch (\PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "  ⊘ Usuário {$u['email']} já existe (ignorado).\n";
            // recuperar ID
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$u['email']]);
            $row = $stmt->fetch();
            if ($row) $userIds[$u['email']] = $row['id'];
        } else {
            echo "  ✗ Erro ao criar {$u['email']}: " . $e->getMessage() . "\n";
        }
    }
}

// 2. Criar conversa de teste
echo "\n[Setup] Criando conversas de teste...\n";
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
    } catch (\PDOException $e) {
        echo "  ✗ Erro ao criar conversa: " . $e->getMessage() . "\n";
    }
}

// 3. Adicionar usuários às conversas
echo "\n[Setup] Adicionando usuários às conversas...\n";
$membershipMap = [
    'Chat com Bot' => ['alice@test.local', 'bob@test.local'],
    'Grupo Geral' => ['alice@test.local', 'bob@test.local'],
];

foreach ($membershipMap as $convTitle => $emails) {
    $convId = $conversationIds[$convTitle] ?? null;
    if (!$convId) {
        echo "  ⊘ Conversa '$convTitle' não encontrada, pulando memberships.\n";
        continue;
    }
    foreach ($emails as $email) {
        $userId = $userIds[$email] ?? null;
        if (!$userId) {
            echo "  ⊘ Usuário '$email' não encontrado, pulando.\n";
            continue;
        }
        try {
            $stmt = $pdo->prepare('INSERT INTO conversation_users (conversation_id,user_id) VALUES (?,?)');
            $stmt->execute([$convId, $userId]);
            echo "  ✓ {$email} adicionado(a) a '{$convTitle}'.\n";
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "  ⊘ {$email} já era membro de '{$convTitle}'.\n";
            } else {
                echo "  ✗ Erro: " . $e->getMessage() . "\n";
            }
        }
    }
}

// 4. Resumo de credenciais
echo "\n" . str_repeat("=", 60) . "\n";
echo "[SUCESSO] Setup completo!\n";
echo str_repeat("=", 60) . "\n\n";
echo "Credenciais de teste:\n";
foreach ($testUsers as $u) {
    echo "  Email: {$u['email']}\n";
    echo "  Senha: {$u['password']}\n";
    echo "  ID: {$userIds[$u['email']]}\n\n";
}

echo "Conversas criadas:\n";
foreach ($conversationIds as $title => $id) {
    echo "  '{$title}' (ID: $id)\n";
}

echo "\n[PRÓXIMAS ETAPAS]\n";
echo "1. Frontend: npm start (em /frontend)\n";
echo "2. Acessar http://localhost:3000\n";
echo "3. Fazer login com uma das credenciais acima\n";
echo "4. Selecionar uma conversa e enviar uma mensagem\n";
echo "5. O bot responderá automaticamente se habilitado\n\n";
?>
