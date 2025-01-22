<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI\Client as OpenAI;
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
   
}
