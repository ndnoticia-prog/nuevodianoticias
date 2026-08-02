<?php

declare(strict_types=1);

namespace NDCore\Tests\Unit\Events;

use NDCore\Container\Container;
use NDCore\Events\Event;
use NDCore\Events\EventDispatcher;
use NDCore\Events\Listener;
use PHPUnit\Framework\TestCase;

final class EventDispatcherTestEvent extends Event {

	public function __construct( public readonly string $articleTitle ) {
	}
}

final class EventDispatcherTestListener implements Listener {

	public static bool $wasCalled = false;

	public function handle( Event $event ): void {
		self::$wasCalled = true;
	}
}

final class EventDispatcherTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		EventDispatcherTestListener::$wasCalled = false;
	}

	public function test_closure_listener_receives_event(): void {
		$dispatcher = new EventDispatcher( new Container() );
		$received   = null;

		$dispatcher->listen(
			EventDispatcherTestEvent::class,
			function ( EventDispatcherTestEvent $event ) use ( &$received ): void {
				$received = $event->articleTitle;
			}
		);

		$dispatcher->dispatch( new EventDispatcherTestEvent( 'Titular de prueba' ) );

		self::assertSame( 'Titular de prueba', $received );
	}

	public function test_class_string_listener_is_resolved_through_container(): void {
		$dispatcher = new EventDispatcher( new Container() );
		$dispatcher->listen( EventDispatcherTestEvent::class, EventDispatcherTestListener::class );

		$dispatcher->dispatch( new EventDispatcherTestEvent( 'Titular de prueba' ) );

		self::assertTrue( EventDispatcherTestListener::$wasCalled );
	}

	public function test_listeners_run_in_priority_order(): void {
		$dispatcher = new EventDispatcher( new Container() );
		$order      = array();

		$dispatcher->listen(
			EventDispatcherTestEvent::class,
			static function () use ( &$order ): void {
				$order[] = 'second';
			},
			20
		);

		$dispatcher->listen(
			EventDispatcherTestEvent::class,
			static function () use ( &$order ): void {
				$order[] = 'first';
			},
			10
		);

		$dispatcher->dispatch( new EventDispatcherTestEvent( 'x' ) );

		self::assertSame( array( 'first', 'second' ), $order );
	}

	public function test_stopped_propagation_prevents_further_listeners(): void {
		$dispatcher   = new EventDispatcher( new Container() );
		$secondCalled = false;

		$dispatcher->listen(
			EventDispatcherTestEvent::class,
			static function ( EventDispatcherTestEvent $event ): void {
				$event->stopPropagation();
			},
			10
		);

		$dispatcher->listen(
			EventDispatcherTestEvent::class,
			static function () use ( &$secondCalled ): void {
				$secondCalled = true;
			},
			20
		);

		$dispatcher->dispatch( new EventDispatcherTestEvent( 'x' ) );

		self::assertFalse( $secondCalled );
	}

	public function test_has_listeners(): void {
		$dispatcher = new EventDispatcher( new Container() );

		self::assertFalse( $dispatcher->hasListeners( EventDispatcherTestEvent::class ) );

		$dispatcher->listen( EventDispatcherTestEvent::class, static fn (): null => null );

		self::assertTrue( $dispatcher->hasListeners( EventDispatcherTestEvent::class ) );
	}
}
