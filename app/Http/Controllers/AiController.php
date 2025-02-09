<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenAI;

class AiController extends Controller
{

    public function chat(Request $request){
        $key = config('app.azure_key');
        $resource = config('app.azure_resource');
        $deployment_id = config('app.azure_deployment_id');
        $ai_version = config('app.azure_ai_version');

        $prompt = $request->post('prompt');

        $baseUri = "https://{$resource}.openai.azure.com/openai/deployments/{$deployment_id}";
        try {
            $client = OpenAI::factory()
            ->withBaseUri($baseUri)
            ->withHttpHeader('api-key', $key)
            ->withQueryParam('api-version', $ai_version)
            ->make();
            // ->withApiKey($key)
            
            $result = $client->chat()->create([
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]); 
            logger($result['choices'][0]['message']['content']);
            return response()->json(['message' => $result['choices'][0]['message']['content']]);
        } catch(\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function thread(Request $request){
        $key = config('app.azure_key');
        $resource = config('app.azure_resource');
        $deployment_id = config('app.azure_deployment_id');
        $ai_version = config('app.azure_ai_version');

        // $assistantId = $request->post('assistant_id');
        $assistantId = 'asst_xVM002Vbb72Rb32i5LDc8fgc';
        $prompt = $request->post('prompt');

        // $baseUri = "https://{$resource}.openai.azure.com/openai/threads?api-version=2024-05-01-preview";
        $baseUri = "https://azure-test-ai-rigel.openai.azure.com/";
        logger($baseUri);
        try {
             $createThread= Http::withHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $key, 
                ])->post("https://{$resource}.openai.azure.com/openai/threads?api-version=2024-05-01-preview", [])
                ->json();

            if ($createThread) {
                logger('Thread created');
                $threadId = $createThread['id'] ?? null;
                
                $response = $this->sendThreadMessage($key, $resource, $threadId, $prompt);
                $messageId = $response['id'];
                logger($messageId);
                
                $run = $this->runThread($key, $resource, $threadId, $assistantId);

                if($run === 'completed') {
                    $threadMessage = $this->getThreadMessages($key, $resource, $threadId, $messageId);
                    $message = $threadMessage['data'][0]['content'][0]['text']['value'];
                    logger($message);
                }
                return response()->json(['message' => $message], 200);
            }
        
        } catch(\Exception $error) {
            logger($error);
            return response()->json(['error' => $error->getMessage()], 500);
        }

    }

    private function sendThreadMessage($key, $resource, $threadId, $message, ) {
        $message = Http::withHeaders([
            'Content-Type' => 'application/json',
            'api-key' => $key, 
        ])->post("https://{$resource}.openai.azure.com/openai/threads/{$threadId}/messages?api-version=2024-05-01-preview", [
            'role' => 'user',
            'content' => $message, 
        ])->json();
        return $message;
    }

    private function runThread($key, $resource, $threadId, $assistantId) {
        logger('Running thread');
        $runThread = Http::withHeaders([
            'Content-Type' => 'application/json',
            'api-key' => $key, 
        ])->post("https://{$resource}.openai.azure.com/openai/threads/{$threadId}/runs?api-version=2024-05-01-preview", [
            'assistant_id' => $assistantId,
        ])->json();

        logger('run1:', ['id' => $runThread['id']]);

        if($runThread) {
            $runId = $runThread['id'] ?? null;
            logger('run2:', ['id' => $runThread['id']]);
            $status = 'queued';

            while($status !== 'completed') {
                $status = $this->checkThreadStatus($key, $resource, $threadId, $runId);
                sleep(5);
            }
            logger("Thread run has completed successfully.");
            return $status;
        }

    }

    private function checkThreadStatus($key, $resource, $threadId, $runId) {
        logger('Checking thread status');
        $statusThread = Http::withHeaders([
            'api-key' => $key, ])->post("https://{$resource}.openai.azure.com/openai/threads/{$threadId}/runs/{$runId}?api-version=2024-05-01-preview", [])
            ->json();
        return $statusThread['status'] ?? 'in_progress';
    }

    private function getThreadMessages($key, $resource, $threadId, $messageId) {
        logger('Getting thread messages');

        $messages = Http::withHeaders([
            'Content-Type' => 'application/json',
            'api-key' => $key, ])->get("https://{$resource}.openai.azure.com/openai/threads/{$threadId}/messages?api-version=2024-05-01-preview")
            ->json();
        
        return $messages;
    }

    public function streamThread(Request $request){
        $key = config('app.azure_key');
        $resource = config('app.azure_resource');
        $deployment_id = config('app.azure_deployment_id');
        $ai_version = config('app.azure_ai_version');

        // $assistantId = $request->post('assistant_id');
        $assistantId = 'asst_xVM002Vbb72Rb32i5LDc8fgc';
        $prompt = $request->post('prompt');

        $baseUri = "https://azure-test-ai-rigel.openai.azure.com/";
        logger($baseUri);
        try {
            $response = Http::withHeaders([
                'api-key' => $key, 'Content-Type' => 'application/json', ])->post("https://$resource.openai.azure.com/openai/threads/runs?api-version=2024-05-01-preview", [
                    "thread" => [
                        "messages" => [
                          ["role" => "user", "content" => $prompt]
                        ],
                    ],
                    'assistant_id' => $assistantId,
                    'stream' => true
                    
                ]); 
        
            $body = $response->getBody();
            $buffer = '';
            $finalResponse = '';
        
            while (!$body->eof()) {
                $buffer .= $body->read(1024);
                
                while (($pos = strpos($buffer, "\n\n")) !== false) {
                    $event = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 2);
        
                    if (preg_match('/data: (\{.*\})/', $event, $matches)) {
                        $data = json_decode($matches[1], true);
                        
                        if (isset($data['delta']['content'][0]['text']['value'])) {
                            $chunk = $data['delta']['content'][0]['text']['value'];
                            $finalResponse .= $chunk;
                            
                            logger('Stream chunk:', ['content' => $chunk]);
                            
                            echo $chunk;
                            ob_flush();
                            flush();
                        }
                    }
                }
            }
            // return response()->make('');
        
        } catch(\Exception $error) {
            logger($error);
            return response()->json(['error' => $error->getMessage()], 500);
        }

    }

    public function streamChat(Request $request){
        $key = config('app.azure_key');
        $resource = config('app.azure_resource');
        $deployment_id = config('app.azure_deployment_id');
        $ai_version = config('app.azure_ai_version');

        $prompt = $request->post('prompt');

        $baseUri = "https://{$resource}.openai.azure.com/openai/deployments/{$deployment_id}";
        try {
            $client = OpenAI::factory()
            ->withBaseUri($baseUri)
            ->withHttpHeader('api-key', $key)
            ->withQueryParam('api-version', $ai_version)
            ->make();
            // ->withApiKey($key)
            
            $result = $client->chat()->createStreamed([
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'stream' => true
            ]);
            
            $finalResponse = ''; 
            foreach ($result as $chunk) {

                $content = $chunk['choices'][0]['delta']['content'] ?? '';
                
                if (!empty($content)) {
                    $finalResponse .= $content;
                    
                    logger('Stream chunk received:', ['content' => $content]);

                    echo $content;
                    ob_flush();
                    flush();
                }
            }

            // return response()->make('');
            
        } catch(\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

   
}
