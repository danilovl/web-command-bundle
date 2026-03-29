<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle;

use Danilovl\WebCommandBundle\DependencyInjection\WebCommandExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class WebCommandBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new WebCommandExtension;
    }
}
