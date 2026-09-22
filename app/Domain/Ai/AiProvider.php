<?php

namespace App\Domain\Ai;

use App\Domain\Ai\Clients\{ClaudeClient, GeminiClient, OpenAiClient};
use Filament\Support\Contracts\HasLabel;

enum AiProvider: string implements HasLabel
{
    case Gemini = 'gemini';
    case OpenAi = 'openai';
    case Claude = 'claude';

    public function getLabel(): string
    {
        return match ($this) {
            self::Gemini => 'Google Gemini',
            self::OpenAi => 'ChatGPT',
            self::Claude => 'Claude',
        };
    }

    /** Base default URL. Can be changed in StoreSettings */
    public function defaultEndpoint(): string
    {
        return match ($this) {
            self::Gemini => 'https://generativelanguage.googleapis.com/v1beta',
            self::OpenAi => 'https://api.openai.com/v1',
            self::Claude => 'https://api.anthropic.com/v1',
        };
    }

    public static function fromState(mixed $state): ?self
    {
        return $state instanceof self ? $state : self::tryFrom((string) $state);
    }

    public static function isDefaultEndpoint(string $url): bool
    {
        return collect(self::cases())
            ->contains(fn (self $p) => $p->defaultEndpoint() === rtrim($url, '/'));
    }

    public function client(): AiClient
    {
        return match ($this) {
            self::Gemini => new GeminiClient(),
            self::OpenAi => new OpenAiClient(),
            self::Claude => new ClaudeClient(),
        };
    }
}