<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Dto;

final class RunCommandDto
{
    /**
     * @param string[] $input
     */
    public function __construct(
        public array $input = [],
        public ?int $timeout = null
    ) {}
}
