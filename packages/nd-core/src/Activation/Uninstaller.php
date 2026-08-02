<?php

declare(strict_types=1);

namespace NDCore\Activation;

use NDCore\Application;
use NDCore\Installer\Installer;

final class Uninstaller {

	public function uninstall(): void {
		$app = Application::getInstance();
		$app->boot();

		if ( is_multisite() ) {
			foreach ( get_sites( array( 'fields' => 'ids' ) ) as $siteId ) {
				switch_to_blog( (int) $siteId );
				$this->uninstallOnCurrentSite( $app );
				restore_current_blog();
			}

			return;
		}

		$this->uninstallOnCurrentSite( $app );
	}

	private function uninstallOnCurrentSite( Application $app ): void {
		/** @var Installer $installer */
		$installer = $app->make( Installer::class );
		$installer->uninstall();
	}
}
