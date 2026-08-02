<?php

declare(strict_types=1);

namespace NDCore\Routing;

use Closure;

/**
 * Definición inmutable de una ruta REST, traducible a los argumentos que
 * espera `register_rest_route()` de WordPress vía {@see toRestArgs()}.
 */
final class Route {

	public readonly Closure $handler;

	public readonly Closure $permissionCallback;

	/**
	 * @param string|list<string> $methods
	 * @param array<string, mixed> $args
	 */
	public function __construct(
		public readonly string $namespace,
		public readonly string $path,
		public readonly string|array $methods,
		callable $handler,
		?callable $permissionCallback = null,
		public readonly array $args = array(),
	) {
		$this->handler            = Closure::fromCallable( $handler );
		$this->permissionCallback = Closure::fromCallable(
			$permissionCallback ?? static fn (): bool => false
		);
	}

	public function fullPath(): string {
		return $this->namespace . $this->path;
	}

	/**
	 * @return array{methods: string|list<string>, callback: Closure, permission_callback: Closure, args: array<string, mixed>}
	 */
	public function toRestArgs(): array {
		return array(
			'methods'             => $this->methods,
			'callback'            => $this->handler,
			'permission_callback' => $this->permissionCallback,
			'args'                => $this->args,
		);
	}
}
