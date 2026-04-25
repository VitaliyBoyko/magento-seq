<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Api;

/**
 * Public service contract for sending custom events to Seq.
 */
interface InstrumentationInterface
{
    /**
     * Send a custom event to Seq.
     *
     * @param array<string, mixed> $context Structured event payload.
     * @param array<string, mixed> $extra Additional top-level CLEF fields.
     */
    public function log(string $message, array $context = [], string $level = 'Debug', array $extra = []): void;
}
