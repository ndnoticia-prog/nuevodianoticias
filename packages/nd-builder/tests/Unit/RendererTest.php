<?php

declare(strict_types=1);

namespace NDBuilder\Tests\Unit;

use NDBuilder\Block;
use NDBuilder\BlockRegistry;
use NDBuilder\Contracts\BlockRenderer;
use NDBuilder\Events\BlockRendered;
use NDBuilder\Renderer;
use NDCore\Container\Container;
use NDCore\Events\EventDispatcher;
use PHPUnit\Framework\TestCase;

final class RendererTestUppercaseRenderer implements BlockRenderer {

	public function render( Block $block ): string {
		return strtoupper( (string) $block->attribute( 'text', '' ) );
	}
}

final class RendererTestEmptyRenderer implements BlockRenderer {

	public function render( Block $block ): string {
		return '';
	}
}

final class RendererTest extends TestCase {

	private function renderer( BlockRegistry $registry, ?EventDispatcher $events = null ): Renderer {
		return new Renderer( $registry, $events ?? new EventDispatcher( new Container() ) );
	}

	public function test_render_delegates_to_the_registered_renderer(): void {
		$registry = new BlockRegistry();
		$registry->register( 'hero', new RendererTestUppercaseRenderer() );

		$html = $this->renderer( $registry )->render( new Block( 'hero', 'hero-1', array( 'text' => 'titular' ) ) );

		self::assertSame( 'TITULAR', $html );
	}

	public function test_render_returns_empty_string_for_unregistered_type(): void {
		$html = $this->renderer( new BlockRegistry() )->render( new Block( 'unknown', 'x-1' ) );

		self::assertSame( '', $html );
	}

	public function test_render_many_concatenates_in_order(): void {
		$registry = new BlockRegistry();
		$registry->register( 'hero', new RendererTestUppercaseRenderer() );

		$html = $this->renderer( $registry )->renderMany(
			array(
				new Block( 'hero', 'a', array( 'text' => 'uno' ) ),
				new Block( 'hero', 'b', array( 'text' => 'dos' ) ),
			)
		);

		self::assertSame( 'UNODOS', $html );
	}

	public function test_render_dispatches_block_rendered_event_when_html_is_not_empty(): void {
		$registry = new BlockRegistry();
		$registry->register( 'hero', new RendererTestUppercaseRenderer() );

		$events   = new EventDispatcher( new Container() );
		$received = null;

		$events->listen(
			BlockRendered::class,
			function ( BlockRendered $event ) use ( &$received ): void {
				$received = $event;
			}
		);

		$block = new Block( 'hero', 'hero-1', array( 'text' => 'titular' ) );
		$this->renderer( $registry, $events )->render( $block );

		self::assertInstanceOf( BlockRendered::class, $received );
		self::assertSame( $block, $received->block );
		self::assertSame( 'TITULAR', $received->html );
	}

	public function test_render_does_not_dispatch_event_when_html_is_empty(): void {
		$registry = new BlockRegistry();
		$registry->register( 'hero', new RendererTestEmptyRenderer() );

		$events     = new EventDispatcher( new Container() );
		$dispatched = false;

		$events->listen(
			BlockRendered::class,
			function () use ( &$dispatched ): void {
				$dispatched = true;
			}
		);

		$this->renderer( $registry, $events )->render( new Block( 'hero', 'hero-1' ) );

		self::assertFalse( $dispatched );
	}
}
