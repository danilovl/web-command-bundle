<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Dto;

/**
 * @phpstan-type ConfigurationArray array{
 *     api_prefix: string,
 *     console_path: ?string,
 *     enable_async: bool,
 *     default_timeout: ?int,
 *     default_time_limit: ?int,
 *     default_memory_limit: ?string,
 *     enabled_dashboard_controller: bool
 * }
 */
final readonly class ConfigurationModel
{
    public function __construct(
        public string $apiPrefix,
        public ?string $consolePath,
        public bool $enableAsync,
        public ?int $defaultTimeout,
        public ?int $defaultTimeLimit,
        public ?string $defaultMemoryLimit,
        public bool $enabledDashboardController
    ) {}

    /**
     * @param ConfigurationArray $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            apiPrefix: $config['api_prefix'],
            consolePath: $config['console_path'],
            enableAsync: $config['enable_async'],
            defaultTimeout: $config['default_timeout'],
            defaultTimeLimit: $config['default_time_limit'],
            defaultMemoryLimit: $config['default_memory_limit'],
            enabledDashboardController: $config['enabled_dashboard_controller']
        );
    }
}
