<?php

declare(strict_types=1);

namespace NDCore\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @implements ArrayAccess<TKey, TValue>
 * @implements IteratorAggregate<TKey, TValue>
 */
final class Collection implements ArrayAccess, Countable, IteratorAggregate {

	/**
	 * @param array<TKey, TValue> $items
	 */
	public function __construct( private array $items = array() ) {
	}

	/**
	 * @param array<TKey, TValue> $items
	 *
	 * @return self<TKey, TValue>
	 */
	public static function make( array $items = array() ): self {
		return new self( $items );
	}

	/**
	 * @template TMapped
	 *
	 * @param callable(TValue, TKey): TMapped $callback
	 *
	 * @return self<TKey, TMapped>
	 */
	public function map( callable $callback ): self {
		return new self( array_map( $callback, $this->items, array_keys( $this->items ) ) );
	}

	/**
	 * @param (callable(TValue, TKey): bool)|null $callback
	 *
	 * @return self<TKey, TValue>
	 */
	public function filter( ?callable $callback = null ): self {
		$filtered = $callback === null
			? array_filter( $this->items )
			: array_filter( $this->items, $callback, ARRAY_FILTER_USE_BOTH );

		return new self( $filtered );
	}

	/**
	 * @param callable(mixed, TValue, TKey): mixed $callback
	 */
	public function reduce( callable $callback, mixed $initial = null ): mixed {
		$accumulator = $initial;

		foreach ( $this->items as $key => $item ) {
			$accumulator = $callback( $accumulator, $item, $key );
		}

		return $accumulator;
	}

	/**
	 * @param callable(TValue, TKey): void $callback
	 *
	 * @return self<TKey, TValue>
	 */
	public function each( callable $callback ): self {
		foreach ( $this->items as $key => $item ) {
			$callback( $item, $key );
		}

		return $this;
	}

	/**
	 * @return self<TKey, mixed>
	 */
	public function pluck( string $key ): self {
		return $this->map( static fn ( mixed $item ): mixed => is_array( $item ) ? Arr::get( $item, $key ) : null );
	}

	/**
	 * @return self<int, TValue>
	 */
	public function values(): self {
		return new self( array_values( $this->items ) );
	}

	/**
	 * @return self<int, TKey>
	 */
	public function keys(): self {
		return new self( array_keys( $this->items ) );
	}

	public function first(): mixed {
		foreach ( $this->items as $item ) {
			return $item;
		}

		return null;
	}

	public function last(): mixed {
		$reversed = array_reverse( $this->items, true );

		foreach ( $reversed as $item ) {
			return $item;
		}

		return null;
	}

	public function sum( ?string $key = null ): int|float {
		if ( $key === null ) {
			/** @var list<int|float> $items */
			$items = $this->items;

			return array_sum( $items );
		}

		return $this->pluck( $key )->sum();
	}

	/**
	 * @return self<int, TValue>
	 */
	public function sortBy( string $key, bool $descending = false ): self {
		$items = $this->items;

		usort(
			$items,
			static function ( mixed $a, mixed $b ) use ( $key ): int {
				$valueA = is_array( $a ) ? Arr::get( $a, $key ) : null;
				$valueB = is_array( $b ) ? Arr::get( $b, $key ) : null;

				return $valueA <=> $valueB;
			}
		);

		if ( $descending ) {
			$items = array_reverse( $items );
		}

		return new self( array_values( $items ) );
	}

	public function isEmpty(): bool {
		return $this->items === array();
	}

	public function isNotEmpty(): bool {
		return ! $this->isEmpty();
	}

	/**
	 * @return array<TKey, TValue>
	 */
	public function toArray(): array {
		return $this->items;
	}

	public function count(): int {
		return count( $this->items );
	}

	public function getIterator(): Traversable {
		return new ArrayIterator( $this->items );
	}

	public function offsetExists( mixed $offset ): bool {
		return isset( $this->items[ $offset ] );
	}

	public function offsetGet( mixed $offset ): mixed {
		return $this->items[ $offset ];
	}

	public function offsetSet( mixed $offset, mixed $value ): void {
		if ( $offset === null ) {
			$this->items[] = $value;

			return;
		}

		$this->items[ $offset ] = $value;
	}

	public function offsetUnset( mixed $offset ): void {
		unset( $this->items[ $offset ] );
	}
}
