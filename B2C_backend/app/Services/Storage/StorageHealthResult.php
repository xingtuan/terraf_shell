<?php

namespace App\Services\Storage;

class StorageHealthResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly array $context = [],
    ) {}

    public static function ok(string $message, array $context = []): self
    {
        return new self(true, $message, $context);
    }

    public static function fail(string $message, array $context = []): self
    {
        return new self(false, $message, $context);
    }

    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
