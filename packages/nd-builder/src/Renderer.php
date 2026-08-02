<?php

declare(strict_types=1);

namespace NDBuilder;

use NDBuilder\Events\BlockRendered;
use NDCore\Events\EventDispatcher;

/**
 * Motor de renderizado server-side del constructor visual: traduce una
 * lista de {@see Block} a HTML delegando en el {@see BlockRegistry}, y
 * despacha {@see BlockRendered} por cada bloque efectivamente renderizado.
 */
final class Renderer {

	public function __construct(
		private readonly BlockRegistry $registry,
		private readonly EventDispatcher $events,
	) {
	}

	public function render( Block $block ): string {
		if ( ! $this->registry->has( $block->type ) ) {
			return '';
		}

		$html = $this->registry->rendererFor( $block->type )->render( $block );

		if ( $html !== '' ) {
			$this->events->dispatch( new BlockRendered( $block, $html ) );
		}

		return $html;
	}

	/**
	 * @param list<Block> $blocks
	 */
	public function renderMany( array $blocks ): string {
		return implode( '', array_map( $this->render( ... ), $blocks ) );
	}
}
