<?php

namespace App\Domain\Ai\Clients;

use App\Domain\Ai\{AiClient, AiConnection};
use Illuminate\Support\Facades\Http;

final class ClaudeClient implements AiClient
{
    public function generate(AiConnection $c, string $prompt): string
    {
        $response = Http::withHeaders([
                'x-api-key'         => $c->apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->timeout(120)->throw()
            ->post("{$c->endpoint}/messages", [
                'model'      => $c->model,
                'max_tokens' => 4096,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]);

        return (string) $response->json('content.0.text');
    }
}