<?php

declare(strict_types=1);

namespace NDBuilder;

use InvalidArgumentException;
use NDBuilder\Contracts\BlockRenderer;

/**
 * Registro de qué {@see BlockRenderer} atiende cada tipo de bloque
 * (`hero`, `noticias`, `breaking`, ...). Cualquier paquete puede añadir
 * nuevos tipos de bloque registrándose aquí.
 */
final class BlockRegistry {

	/**
	 * @var array<string, BlockRenderer>
	 */
	private array $renderers = array();

	public function register( string $type, BlockRenderer $renderer ): void {
		$this->renderers[ $type ] = $renderer;
	}

	public function has( string $type ): bool {
		return isset( $this->renderers[ $type ] );
	}

	public function rendererFor( string $type ): BlockRenderer {
		if ( ! isset( $this->renderers[ $type ] ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'No hay ningún renderer registrado para el tipo de bloque "%s".',
					$type
				)
			);
		}

		return $this->renderers[ $type ];
	}

	/**
	 * @return list<string>
	 */
	public function registeredTypes(): array {
		return array_keys( $this->renderers );
	}
}
