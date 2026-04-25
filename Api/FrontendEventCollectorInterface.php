<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Api;

/**
 * Accepts browser-originated events and forwards them to Seq.
 */
interface FrontendEventCollectorInterface
{
    /**
     * Collect a frontend event and relay it to Seq.
     *
     * The JSON payload is passed as a string because the Magento Web API route
     * accepts primitive request parameters more reliably than arbitrary objects.
     *
     * @param string $message Event name or message template.
     * @param string $contextJson JSON-encoded event context.
     * @param string $level Seq/Serilog log level name.
     *
     * @return bool
     */
    public function collect(
        string $message = 'frontend.event',
        string $contextJson = '{}',
        string $level = 'Debug'
    ): bool;
}
