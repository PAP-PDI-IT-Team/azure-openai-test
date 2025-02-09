<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    protected $key;
    protected $resource;
    protected $deploymentId;
    protected $aiVersion;

    public function __construct()
    {
        $this->key = config('app.azure_key');
        $this->resource = config('app.azure_resource');
        $this->deploymentId = config('app.azure_deployment_id');
        $this->aiVersion = config('app.azure_ai_version');
    }

    
    public function createAssistant($name, $instructions, $model)
    {
        $url = "https://{$this->resource}.openai.azure.com/openai/assistants?api-version={$this->aiVersion}";

        $response = Http::withHeaders([
            'api-key' => $this->key,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'name' => $name,
            'instructions' => $instructions,
            'model' => $model,
        ]);

        return $response->json();
    }

    // Create Thread
    public function createThread($messages = [])
    {
        $url = "https://{$this->resource}/openai/threads?api-version={$this->aiVersion}";

        $response = Http::withHeaders([
            'api-key' => $this->key,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'messages' => $messages,
        ]);

        if ($response->failed()) {
            return [
                'error' => $response->json(),
                'status' => $response->status(),
            ];
        }

        return $response->json();
    }

    // Run Thread
    public function runThread($assistantId, $threadId)
    {
        $url = "https://{$this->resource}/openai/threads/{$threadId}/runs?api-version={$this->aiVersion}";

        $response = Http::withHeaders([
            'api-key' => $this->key,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'assistant_id' => $assistantId,
        ]);

        if ($response->failed()) {
            return [
                'error' => $response->json(),
                'status' => $response->status(),
            ];
        }

        return $response->json();
    }

    // Check Run Status
    public function getRunStatus($threadId, $runId)
    {
        $url = "https://{$this->resource}/openai/threads/{$threadId}/runs/{$runId}?api-version={$this->aiVersion}";

        $response = Http::withHeaders([
            'api-key' => $this->key,
        ])->get($url);

        return $response->json();
    }

    // Get Thread Messages
    public function getThreadMessages($threadId)
    {
        $url = "https://{$this->resource}/openai/threads/{$threadId}/messages?api-version={$this->aiVersion}";

        $response = Http::withHeaders([
            'api-key' => $this->key,
        ])->get($url);

        return $response->json();
    }
    
    public function streamChatAssistant($prompt, $assistantId)
    {
        $baseUri = "https://{$this->resource}.openai.azure.com/openai/threads/runs?api-version={$this->aiVersion}";

        $response = Http::withHeaders([
            'api-key' => $this->key,
            'Content-Type' => 'application/json',
        ])->post($baseUri, [
            "thread" => [
                "messages" => [
                    ["role" => "user", "content" => $prompt]
                ],
            ],
            "response_format" => ["type" => "json_object"],
            'assistant_id' => $assistantId,
            'stream' => true
        ]);

        return $response->getBody();
    }
}