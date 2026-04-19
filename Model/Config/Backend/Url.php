<?php
declare(strict_types=1);

namespace VitaliiBoiko\Seq\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;
use VitaliiBoiko\Seq\Model\ConnectionValidator;
use VitaliiBoiko\Seq\Model\UrlProcessor;

class Url extends Value
{
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        private readonly UrlProcessor $urlProcessor,
        private readonly ConnectionValidator $connectionValidator,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function beforeSave(): self
    {
        $value = trim((string) $this->getValue());
        $isEnabled = (string) $this->getFieldsetDataValue('enabled') === '1';

        if ($isEnabled && $value === '') {
            throw new LocalizedException(
                __('Enter a Seq host or URL before enabling the module.')
            );
        }

        $this->urlProcessor->validate($value);
        $this->connectionValidator->validateReachable($value);
        $this->setValue($value);

        return parent::beforeSave();
    }
}
