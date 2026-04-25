<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Block;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use VitaliiBoiko\Seq\Model\Config;

/**
 * Renders the frontend bootstrap configuration for the browser logger.
 */
class Bootstrap extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly Json $jsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Determine whether the frontend bootstrap should be rendered.
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled() && $this->config->getUrl() !== '';
    }

    /**
     * Serialize the client-side configuration consumed by the inline bootstrap.
     */
    public function getJsonConfig(): string
    {
        return $this->jsonSerializer->serialize([
            'collectUrl' => $this->getCollectUrl(),
        ]);
    }

    /**
     * Build the REST endpoint used by the browser logger.
     */
    public function getCollectUrl(): string
    {
        $baseUrl = rtrim((string) $this->storeManager->getStore()->getBaseUrl(), '/');

        return $baseUrl . '/rest/V1/vitaliiboiko-seq/collect';
    }
}
