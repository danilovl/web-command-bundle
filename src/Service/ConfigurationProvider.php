<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Service;

final readonly class ConfigurationProvider
{
    public function __construct(
        public string $apiPrefix,
        public string $consolePath,
        public bool $enableAsync,
        public ?int $defaultTimeout,
        public ?int $defaultTimeLimit,
        public ?string $defaultMemoryLimit,
        public bool $enabledAdminDashboard,
        public bool $enabledDashboardLiveStatus
    ) {}
}
