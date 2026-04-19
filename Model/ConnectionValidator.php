<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Model;

use Magento\Framework\Exception\LocalizedException;

class ConnectionValidator
{
    public function __construct(
        private readonly UrlProcessor $urlProcessor
    ) {
    }

    /**
     * @throws LocalizedException
     */
    public function validateReachable(string $value): void
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        if (!function_exists('curl_init')) {
            throw new LocalizedException(
                __('cURL is required to validate the Seq server connection.')
            );
        }

        $healthCheckUrl = $this->urlProcessor->getHealthCheckUrl($value);
        if ($healthCheckUrl === '') {
            throw new LocalizedException(
                __('Unable to build the Seq health-check URL from the configured value.')
            );
        }

        $curl = curl_init($healthCheckUrl);
        if ($curl === false) {
            throw new LocalizedException(
                __('Unable to initialize cURL for the Seq health check.')
            );
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPGET => true,
            CURLOPT_CONNECTTIMEOUT_MS => 700,
            CURLOPT_TIMEOUT_MS => 1500,
            CURLOPT_HEADER => false,
        ]);

        curl_exec($curl);
        $errorCode = curl_errno($curl);
        $errorMessage = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if ($errorCode !== 0) {
            throw new LocalizedException(
                __('Seq server is unreachable at `%1`: %2', $healthCheckUrl, $errorMessage !== '' ? $errorMessage : $errorCode)
            );
        }

        if ($statusCode === 0) {
            throw new LocalizedException(
                __('Seq server is unreachable at `%1`.', $healthCheckUrl)
            );
        }

        if ($statusCode >= 500) {
            throw new LocalizedException(
                __('Seq server responded with HTTP %1 during the connection test.', $statusCode)
            );
        }
    }
}
