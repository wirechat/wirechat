<?php

namespace Wirechat\Wirechat\Services\Concerns;

trait InteractsWithLinks
{
    /**
     * Check if the given message contains only a single link and nothing else.
     *
     * The method returns true only when the message is a valid URL
     * without any extra text, spaces, or characters.
     */
    public function isLink(string $message): bool
    {
        $message = trim($message);

        // No spaces allowed
        if ($message === '' || str_contains($message, ' ')) {
            return false;
        }

        // Normalize scheme for parsing (only for validation)
        $raw = $message;
        if (! preg_match('~^https?://~i', $message)) {
            $message = 'https://'.$message;
        }

        // Basic URL validation
        if (filter_var($message, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $host = parse_url($message, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        // Must contain a dot and not start/end with one
        if (! str_contains($host, '.') || str_starts_with($host, '.') || str_ends_with($host, '.')) {
            return false;
        }

        // No consecutive dots
        if (str_contains($host, '..')) {
            return false;
        }

        $labels = explode('.', $host);

        // Require at least 2 labels: example + tld
        if (count($labels) < 2) {
            return false;
        }

        $tld = strtolower((string) end($labels));

        // TLD: letters only, length 2..24 (tweak as you like)
        if (! preg_match('/^[a-z]{2,24}$/', $tld)) {
            return false;
        }

        $allowedTlds = $this->allowedTlds();
        if ($allowedTlds !== null && ! in_array($tld, $allowedTlds, true)) {
            return false;
        }

        // Validate each label (RFC-ish): a-z0-9, hyphen not at ends, length 1..63
        foreach ($labels as $label) {
            if ($label === '' || strlen($label) > 63) {
                return false;
            }
            if (! preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $label)) {
                return false;
            }
        }

        // --- Chat heuristics to avoid auto-linking random "word.tld" ---
        // If typed WITHOUT scheme and it's exactly two labels (one dot),
        // require intent unless bare domains are allowed.
        if (! preg_match('~^https?://~i', $raw) && substr_count($host, '.') === 1) {
            $looksIntentional =
                str_starts_with($host, 'www.') ||
                str_contains($raw, '/') ||
                str_contains($raw, '?') ||
                str_contains($raw, '#');

            if (! $looksIntentional && ! $this->canLinkifyBareDomain()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the message contains any link-like text.
     */
    public function containsLink(string $message): bool
    {
        $pattern = $this->linkifyPattern();
        if (! preg_match_all($pattern, $message, $matches)) {
            return false;
        }

        foreach ($matches[0] as $token) {
            if ($this->resolveLinkToken($token) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Split a message into link and text segments for rendering.
     *
     * @return array<int, array{text: string, href: string|null, is_link: bool}>
     */
    public function linkifyMessage(string $message): array
    {
        $pattern = $this->linkifyPattern();
        $parts = preg_split($pattern, $message, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (! is_array($parts) || $parts === []) {
            return [[
                'text' => $message,
                'href' => null,
                'is_link' => false,
            ]];
        }

        $segments = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (preg_match($pattern, $part)) {
                [$linkPart, $trailing] = $this->splitTrailingPunctuation($part);
                $href = $this->resolveLinkToken($linkPart);

                if ($href !== null) {
                    $segments[] = [
                        'text' => $linkPart,
                        'href' => $href,
                        'is_link' => true,
                    ];

                    if ($trailing !== '') {
                        $segments[] = [
                            'text' => $trailing,
                            'href' => null,
                            'is_link' => false,
                        ];
                    }
                } else {
                    $segments[] = [
                        'text' => $part,
                        'href' => null,
                        'is_link' => false,
                    ];
                }
            } else {
                $segments[] = [
                    'text' => $part,
                    'href' => null,
                    'is_link' => false,
                ];
            }
        }

        return $segments;
    }

    public function allowedTlds(): ?array
    {
        $allowed = config('wirechat.message_url_parsing.allowed_tlds', null);

        if ($allowed === null) {
            return null;
        }

        return array_values(array_filter(array_map('strtolower', (array) $allowed)));
    }

    private function linkifyPattern(): string
    {
        return '~(https?://[^\s<]+|(?<![\w@])[a-z0-9._%+\-]+@[a-z0-9.-]+\.[a-z]{2,24}(?:[/?#][^\s<]*)?(?![\w@])|(?<![\w@])www\.[^\s<]+|(?<![\w@])[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+(?::\d{1,5})?(?:[/?#][^\s<]*)?)~i';
    }

    private function resolveLinkToken(string $token): ?string
    {
        [$token] = $this->splitTrailingPunctuation($token);

        if ($token === '') {
            return null;
        }

        if ($this->isEmailToken($token)) {
            return 'mailto:'.$token;
        }

        $hasScheme = preg_match('~^https?://~i', $token) === 1;
        $hasWww = str_starts_with(strtolower($token), 'www.');
        $isBare = ! $hasScheme && ! $hasWww;

        if (! $this->isLink($token)) {
            return null;
        }

        if ($isBare && ! $this->canLinkifyBareDomain()) {
            return null;
        }

        return $hasScheme ? $token : 'https://'.$token;
    }

    private function canLinkifyBareDomain(): bool
    {
        return (bool) config('wirechat.message_url_parsing.allow_bare_domains', true);
    }

    private function isEmailToken(string $token): bool
    {
        $token = trim($token);
        if ($token === '') {
            return false;
        }

        $email = preg_split('/[?#]/', $token, 2)[0] ?? '';

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        [$local, $host] = explode('@', $email, 2);

        if ($host === '') {
            return false;
        }

        $host = strtolower($host);

        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        if (! str_contains($host, '.') || str_starts_with($host, '.') || str_ends_with($host, '.')) {
            return false;
        }

        if (str_contains($host, '..')) {
            return false;
        }

        $labels = explode('.', $host);
        if (count($labels) < 2) {
            return false;
        }

        $tld = strtolower((string) end($labels));
        if (! preg_match('/^[a-z]{2,24}$/', $tld)) {
            return false;
        }

        $allowedTlds = $this->allowedTlds();
        if ($allowedTlds !== null && ! in_array($tld, $allowedTlds, true)) {
            return false;
        }

        foreach ($labels as $label) {
            if ($label === '' || strlen($label) > 63) {
                return false;
            }
            if (! preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', $label)) {
                return false;
            }
        }

        return $local !== '';
    }

    private function splitTrailingPunctuation(string $token): array
    {
        $trimmed = rtrim($token, '.,!?;:)]');
        $trailing = substr($token, strlen($trimmed));

        return [$trimmed, $trailing];
    }
}
