<?php

declare(strict_types=1);

namespace NDCore\Activation;

use NDCore\Application;
use NDCore\Installer\Installer;

final class Activator {

	public function activate( bool $networkWide ): void {
		$app = Application::getInstance();
		$app->boot();

		if ( $networkWide && is_multisite() ) {
			foreach ( get_sites( array( 'fields' => 'ids' ) ) as $siteId ) {
				switch_to_blog( (int) $siteId );
				$this->installOnCurrentSite( $app );
				restore_current_blog();
			}

			return;
		}

		$this->installOnCurrentSite( $app );
	}

	private function installOnCurrentSite( Application $app ): void {
		/** @var Installer $installer */
		$installer = $app->make( Installer::class );
		$installer->install();
	}
}
