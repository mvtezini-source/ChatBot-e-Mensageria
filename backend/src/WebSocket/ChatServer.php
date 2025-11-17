<?php
namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Bot\ChatBot;
use App\Db;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ChatServer implements MessageComponentInterface {
    protected $clients; // SplObjectStorage
    protected $settings;

    public function __construct($settings){
        $this->clients = new \SplObjectStorage;
        $this->settings = $settings;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Prefer token via Sec-WebSocket-Protocol (subprotocol) for browser WS.
        $protocols = $conn->httpRequest->getHeader('Sec-WebSocket-Protocol');
        $token = null;
        if (!empty($protocols) && is_array($protocols)){
            // header may contain a comma-separated list in a single element
            $p = $protocols[0];
            // if multiple, take the first
            $parts = array_map('trim', explode(',', $p));
            $token = $parts[0] ?? null;
        }
        // fallback to query param token (deprecated)
        if (!$token){
            parse_str($conn->httpRequest->getUri()->getQuery(), $qs);
            $token = $qs['token'] ?? null;
        }

        if (!$token) {
            $conn->send(json_encode(['type'=>'error','message'=>'no_token']));
            $conn->close();
            return;
        }
        try {
            $decoded = JWT::decode($token, new Key($this->settings['jwt_secret'], 'HS256'));
            $conn->user_id = $decoded->sub ?? null;
        } catch (\Exception $e) {
            $conn->send(json_encode(['type'=>'error','message'=>'invalid_token']));
            $conn->close();
            return;
        }

        // initialize subscriptions (conversation ids the client wants to receive)
        $conn->subscriptions = [];
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!$data) return;

        // handle subscription messages: { type: 'subscribe', conversation_id }
        if (isset($data['type']) && $data['type'] === 'subscribe'){
            $cid = (int)($data['conversation_id'] ?? 0);
            if ($cid) {
                if (!in_array($cid, $from->subscriptions)) $from->subscriptions[] = $cid;
                $from->send(json_encode(['type'=>'subscribed','conversation_id'=>$cid]));
            }
            return;
        }

        // handle outgoing chat message: { type: 'message', conversation_id, content }
        if (isset($data['type']) && $data['type'] === 'message'){
            $conversationId = (int)$data['conversation_id'];
            $userId = $from->user_id ?? null;
            if (!$userId) {
                $from->send(json_encode(['type'=>'error','message'=>'unauthenticated']));
                return;
            }

            // verify user is member of conversation
            $pdo = Db::getConnection($this->settings);
            $stmt = $pdo->prepare('SELECT 1 FROM conversation_users WHERE conversation_id = ? AND user_id = ? LIMIT 1');
            $stmt->execute([$conversationId, $userId]);
            if (!$stmt->fetch()){
                $from->send(json_encode(['type'=>'error','message'=>'not_in_conversation']));
                return;
            }

            // Broadcast only to clients subscribed to this conversation
            $payload = ['type'=>'message','conversation_id'=>$conversationId,'from'=>$data['from'] ?? $userId,'content'=>$data['content']];
            foreach ($this->clients as $client) {
                if (!empty($client->subscriptions) && in_array($conversationId, $client->subscriptions)){
                    $client->send(json_encode($payload));
                }
            }

            // Check bot enabled for conversation
            $stmt2 = $pdo->prepare('SELECT bot_enabled FROM conversation_bot_settings WHERE conversation_id = ? LIMIT 1');
            $stmt2->execute([$conversationId]);
            $bs = $stmt2->fetch();
            $botEnabled = $bs ? (bool)$bs['bot_enabled'] : true;

            if ($botEnabled){
                $bot = new ChatBot($this->settings);
                $botReply = $bot->process($data['content'], $conversationId);
                if ($botReply) {
                    // persist bot message to DB using bot user id if available
                    $botUserId = null;
                    try{
                        $stmtBot = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                        $stmtBot->execute(['bot@local']);
                        $botRow = $stmtBot->fetch();
                        if ($botRow) $botUserId = $botRow['id'];
                    }catch(\Exception $e){
                        $botUserId = null;
                    }
                    $stmtIns = $pdo->prepare('INSERT INTO messages (conversation_id,user_id,content,created_at) VALUES (?,?,?,NOW())');
                    $stmtIns->execute([$conversationId, $botUserId, $botReply]);

                    $botPayload = ['type'=>'message','conversation_id'=>$conversationId,'from'=>'bot','content'=>$botReply];
                    foreach ($this->clients as $client) {
                        if (!empty($client->subscriptions) && in_array($conversationId, $client->subscriptions)){
                            $client->send(json_encode($botPayload));
                        }
                    }
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}
