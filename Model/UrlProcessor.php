<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Normalizes and validates Seq URLs entered in admin configuration.
 */
class UrlProcessor
{
    private const DEFAULT_INGEST_PATH = '/api/events/raw';
    private const DEFAULT_INGEST_QUERY = 'clef';
    private const HEALTH_CHECK_PATH = '/api';

    /**
     * Normalize a host or partial URL into the Seq raw ingest endpoint.
     */
    public function normalize(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $parts = $this->parseParts($value);
        if ($parts === null) {
            return '';
        }

        $path = $parts['path'] ?? '';
        $query = $parts['query'] ?? null;

        if ($path === '' || $path === '/') {
            $path = self::DEFAULT_INGEST_PATH;
            $query = self::DEFAULT_INGEST_QUERY;
        } elseif (rtrim($path, '/') === self::DEFAULT_INGEST_PATH && ($query === null || $query === '')) {
            $path = self::DEFAULT_INGEST_PATH;
            $query = self::DEFAULT_INGEST_QUERY;
        }

        return $this->buildUrl($parts, $path, $query);
    }

    /**
     * Validate a Seq host or URL entered in admin configuration.
     *
     * @throws LocalizedException
     */
    public function validate(string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        $parts = $this->parseParts($value);
        if ($parts === null) {
            throw new LocalizedException(
                __('Enter a valid Seq host or URL, for example `http://seq:80` or `http://seq/api/events/raw?clef`.')
            );
        }

        if (isset($parts['scheme']) && !in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            throw new LocalizedException(
                __('Only `http` and `https` Seq URLs are supported.')
            );
        }

        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            throw new LocalizedException(
                __('Seq URL fragments are not supported.')
            );
        }

        $path = $parts['path'] ?? '';
        $normalizedPath = rtrim($path, '/');
        if ($path !== '' && $path !== '/' && $normalizedPath !== self::DEFAULT_INGEST_PATH) {
            throw new LocalizedException(
                $this->buildPathError()
            );
        }

        $query = $parts['query'] ?? null;
        if ($query !== null && $query !== '' && $query !== self::DEFAULT_INGEST_QUERY) {
            throw new LocalizedException(
                __('Only the `clef` query string is supported for the Seq raw endpoint.')
            );
        }

        if (($query !== null && $query !== '') && ($path === '' || $path === '/')) {
            throw new LocalizedException(
                __('Add the full raw endpoint path when using a query string, or leave the path empty and let the module append it.')
            );
        }
    }

    /**
     * Build the lightweight Seq API URL used for connectivity tests.
     */
    public function getHealthCheckUrl(string $value): string
    {
        $parts = $this->parseParts(trim($value));
        if ($parts === null) {
            return '';
        }

        return $this->buildUrl($parts, self::HEALTH_CHECK_PATH, null);
    }

    /**
     * Parse a Seq host or URL into parts, defaulting the scheme when omitted.
     *
     * @return array<string, int|string>|null
     */
    private function parseParts(string $value): ?array
    {
        $url = preg_match('~^[a-z][a-z0-9+.-]*://~i', $value) === 1 ? $value : 'http://' . $value;
        $parts = parse_url($url);

        if ($parts === false || !isset($parts['host']) || $parts['host'] === '') {
            return null;
        }

        return $parts;
    }

    /**
     * Rebuild a normalized URL from parsed parts.
     *
     * @param array<string, int|string> $parts
     */
    private function buildUrl(array $parts, string $path, ?string $query): string
    {
        $url = '';

        if (isset($parts['scheme'])) {
            $url .= $parts['scheme'] . '://';
        }

        if (isset($parts['user'])) {
            $url .= $parts['user'];
            if (isset($parts['pass'])) {
                $url .= ':' . $parts['pass'];
            }
            $url .= '@';
        }

        $url .= $parts['host'];

        if (isset($parts['port'])) {
            $url .= ':' . $parts['port'];
        }

        $url .= $path;

        if ($query !== null && $query !== '') {
            $url .= '?' . $query;
        }

        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $url .= '#' . $parts['fragment'];
        }

        return $url;
    }

    /**
     * Build the reusable validation message for unsupported paths.
     */
    private function buildPathError(): Phrase
    {
        return __(
            'Use only the Seq host, or the exact raw endpoint path `%1`.',
            self::DEFAULT_INGEST_PATH
        );
    }
}
