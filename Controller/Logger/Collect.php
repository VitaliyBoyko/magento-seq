<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Controller\Logger;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Throwable;
use VitaliiBoiko\Seq\Api\InstrumentationInterface;

class Collect implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly JsonSerializer $jsonSerializer,
        private readonly InstrumentationInterface $instrumentation
    ) {
    }

    public function execute(): Json
    {
        $payload = $this->readPayload();
        $message = trim((string) ($payload['message'] ?? $payload['event'] ?? 'frontend.event'));
        $level = (string) ($payload['level'] ?? 'Debug');
        $context = $payload['context'] ?? [];

        if (!is_array($context)) {
            $context = ['raw_context' => $context];
        }

        $this->instrumentation->log(
            $message !== '' ? $message : 'frontend.event',
            $context,
            $level,
            [
                'channel' => 'frontend',
                'source' => 'frontend',
            ]
        );

        $result = $this->resultJsonFactory->create();
        $result->setData(['ok' => true]);

        return $result;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayload(): array
    {
        try {
            $content = trim((string) $this->request->getContent());
            if ($content !== '') {
                $decoded = $this->jsonSerializer->unserialize($content);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        } catch (Throwable) {
            return [];
        }

        $params = $this->request->getParams();

        return is_array($params) ? $params : [];
    }
}
