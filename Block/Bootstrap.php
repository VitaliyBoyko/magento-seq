<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Block;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use VitaliiBoiko\Seq\Model\Config;

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

    public function isEnabled(): bool
    {
        return $this->config->isEnabled() && $this->config->getUrl() !== '';
    }

    public function getJsonConfig(): string
    {
        return $this->jsonSerializer->serialize([
            'collectUrl' => $this->getCollectUrl(),
        ]);
    }

    public function getCollectUrl(): string
    {
        $baseUrl = rtrim((string) $this->storeManager->getStore()->getBaseUrl(), '/');

        return $baseUrl . '/rest/V1/vitaliiboiko-seq/collect';
    }
}
