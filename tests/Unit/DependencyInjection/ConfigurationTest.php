<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Tests\Unit\DependencyInjection;

use Danilovl\WebCommandBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        return new Processor()->processConfiguration(new Configuration, [$config]);
    }

    public function testDefaults(): void
    {
        $config = $this->process([]);

        $this->assertNull($config['default_timeout']);
        $this->assertNull($config['default_time_limit']);
        $this->assertNull($config['required_role']);
    }

    #[DataProvider('provideTimeoutMustBePositiveCases')]
    public function testTimeoutMustBePositive(string $option, int $value): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process([$option => $value]);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideTimeoutMustBePositiveCases(): iterable
    {
        yield 'zero timeout' => ['default_timeout', 0];
        yield 'negative timeout' => ['default_timeout', -1];
        yield 'zero time limit' => ['default_time_limit', 0];
        yield 'negative time limit' => ['default_time_limit', -1];
    }
}
