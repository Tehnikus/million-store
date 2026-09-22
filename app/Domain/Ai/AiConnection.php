<?php
namespace App\Domain\Ai;

final readonly class AiConnection
{
    public function __construct(
        public string $apiKey,
        public string $endpoint,
        public string $model,
    ) {}

    public static function fromSettings(array $item): self
    {
        // Later add api key encrypt/decrypt here
        return new self($item['api_key'], rtrim($item['endpoint'], '/'), $item['model']);
    }
}