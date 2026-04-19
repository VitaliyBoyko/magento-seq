<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Api;

interface FrontendEventCollectorInterface
{
    /**
     * Collect a frontend event and relay it to Seq.
     *
     * @param string $message
     * @param string $contextJson
     * @param string $level
     * @return bool
     */
    public function collect(
        string $message = 'frontend.event',
        string $contextJson = '{}',
        string $level = 'Debug'
    ): bool;
}
