<?php
namespace App\Domain\Ai;

interface AiClient
{
    /** @throws \Illuminate\Http\Client\RequestException|\Illuminate\Http\Client\ConnectionException */
    public function generate(AiConnection $connection, string $prompt): string;
}