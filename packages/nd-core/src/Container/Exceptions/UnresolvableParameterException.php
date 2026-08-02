<?php

declare(strict_types=1);

namespace NDCore\Container\Exceptions;

final class UnresolvableParameterException extends ContainerException
{
    public static function forParameter(string $parameterName, string $class): self
    {
        return new self(sprintf(
            'No se pudo resolver el parámetro "$%s" del constructor de "%s": ' .
            'no tiene type-hint de clase, no tiene valor por defecto y no hay binding explícito.',
            $parameterName,
            $class
        ));
    }
}
