<?php

declare(strict_types=1);

namespace NDCore\Queue;

use NDCore\Queue\Contracts\ShouldQueue;

/**
 * Clase base conveniente para trabajos encolados. Las subclases deben tener
 * un constructor sin argumentos obligatorios: el estado se restaura a través
 * de propiedades públicas serializadas en {@see toPayload()}.
 */
abstract class Job implements ShouldQueue {

	public int $maxAttempts = 3;

	abstract public function handle(): void;

	/**
	 * @return array<string, mixed>
	 */
	public function toPayload(): array {
		return get_object_vars( $this );
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public static function fromPayload( array $payload ): static {
		// Uso intencional de "new static()": es precisamente el mecanismo
		// que permite reconstruir la subclase concreta de Job a partir de
		// solo su nombre de clase (guardado en la cola) y su payload. El
		// docblock de la clase ya documenta el contrato exigido a las
		// subclases (constructor sin argumentos obligatorios).
		// @phpstan-ignore new.static
		$instance = new static();

		foreach ( $payload as $property => $value ) {
			if ( property_exists( $instance, $property ) ) {
				// @phpstan-ignore property.dynamicName
				$instance->{$property} = $value;
			}
		}

		return $instance;
	}
}
