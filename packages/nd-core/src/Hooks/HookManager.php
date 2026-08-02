<?php

declare(strict_types=1);

namespace NDCore\Hooks;

use Closure;

/**
 * Única puerta de entrada de ND Platform hacia el sistema de hooks de
 * WordPress. Ningún paquete debe llamar a `add_action`/`add_filter`
 * directamente: deben depender de esta clase para seguir siendo comprobables
 * unitariamente sin un WordPress real.
 */
final class HookManager {

	/**
	 * @var list<HookHandle>
	 */
	private array $handles = array();

	public function addAction(
		string $hookName,
		callable $callback,
		int $priority = 10,
		int $acceptedArgs = 1
	): HookHandle {
		return $this->register( 'action', $hookName, $callback, $priority, $acceptedArgs );
	}

	public function addFilter(
		string $hookName,
		callable $callback,
		int $priority = 10,
		int $acceptedArgs = 1
	): HookHandle {
		return $this->register( 'filter', $hookName, $callback, $priority, $acceptedArgs );
	}

	public function remove( HookHandle $handle ): bool {
		$removed = $handle->type === 'action'
			? remove_action( $handle->hookName, $handle->callback, $handle->priority )
			: remove_filter( $handle->hookName, $handle->callback, $handle->priority );

		$this->handles = array_values(
			array_filter(
				$this->handles,
				static fn ( HookHandle $existing ): bool => $existing !== $handle
			)
		);

		return $removed;
	}

	/**
	 * @param non-empty-string $hookName
	 * @param mixed ...$args
	 */
	public function doAction( string $hookName, mixed ...$args ): void {
		do_action( $hookName, ...$args );
	}

	/**
	 * @param non-empty-string $hookName
	 * @param mixed ...$args
	 */
	public function applyFilters( string $hookName, mixed $value, mixed ...$args ): mixed {
		return apply_filters( $hookName, $value, ...$args );
	}

	/**
	 * @return list<HookHandle>
	 */
	public function registered(): array {
		return $this->handles;
	}

	/**
	 * Convierte `$callback` a `Closure` una única vez y usa esa misma
	 * instancia tanto para registrar el hook como para almacenarla en el
	 * `HookHandle`: si se guardara una copia distinta, `remove_action`/
	 * `remove_filter` no encontrarían coincidencia y `remove()` no
	 * eliminaría nada.
	 */
	private function register(
		string $type,
		string $hookName,
		callable $callback,
		int $priority,
		int $acceptedArgs
	): HookHandle {
		$closure = Closure::fromCallable( $callback );

		if ( $type === 'action' ) {
			add_action( $hookName, $closure, $priority, $acceptedArgs );
		} else {
			add_filter( $hookName, $closure, $priority, $acceptedArgs );
		}

		$handle          = new HookHandle( $type, $hookName, $closure, $priority, $acceptedArgs );
		$this->handles[] = $handle;

		return $handle;
	}
}
