<?php

namespace App\Domain\Ai\Clients;

use App\Domain\Ai\{AiClient, AiConnection};
use Illuminate\Support\Facades\Http;

final class GeminiClient implements AiClient
{
    public function generate(AiConnection $c, string $prompt): string
    {
        $response = Http::withHeaders(['x-goog-api-key' => $c->apiKey])
            ->timeout(120)->throw()
            ->post("{$c->endpoint}/models/{$c->model}:generateContent", [
                'contents' => [['parts' => [['text' => $prompt]]]],
            ]);

        return (string) $response->json('candidates.0.content.parts.0.text');
    }
}