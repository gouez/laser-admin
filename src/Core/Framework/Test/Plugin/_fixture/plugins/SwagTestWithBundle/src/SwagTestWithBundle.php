<?php declare(strict_types=1);

namespace SwagTestWithBundle;

use Laser\Core\Framework\Parameter\AdditionalBundleParameters;
use Laser\Core\Framework\Plugin;
use Laser\Core\Framework\Test\Plugin\_fixture\bundles\FooBarBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

class SwagTestWithBundle extends Plugin
{
    public function getAdditionalBundles(AdditionalBundleParameters $parameters): array
    {
        require_once __DIR__ . '/../../../bundles/FooBarBundle.php';

        return [
            // is already provided externally and should not be loaded
            new FrameworkBundle(),
            // is already provided by SwagTest and should not be loaded twice
            new FooBarBundle(),
        ];
    }
}
