<?php

declare(strict_types=1);

namespace NDCore\Hooks;

use Closure;

/**
 * Representa una única suscripción a un hook de WordPress, devuelta por
 * {@see HookManager} para poder eliminarla explícitamente más tarde.
 */
final class HookHandle {

	public function __construct(
		public readonly string $type,
		public readonly string $hookName,
		public readonly Closure $callback,
		public readonly int $priority,
		public readonly int $acceptedArgs,
	) {
	}
}
