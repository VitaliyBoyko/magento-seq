<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Throwable;

/**
 * Reads and normalizes module configuration values from Magento config storage.
 */
class Config
{
    private const XML_PATH_ENABLED = 'vitaliiboiko_seq/general/enabled';
    private const XML_PATH_URL = 'vitaliiboiko_seq/general/url';
    private const XML_PATH_PASSWORD = 'vitaliiboiko_seq/general/password';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly UrlProcessor $urlProcessor
    ) {
    }

    /**
     * Check whether Seq forwarding is enabled for the current store scope.
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Return the normalized Seq ingest URL configured for the current store.
     */
    public function getUrl(): string
    {
        $value = trim((string) $this->scopeConfig->getValue(self::XML_PATH_URL, ScopeInterface::SCOPE_STORE));
        if ($value === '') {
            return '';
        }

        return $this->urlProcessor->normalize($value);
    }

    /**
     * Return the configured Seq API key.
     *
     * Magento stores encrypted config values, but this fallback keeps local
     * development setups working when the value was saved in plain text.
     */
    public function getPassword(): string
    {
        $value = (string) $this->scopeConfig->getValue(self::XML_PATH_PASSWORD, ScopeInterface::SCOPE_STORE);
        if ($value === '') {
            return '';
        }

        try {
            return (string) $this->encryptor->decrypt($value);
        } catch (Throwable) {
            return $value;
        }
    }
}
