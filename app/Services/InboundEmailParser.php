<?php

namespace App\Services;

use Illuminate\Support\Str;

class InboundEmailParser
{
    public function parse(string $raw): InboundParsedEmail
    {
        [$headerBlock, $bodyBlock] = $this->splitHeaderAndBody($raw);
        $headers = $this->parseHeaders($headerBlock);
        $parts = $this->parsePart($headers, $bodyBlock);
        $textBody = $this->firstBody($parts, 'text/plain');
        $htmlBody = $this->firstBody($parts, 'text/html');

        return new InboundParsedEmail(
            headers: $headers,
            messageId: $this->cleanMessageId($this->firstHeader($headers, 'Message-ID')),
            fromEmail: $this->extractFirstEmail((string) $this->firstHeader($headers, 'From')),
            recipientAddresses: $this->recipientAddresses($headers),
            subject: $this->decodeHeader((string) $this->firstHeader($headers, 'Subject')),
            body: $textBody !== ''
                ? $textBody
                : $this->htmlToText($htmlBody),
            attachments: $this->attachments($parts),
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitHeaderAndBody(string $raw): array
    {
        $raw = str_replace("\r\n", "\n", $raw);
        $position = strpos($raw, "\n\n");

        if ($position === false) {
            return [$raw, ''];
        }

        return [substr($raw, 0, $position), substr($raw, $position + 2)];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function parseHeaders(string $headerBlock): array
    {
        $headers = [];
        $currentName = null;

        foreach (explode("\n", $headerBlock) as $line) {
            if ($line === '') {
                continue;
            }

            if (($line[0] === ' ' || $line[0] === "\t") && $currentName !== null) {
                $lastIndex = array_key_last($headers[$currentName]);
                $headers[$currentName][$lastIndex] .= ' '.trim($line);
                continue;
            }

            if (! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $currentName = strtolower(trim($name));
            $headers[$currentName][] = trim($value);
        }

        return $headers;
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     * @return array<int, array<string, mixed>>
     */
    private function parsePart(array $headers, string $body): array
    {
        $contentType = $this->firstHeader($headers, 'Content-Type') ?: 'text/plain';
        $boundary = $this->headerParameter($contentType, 'boundary');

        if ($boundary !== null && str_contains(Str::lower($contentType), 'multipart/')) {
            $parts = [];
            $segments = preg_split('/--'.preg_quote($boundary, '/').'(--)?\s*\n/', str_replace("\r\n", "\n", $body));

            foreach ($segments ?: [] as $segment) {
                $segment = trim($segment, "\n");

                if ($segment === '' || $segment === '--') {
                    continue;
                }

                [$childHeaderBlock, $childBody] = $this->splitHeaderAndBody($segment);
                $parts = array_merge($parts, $this->parsePart($this->parseHeaders($childHeaderBlock), $childBody));
            }

            return $parts;
        }

        return [[
            'headers' => $headers,
            'content_type' => $this->normalizeContentType($contentType),
            'disposition' => Str::lower((string) $this->firstHeader($headers, 'Content-Disposition')),
            'filename' => $this->headerParameter((string) $this->firstHeader($headers, 'Content-Disposition'), 'filename')
                ?? $this->headerParameter($contentType, 'name'),
            'content_id' => trim((string) $this->firstHeader($headers, 'Content-ID'), '<> '),
            'body' => $this->decodeBody($body, (string) $this->firstHeader($headers, 'Content-Transfer-Encoding')),
        ]];
    }

    /**
     * @param  array<int, array<string, mixed>>  $parts
     */
    private function firstBody(array $parts, string $contentType): string
    {
        foreach ($parts as $part) {
            if (($part['content_type'] ?? '') === $contentType && ! $this->isAttachmentPart($part)) {
                return trim((string) ($part['body'] ?? ''));
            }
        }

        return '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $parts
     * @return array<int, array<string, mixed>>
     */
    private function attachments(array $parts): array
    {
        $attachments = [];

        foreach ($parts as $part) {
            if (! $this->isAttachmentLikePart($part)) {
                continue;
            }

            $attachments[] = [
                'filename' => $part['filename'] ?? '',
                'mime_type' => $part['content_type'] ?? '',
                'disposition' => str_contains((string) ($part['disposition'] ?? ''), 'inline') ? 'inline' : 'attachment',
                'content_id' => $part['content_id'] ?? '',
                'size' => strlen((string) ($part['body'] ?? '')),
            ];
        }

        return $attachments;
    }

    /**
     * @param  array<string, mixed>  $part
     */
    private function isAttachmentPart(array $part): bool
    {
        return str_contains((string) ($part['disposition'] ?? ''), 'attachment');
    }

    /**
     * @param  array<string, mixed>  $part
     */
    private function isAttachmentLikePart(array $part): bool
    {
        $contentType = (string) ($part['content_type'] ?? '');

        if (str_starts_with($contentType, 'text/plain') || str_starts_with($contentType, 'text/html')) {
            return $this->isAttachmentPart($part);
        }

        return $this->isAttachmentPart($part)
            || ($part['filename'] ?? '') !== ''
            || ($part['content_id'] ?? '') !== '';
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     */
    private function recipientAddresses(array $headers): array
    {
        $addresses = [];

        foreach (['To', 'Delivered-To', 'X-Original-To', 'Envelope-To'] as $header) {
            foreach ($headers[strtolower($header)] ?? [] as $value) {
                $addresses = array_merge($addresses, $this->extractEmails($value));
            }
        }

        return array_values(array_unique(array_map('strtolower', $addresses)));
    }

    /**
     * @return array<int, string>
     */
    private function extractEmails(string $value): array
    {
        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $value, $matches);

        return $matches[0] ?? [];
    }

    private function extractFirstEmail(string $value): ?string
    {
        return strtolower($this->extractEmails($value)[0] ?? '') ?: null;
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     */
    private function firstHeader(array $headers, string $name): ?string
    {
        return $headers[strtolower($name)][0] ?? null;
    }

    private function cleanMessageId(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function decodeHeader(string $value): string
    {
        return iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') ?: $value;
    }

    private function normalizeContentType(string $contentType): string
    {
        return Str::lower(trim(explode(';', $contentType)[0]));
    }

    private function headerParameter(string $headerValue, string $parameter): ?string
    {
        if (preg_match('/(?:^|;)\s*'.preg_quote($parameter, '/').'\*?=(?:"([^"]+)"|([^;]+))/i', $headerValue, $matches)) {
            return trim($matches[1] ?: $matches[2]);
        }

        return null;
    }

    private function decodeBody(string $body, string $encoding): string
    {
        $encoding = Str::lower(trim($encoding));

        return match ($encoding) {
            'base64' => base64_decode(preg_replace('/\s+/', '', $body) ?: '', true) ?: '',
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };
    }

    private function htmlToText(string $html): string
    {
        return trim(html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $html) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
