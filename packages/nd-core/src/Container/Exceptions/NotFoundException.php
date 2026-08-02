<?php

declare(strict_types=1);

namespace NDCore\Container\Exceptions;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

final class NotFoundException extends RuntimeException implements NotFoundExceptionInterface {

	public static function forAbstract( string $abstract ): self {
		return new self( sprintf( 'No hay ningún binding ni clase resoluble para "%s".', $abstract ) );
	}
}
