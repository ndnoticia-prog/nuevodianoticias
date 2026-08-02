<?php

declare(strict_types=1);

namespace NDCore\Container;

use Closure;
use NDCore\Container\Exceptions\ContainerException;
use NDCore\Container\Exceptions\NotFoundException;
use NDCore\Container\Exceptions\UnresolvableParameterException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Contenedor de inyección de dependencias con autowiring, compatible con PSR-11.
 */
class Container implements ContainerInterface {

	/**
	 * @var array<string, array{concrete: Closure|string, shared: bool}>
	 */
	private array $bindings = array();

	/**
	 * @var array<string, object>
	 */
	private array $instances = array();

	/**
	 * Pila de abstracts que se están resolviendo actualmente, usada para
	 * detectar dependencias circulares.
	 *
	 * @var list<string>
	 */
	private array $buildStack = array();

	public function bind( string $abstract, Closure|string|null $concrete = null, bool $shared = false ): void {
		$concrete ??= $abstract;

		unset( $this->instances[ $abstract ] );

		$this->bindings[ $abstract ] = array(
			'concrete' => $concrete,
			'shared'   => $shared,
		);
	}

	public function singleton( string $abstract, Closure|string|null $concrete = null ): void {
		$this->bind( $abstract, $concrete, true );
	}

	public function instance( string $abstract, object $instance ): void {
		$this->instances[ $abstract ] = $instance;
	}

	public function has( string $id ): bool {
		return isset( $this->instances[ $id ] )
			|| isset( $this->bindings[ $id ] )
			|| class_exists( $id );
	}

	public function get( string $id ): mixed {
		return $this->make( $id );
	}

	/**
	 * @param array<string, mixed> $parameters Parámetros con nombre que sobreescriben la resolución automática.
	 */
	public function make( string $abstract, array $parameters = array() ): mixed {
		if ( isset( $this->instances[ $abstract ] ) ) {
			return $this->instances[ $abstract ];
		}

		$concrete = $this->bindings[ $abstract ]['concrete'] ?? $abstract;
		$shared   = $this->bindings[ $abstract ]['shared'] ?? false;

		if ( in_array( $abstract, $this->buildStack, true ) ) {
			throw new ContainerException(
				sprintf(
					'Dependencia circular detectada al resolver "%s": %s -> %s.',
					$abstract,
					implode( ' -> ', $this->buildStack ),
					$abstract
				)
			);
		}

		$this->buildStack[] = $abstract;

		try {
			$object = $concrete instanceof Closure
				? $concrete( $this, $parameters )
				: $this->build( $concrete, $parameters );
		} finally {
			array_pop( $this->buildStack );
		}

		if ( $shared ) {
			$this->instances[ $abstract ] = $object;
		}

		return $object;
	}

	/**
	 * @param array<string, mixed> $parameters
	 */
	private function build( string $concrete, array $parameters ): object {
		if ( ! class_exists( $concrete ) ) {
			throw NotFoundException::forAbstract( $concrete );
		}

		// class_exists() ya garantiza que $concrete es una clase real y
		// autocargable, así que ReflectionClass no puede lanzar
		// ReflectionException aquí (solo lo hace cuando la clase/interfaz/
		// trait no existe).
		$reflector = new ReflectionClass( $concrete );

		if ( ! $reflector->isInstantiable() ) {
			throw new ContainerException( sprintf( '"%s" no es instanciable (interfaz o clase abstracta sin binding).', $concrete ) );
		}

		$constructor = $reflector->getConstructor();

		if ( $constructor === null ) {
			return new $concrete();
		}

		$dependencies = array();

		foreach ( $constructor->getParameters() as $parameter ) {
			if ( array_key_exists( $parameter->getName(), $parameters ) ) {
				$dependencies[] = $parameters[ $parameter->getName() ];

				continue;
			}

			$dependencies[] = $this->resolveParameter( $parameter, $concrete );
		}

		return $reflector->newInstanceArgs( $dependencies );
	}

	private function resolveParameter( ReflectionParameter $parameter, string $class ): mixed {
		$type = $parameter->getType();

		if ( $type instanceof ReflectionNamedType && ! $type->isBuiltin() ) {
			/** @var class-string $typeName */
			$typeName = $type->getName();

			try {
				return $this->make( $typeName );
			} catch ( ContainerException | NotFoundException $exception ) {
				if ( $parameter->isDefaultValueAvailable() ) {
					return $parameter->getDefaultValue();
				}

				if ( $type->allowsNull() ) {
					return null;
				}

				throw $exception;
			}
		}

		if ( $parameter->isDefaultValueAvailable() ) {
			return $parameter->getDefaultValue();
		}

		if ( $type instanceof ReflectionNamedType && $type->allowsNull() ) {
			return null;
		}

		throw UnresolvableParameterException::forParameter( $parameter->getName(), $class );
	}
}
