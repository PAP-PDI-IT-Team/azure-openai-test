<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenAI;
use OpenAI\Transporters\GuzzleTransporter;  
use GuzzleHttp\Client as GuzzleClient;  


class AiController extends Controller
{
    public function chat(Request $request){
        $key = config('app.azure_key');
        $resource = config('app.azure_resource');
        $deployment_id = config('app.azure_deployment_id');
        $ai_version = config('app.azure_ai_version');

        $prompt = $request->post('prompt');

        $client = new GuzzleClient([
            'base_uri' => "https://{$resource}.openai.azure.com/openai/deployments/{$deployment_id}/chat/completions?api-version={$ai_version}",
            'headers' => [
                'api-key' => $key,
                'Content-Type' => 'application/json'
            ]
        ]);
        
        try {
            $response = $client->post('', [
                'json' => [
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ]
                ]
            ]);

            $result = json_decode($response->getBody(), true);

            $message = $result['choices'][0]['message']['content'] ?? 'No message found';

            logger($message);
            return response()->json(['data' => $message]);
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function thread(Request $request){
        $key = config('app.azure_key');
        $resource = config('app.azure_resource');
        $deployment_id = config('app.azure_deployment_id');
        $ai_version = config('app.azure_ai_version');

        // $assistantId = $request->post('assistant_id');
        $assistantId = 'asst_Y2wli5vbneJrdbYT8XKYKLxH';
        $prompt = $request->post('prompt');

        // $baseUri = "https://{$resource}.openai.azure.com/openai/deployments/{$deployment_id}/chat/completions?api-version={$ai_version}";
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
                    ['role' => 'user', 'content' => 'Hi.'],
                ],
            ]); 
            logger($result['choices'][0]['message']['content']);
            return response()->json(['message' => $result['choices'][0]['message']['content']]);
        } catch(\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
        // logger($baseUri);

        
        // $client = OpenAI::factory()
        // ->withBaseUri("https://{$resource}.openai.azure.com/")
        // ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 300, 'connect_timeout' => 300]))
        // ->withHttpHeader('api-key', $key)
        // ->withQueryParam('api-version', $ai_version)
        // ->make();

        // $createThread= Http::withHeaders([
        //     'Content-Type' => 'application/json',
        //     'api-key' => $key, 
        // ])->post("https://{$resource}.openai.azure.com/openai/threads?api-version=2024-05-01-preview", [])
        //     ->json();

        // if ($createThread) {
        //     logger('Thread created');
        //     $threadId = $createThread['id'] ?? null;

        //     $message = Http::withHeaders([
        //         'Content-Type' => 'application/json',
        //         'api-key' => $key, 
        //     ])->post("https://{$resource}.openai.azure.com/openai/threads/{$threadId}/messages?api-version=2024-05-01-preview", [
        //         'role' => 'user',
        //         'content' => $prompt, 
        //     ])->json();

        //     $runThread = Http::withHeaders([
        //         'Content-Type' => 'application/json',
        //         'api-key' => $key, 
        //     ])->post("https://{$resource}.openai.azure.com/openai/threads/{$threadId}/runs?api-version=2024-05-01-preview", [
        //         'assistant_id' => $assistantId,
        //     ])->json();

        //     logger($runThread);

        //     if($runThread) {
        //         $runId = $runThread['id'] ?? null;
        //         $statusThread = Http::withHeaders([
        //             'Content-Type' => 'application/json',
        //             'api-key' => $key, ])->post("https://{$resource}.openai.azure.com/openai/threads/${threadId}/runs/${runId}?api-version=2024-05-01-preview", [])
        //             ->json();
        //         logger($statusThread);
        //     }


        // } else {
        //     logger('Failed to create thread');
        // }

        // $client = new GuzzleClient([
        //     'base_uri' => "https://{$resource}.openai.azure.com/openai/assistants?api-version=2024-05-01-preview",
        //     'headers' => [
        //         'api-key' => $key,
        //         'Content-Type' => 'application/json'
        //     ]
        // ]);

        // $thread_response =  $openAI::threads()->create([]);
        // $thread = $thread_response->toArray(); 
        // $message_response =  $openAI::threads()->messages()->create($thread['id'], [
        //     'role' => 'user',
        //     'content' =>  $prompt, 
        // ]);
        // $message = $message_response->toArray(); 
       
        // $run_response =  $openAI::threads()->runs()->create(
        //     threadId: $thread['id'], 
        //     parameters: [
        //         'assistant_id' => 'asst_Y2wli5vbneJrdbYT8XKYKLxH',
        //     ],
        // );
        // $run = $run_response->toArray();

        // return response()->json([
        //     'thread_id' => $thread['id'], 
        //     'message_id' => $message['id'], 
        //     'run_id' => $run['id']
        // ], 200);
    }
   
}
