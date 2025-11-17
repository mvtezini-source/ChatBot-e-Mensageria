<?php
namespace App\Bot;

use GuzzleHttp\Client;

class ChatBot {
    private $settings;
    public function __construct($settings){
        $this->settings = $settings;
    }

    public function process($message, $conversationId){
        if ($this->settings['bot_mode'] === 'off') return null;
        if ($this->settings['bot_mode'] === 'heuristic'){
            // Simple heuristic: echo or keyword-based
            if (stripos($message,'horário') !== false) return 'Meu horário de atendimento é 9h-18h.';
            return 'Bot (heuristic): ' . substr($message,0,140);
        }
        // AI mode: call OpenAI (example)
        if (!$this->settings['openai_key']){
            return 'Bot: sem API configurada';
        }
        $client = new Client(['base_uri'=>'https://api.openai.com/']);
        try{
            $resp = $client->post('v1/chat/completions',[
                'headers'=>['Authorization'=>'Bearer '.$this->settings['openai_key'],'Content-Type'=>'application/json'],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [[ 'role'=>'user','content'=>$message ]],
                    'max_tokens' => 200
                ]
            ]);
            $data = json_decode($resp->getBody()->getContents(), true);
            return $data['choices'][0]['message']['content'] ?? null;
        }catch(\Exception $e){
            return 'Bot: erro ao chamar serviço de IA';
        }
    }
}
