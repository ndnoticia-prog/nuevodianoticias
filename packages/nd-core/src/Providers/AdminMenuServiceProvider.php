<?php

declare(strict_types=1);

namespace NDCore\Providers;

use NDCore\Admin\AdminPage;
use NDCore\Admin\Contracts\RegistersAdminPages;
use NDCore\Hooks\HookManager;

/**
 * Registra el menú "ND Platform" del admin de WordPress y sus submenús,
 * recogidos de todos los paquetes vía el filtro `nd_core/admin_pages`
 * (mismo patrón que `nd_core/rest_controllers` de {@see RestApiServiceProvider}).
 *
 * La página con `position` más baja fija el slug del menú de nivel superior
 * (patrón estándar de WordPress: WooCommerce, Yoast, etc. hacen lo mismo en
 * vez de crear una página "índice" separada y duplicada) — por convención,
 * es la página de nd-workflow (`nd-platform`, `position: 10`).
 */
final class AdminMenuServiceProvider extends ServiceProvider {

	private const string MENU_CAPABILITY = 'read';
	private const string MENU_ICON       = 'dashicons-admin-site-alt3';
	private const int MENU_POSITION      = 30;

	public function register(): void {
	}

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->make( HookManager::class );

		$hooks->addAction( 'admin_menu', $this->registerMenu( ... ) );
	}

	public function registerMenu(): void {
		$pages = $this->collectPages();

		if ( $pages === array() ) {
			return;
		}

		usort( $pages, static fn ( AdminPage $a, AdminPage $b ): int => $a->position <=> $b->position );

		$first = $pages[0];

		add_menu_page(
			__( 'ND Platform', 'nd-core' ),
			__( 'ND Platform', 'nd-core' ),
			self::MENU_CAPABILITY,
			$first->slug,
			$first->render,
			self::MENU_ICON,
			self::MENU_POSITION
		);

		foreach ( $pages as $page ) {
			add_submenu_page(
				$first->slug,
				$page->pageTitle,
				$page->menuTitle,
				$page->capability,
				$page->slug,
				$page->render
			);
		}
	}

	/**
	 * @return list<AdminPage>
	 */
	private function collectPages(): array {
		/** @var HookManager $hooks */
		$hooks = $this->container->make( HookManager::class );

		/** @var list<class-string<RegistersAdminPages>> $registrarClasses */
		$registrarClasses = $hooks->applyFilters( 'nd_core/admin_pages', array() );

		$pages = array();

		foreach ( $registrarClasses as $registrarClass ) {
			/** @var RegistersAdminPages $registrar */
			$registrar = $this->container->make( $registrarClass );

			array_push( $pages, ...$registrar->adminPages() );
		}

		return $pages;
	}
}
