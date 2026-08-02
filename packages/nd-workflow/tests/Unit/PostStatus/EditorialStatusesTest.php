<?php

declare(strict_types=1);

namespace NDWorkflow\Tests\Unit\PostStatus;

use Brain\Monkey\Functions;
use NDWorkflow\PostStatus\EditorialStatuses;
use NDWorkflow\Tests\BrainMonkeyTestCase;

final class EditorialStatusesTest extends BrainMonkeyTestCase {

	public function test_registers_both_custom_statuses(): void {
		Functions\when( '_x' )->returnArg( 1 );
		Functions\when( '_n_noop' )->justReturn( array() );

		Functions\expect( 'register_post_status' )
			->once()
			->with( EditorialStatuses::IN_REVIEW, \Mockery::type( 'array' ) );

		Functions\expect( 'register_post_status' )
			->once()
			->with( EditorialStatuses::NEEDS_CHANGES, \Mockery::type( 'array' ) );

		( new EditorialStatuses() )->register();

		// Las expectativas de Brain Monkey/Mockery arriba ya verifican el
		// comportamiento; esto solo evita que PHPUnit marque el test como
		// "risky" por no llamar a self::assert*() explícitamente.
		$this->addToAssertionCount( 1 );
	}
}
