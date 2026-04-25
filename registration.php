<?php
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

// Register the module so Magento can discover it during setup and runtime.
ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'VitaliiBoiko_Seq',
    __DIR__
);
