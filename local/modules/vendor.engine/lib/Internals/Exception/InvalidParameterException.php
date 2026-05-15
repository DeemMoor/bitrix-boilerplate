<?php

declare(strict_types=1);

namespace Vendor\Engine\Internals\Exception;

class InvalidParameterException extends EngineException
{
    public static function forParameter(string $name, string $reason): self
    {
        return new self(sprintf('Invalid parameter "%s": %s.', $name, $reason));
    }
}
