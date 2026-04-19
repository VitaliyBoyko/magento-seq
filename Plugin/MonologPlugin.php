<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Plugin;

use Monolog\Logger;
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
        mixed $level,
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

    private function resolveLevelName(mixed $level): string
    {
        if (is_object($level) && isset($level->name) && is_string($level->name)) {
            return $level->name;
        }

        if (is_string($level) && $level !== '') {
            return ucfirst(strtolower($level));
        }

        return match ((int) $level) {
            200 => 'Info',
            250 => 'Notice',
            300 => 'Warning',
            400 => 'Error',
            500 => 'Critical',
            550 => 'Alert',
            600 => 'Emergency',
            default => 'Debug',
        };
    }
}
