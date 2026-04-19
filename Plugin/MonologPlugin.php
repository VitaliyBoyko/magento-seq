<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Plugin;

use Monolog\Level;
use Monolog\Logger;
use Throwable;
use VitaliiBoiko\Seq\Model\SeqClient;

class MonologPlugin
{
    public function __construct(
        private readonly SeqClient $seqClient
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function afterAddRecord(
        Logger $subject,
        bool $result,
        int|Level $level,
        string $message,
        array $context = []
    ): bool {
        if (!$result) {
            return $result;
        }

        $this->seqClient->send(
            $message,
            $context,
            $this->resolveLevelName($level),
            [
                'channel' => 'monolog',
                'logger_channel' => $subject->getName(),
                'source' => 'monolog',
            ]
        );

        return $result;
    }

    private function resolveLevelName(int|Level $level): string
    {
        if ($level instanceof Level) {
            return $level->name;
        }

        try {
            return Level::fromValue($level)->name;
        } catch (Throwable) {
            return Level::Debug->name;
        }
    }
}
