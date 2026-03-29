<?php declare(strict_types=1);

namespace Danilovl\WebCommandBundle\Message;

final readonly class RunCommandMessage
{
    public function __construct(public int $jobId) {}
}
