<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Model;

use VitaliiBoiko\Seq\Api\InstrumentationInterface;

class Instrumentation implements InstrumentationInterface
{
    public function __construct(
        private readonly SeqClient $seqClient
    ) {
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     */
    public function log(string $message, array $context = [], string $level = 'Debug', array $extra = []): void
    {
        $extra['channel'] = $extra['channel'] ?? 'backend';
        $extra['source'] = $extra['source'] ?? 'instrumentation';

        $this->seqClient->send($message, $context, $level, $extra);
    }
}
