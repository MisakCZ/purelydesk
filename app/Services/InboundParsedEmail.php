<?php

namespace App\Services;

class InboundParsedEmail
{
    /**
     * @param  array<string, array<int, string>>  $headers
     * @param  array<int, array<string, mixed>>  $attachments
     */
    public function __construct(
        public readonly array $headers,
        public readonly ?string $messageId,
        public readonly ?string $fromEmail,
        public readonly array $recipientAddresses,
        public readonly string $subject,
        public readonly string $body,
        public readonly array $attachments,
    ) {}

    public function firstHeader(string $name): ?string
    {
        $values = $this->headers[strtolower($name)] ?? [];

        return $values[0] ?? null;
    }
}
