<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Throwable;

/**
 * Sends CLEF-formatted events to the configured Seq endpoint.
 */
class SeqClient
{
    public function __construct(
        private readonly Config $config,
        private readonly Normalizer $normalizer,
        private readonly RequestInterface $request,
        private readonly RemoteAddress $remoteAddress,
        private readonly State $appState
    ) {
    }

    /**
     * Send a single event to Seq.
     *
     * @param array<string, mixed> $context Structured event payload.
     * @param array<string, mixed> $extra Additional top-level CLEF fields.
     */
    public function send(string $message, array $context = [], string $level = 'Debug', array $extra = []): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $url = $this->config->getUrl();
        if ($url === '' || !function_exists('curl_init')) {
            return;
        }

        // Seq accepts newline-delimited CLEF events. Each request emits one event.
        $payload = [
            '@t' => gmdate('c'),
            '@mt' => $message,
            '@l' => $level,
            'request' => $this->collectRequestMetadata(),
            'context' => $this->normalizer->normalize($context),
        ];

        foreach ($extra as $key => $value) {
            $payload[$key] = $this->normalizer->normalize($value);
        }

        $body = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );

        if ($body === false) {
            return;
        }

        $headers = ['Content-Type: application/vnd.serilog.clef'];
        $password = $this->config->getPassword();
        if ($password !== '') {
            $headers[] = 'X-Seq-ApiKey: ' . $password;
        }

        $curl = curl_init($url);
        if ($curl === false) {
            return;
        }

        // Keep delivery best-effort and non-blocking for normal Magento execution paths.
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body . "\n",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => 300,
            CURLOPT_TIMEOUT_MS => 800,
        ]);

        curl_exec($curl);
        curl_close($curl);
    }

    /**
     * Collect request metadata that helps correlate events in Seq.
     *
     * @return array<string, mixed>
     */
    private function collectRequestMetadata(): array
    {
        $metadata = [
            'uri' => $this->request->getRequestUri(),
            'path' => $this->request->getPathInfo(),
            'remote_addr' => $this->remoteAddress->getRemoteAddress(),
        ];

        if (method_exists($this->request, 'getMethod')) {
            $metadata['method'] = $this->request->getMethod();
        }

        try {
            $metadata['area_code'] = $this->appState->getAreaCode();
        } catch (Throwable) {
            $metadata['area_code'] = null;
        }

        return $metadata;
    }
}
