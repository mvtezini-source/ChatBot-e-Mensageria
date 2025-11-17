<?php
namespace App;

use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;

require __DIR__ . '/../vendor/autoload.php';

$settings = require __DIR__ . '/settings.php';
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Simple CORS for dev
$app->add(function (Request $request, RequestHandler $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
});

function respondJson(Response $res, $data, $status=200){
    $res->getBody()->write(json_encode($data));
    return $res->withHeader('Content-Type','application/json')->withStatus($status);
}

// Register user
$app->post('/api/register', function(Request $req, Response $res){
    $body = $req->getParsedBody();
    $settings = require __DIR__ . '/settings.php';
    $pdo = Db::getConnection($settings);
    $stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)');
    $hash = password_hash($body['password'], PASSWORD_DEFAULT);
    $stmt->execute([$body['name'],$body['email'],$hash]);
    return respondJson($res,['ok'=>true]);
});

// Login -> return JWT
$app->post('/api/login', function(Request $req, Response $res){
    $body = $req->getParsedBody();
    $settings = require __DIR__ . '/settings.php';
    $pdo = Db::getConnection($settings);
    $stmt = $pdo->prepare('SELECT id,password_hash FROM users WHERE email=?');
    $stmt->execute([$body['email']]);
    $u = $stmt->fetch();
    if (!$u || !password_verify($body['password'],$u['password_hash'])){
        return respondJson($res,['error'=>'invalid_credentials'],401);
    }
    $payload = ['sub'=>$u['id'],'iat'=>time(),'exp'=>time()+3600*24];
    $jwt = JWT::encode($payload, $settings['jwt_secret'], 'HS256');
    return respondJson($res,['token'=>$jwt]);
});

// Middleware to validate JWT
$authMiddleware = function(Request $req, RequestHandler $handler) use ($settings){
    $auth = $req->getHeaderLine('Authorization');
    if (!$auth || !preg_match('/^Bearer\s+(.*)$/', $auth, $m)){
        $res = new \Slim\Psr7\Response();
        $res->getBody()->write(json_encode(['error'=>'unauthenticated']));
        return $res->withHeader('Content-Type','application/json')->withStatus(401);
    }
    try{
        $token = $m[1];
        $decoded = JWT::decode($token, new Key($settings['jwt_secret'], 'HS256'));
        $req = $req->withAttribute('user_id', $decoded->sub);
        return $handler->handle($req);
    }catch(\Exception $e){
        $res = new \Slim\Psr7\Response();
        $res->getBody()->write(json_encode(['error'=>'invalid_token','message'=>$e->getMessage()]));
        return $res->withHeader('Content-Type','application/json')->withStatus(401);
    }
};

// List conversations
$app->get('/api/conversations', function(Request $req, Response $res){
    $settings = require __DIR__ . '/settings.php';
    $pdo = Db::getConnection($settings);
    $userId = $req->getAttribute('user_id');
    $stmt = $pdo->prepare('SELECT c.* FROM conversations c JOIN conversation_users cu ON cu.conversation_id = c.id WHERE cu.user_id = ?');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();
    return respondJson($res,$rows);
})->add($authMiddleware);

// Conversation messages (history)
$app->get('/api/conversations/{id}/messages', function(Request $req, Response $res, $args){
    $id = $args['id'];
    $settings = require __DIR__ . '/settings.php';
    $pdo = Db::getConnection($settings);
    $userId = $req->getAttribute('user_id');
    // verify membership
    $stmtCheck = $pdo->prepare('SELECT 1 FROM conversation_users WHERE conversation_id = ? AND user_id = ? LIMIT 1');
    $stmtCheck->execute([$id, $userId]);
    if (!$stmtCheck->fetch()){
        return respondJson($res,['error'=>'forbidden'],403);
    }
    $stmt = $pdo->prepare('SELECT m.*, u.name as author_name FROM messages m LEFT JOIN users u ON u.id = m.user_id WHERE m.conversation_id = ? ORDER BY m.created_at ASC');
    $stmt->execute([$id]);
    $rows = $stmt->fetchAll();
    return respondJson($res,$rows);
})->add($authMiddleware);

// Save message (persist via REST). Expects: conversation_id, content
$app->post('/api/messages', function(Request $req, Response $res){
    $body = $req->getParsedBody();
    $settings = require __DIR__ . '/settings.php';
    $pdo = Db::getConnection($settings);
    $userId = $req->getAttribute('user_id');
    // Check membership before allowing to post
    $stmtCheck = $pdo->prepare('SELECT 1 FROM conversation_users WHERE conversation_id = ? AND user_id = ? LIMIT 1');
    $stmtCheck->execute([$body['conversation_id'], $userId]);
    if (!$stmtCheck->fetch()){
        return respondJson($res,['error'=>'forbidden'],403);
    }
    $stmt = $pdo->prepare('INSERT INTO messages (conversation_id,user_id,content,created_at) VALUES (?,?,?,NOW())');
    $stmt->execute([$body['conversation_id'],$userId,$body['content']]);
    $id = $pdo->lastInsertId();
    return respondJson($res,['id'=>$id]);
})->add($authMiddleware);

// Bot trigger endpoint (optional manual trigger)
$app->post('/api/bot/respond', function(Request $req, Response $res){
    $body = $req->getParsedBody();
    $bot = new Bot\ChatBot(require __DIR__ . '/settings.php');
    $resp = $bot->process($body['content'], $body['conversation_id']);
    return respondJson($res,['reply'=>$resp]);
})->add($authMiddleware);

return $app;
