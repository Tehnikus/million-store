<?php

namespace App\Domain\Ai\Clients;

use App\Domain\Ai\{AiClient, AiConnection};
use Illuminate\Support\Facades\Http;

final class OpenAiClient implements AiClient
{
    public function generate(AiConnection $c, string $prompt): string
    {
        $response = Http::withToken($c->apiKey)
            ->timeout(120)->throw()
            ->post("{$c->endpoint}/chat/completions", [
                'model'    => $c->model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

        return (string) $response->json('choices.0.message.content');
    }
}