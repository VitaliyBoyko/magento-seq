<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Api;

interface InstrumentationInterface
{
    /**
     * Send a custom event to Seq.
     *
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     */
    public function log(string $message, array $context = [], string $level = 'Debug', array $extra = []): void;
}
