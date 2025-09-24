<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeApiService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct()
    {
        $this->apiKey = config('services.claude.api_key');
        
        if (empty($this->apiKey)) {
            throw new \Exception('Claude API key not configured');
        }
    }

    public function generateSummary(string $text): string
    {
        $prompt = $this->buildSummaryPrompt($text);

        try {
            $response = Http::withHeaders([
                'anthropic-version' => '2023-06-01',
                'x-api-key' => $this->apiKey,
                'content-type' => 'application/json',
            ])->post($this->apiUrl, [
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 1000,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ]);

            if (!$response->successful()) {
                Log::error('Claude API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Claude API request failed');
            }

            $data = $response->json();
            
            if (!isset($data['content'][0]['text'])) {
                throw new \Exception('Invalid response format from Claude API');
            }

            return $data['content'][0]['text'];

        } catch (\Exception $e) {
            Log::error('Claude API Service Error', [
                'message' => $e->getMessage(),
                'text_length' => strlen($text)
            ]);
            throw $e;
        }
    }

    private function buildSummaryPrompt(string $text): string
    {
        return "以下のテキストの内容を簡潔にまとめてください。重要なポイントや決定事項があれば含めてください。前置きや説明は不要です。内容のみを直接記述してください。

{$text}";
    }
}