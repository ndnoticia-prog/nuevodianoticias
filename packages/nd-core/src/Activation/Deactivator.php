<?php

declare(strict_types=1);

namespace NDCore\Activation;

use NDCore\Application;
use NDCore\Scheduler\Scheduler;

final class Deactivator {

	private const QUEUE_TICK_HOOK = 'nd_core/queue/tick';

	public function deactivate( bool $networkWide ): void {
		$app = Application::getInstance();
		$app->boot();

		if ( $networkWide && is_multisite() ) {
			foreach ( get_sites( array( 'fields' => 'ids' ) ) as $siteId ) {
				switch_to_blog( (int) $siteId );
				$this->unscheduleOnCurrentSite( $app );
				restore_current_blog();
			}

			return;
		}

		$this->unscheduleOnCurrentSite( $app );
	}

	private function unscheduleOnCurrentSite( Application $app ): void {
		/** @var Scheduler $scheduler */
		$scheduler = $app->make( Scheduler::class );
		$scheduler->unschedule( self::QUEUE_TICK_HOOK );
	}
}
