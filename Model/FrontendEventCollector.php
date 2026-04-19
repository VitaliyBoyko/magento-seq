<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Model;

use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use VitaliiBoiko\Seq\Api\FrontendEventCollectorInterface;
use VitaliiBoiko\Seq\Api\InstrumentationInterface;
use Throwable;

class FrontendEventCollector implements FrontendEventCollectorInterface
{
    public function __construct(
        private readonly InstrumentationInterface $instrumentation,
        private readonly JsonSerializer $jsonSerializer
    ) {
    }

    public function collect(
        string $message = 'frontend.event',
        string $contextJson = '{}',
        string $level = 'Debug'
    ): bool {
        $message = trim($message);
        $context = $this->decodeContext($contextJson);

        $this->instrumentation->log(
            $message !== '' ? $message : 'frontend.event',
            $context,
            $level,
            [
                'channel' => 'frontend',
                'source' => 'frontend',
            ]
        );

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeContext(string $contextJson): array
    {
        $contextJson = trim($contextJson);
        if ($contextJson === '') {
            return [];
        }

        try {
            $decoded = $this->jsonSerializer->unserialize($contextJson);
            if (is_array($decoded)) {
                return $decoded;
            }
        } catch (Throwable) {
            return ['raw_context' => $contextJson];
        }

        return ['raw_context' => $contextJson];
    }
}
