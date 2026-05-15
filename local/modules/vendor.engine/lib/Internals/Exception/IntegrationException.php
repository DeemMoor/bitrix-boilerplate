<?php

declare(strict_types=1);

namespace Vendor\Engine\Internals\Exception;

class IntegrationException extends EngineException
{
    public static function forService(string $service, string $reason): self
    {
        return new self(sprintf('Integration service "%s" failed: %s.', $service, $reason));
    }
}
