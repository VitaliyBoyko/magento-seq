<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Model;

use VitaliiBoiko\Seq\Api\InstrumentationInterface;

/**
 * Default implementation of the public instrumentation service.
 */
class Instrumentation implements InstrumentationInterface
{
    public function __construct(
        private readonly SeqClient $seqClient
    ) {
    }

    /**
     * Send an event to Seq with backend defaults for shared metadata.
     *
     * @param array<string, mixed> $context Structured event payload.
     * @param array<string, mixed> $extra Additional top-level CLEF fields.
     */
    public function log(string $message, array $context = [], string $level = 'Debug', array $extra = []): void
    {
        $extra['channel'] = $extra['channel'] ?? 'backend';
        $extra['source'] = $extra['source'] ?? 'instrumentation';

        $this->seqClient->send($message, $context, $level, $extra);
    }
}
