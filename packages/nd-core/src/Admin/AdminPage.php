<?php

declare(strict_types=1);

namespace NDCore\Admin;

use Closure;

/**
 * Definición inmutable de una página de submenú bajo "ND Platform" en el
 * admin de WordPress, traducible a los argumentos que espera
 * `add_submenu_page()` vía {@see AdminMenuServiceProvider}.
 */
final class AdminPage {

	public readonly Closure $render;

	public function __construct(
		public readonly string $slug,
		public readonly string $pageTitle,
		public readonly string $menuTitle,
		public readonly string $capability,
		callable $render,
		public readonly int $position = 10,
	) {
		$this->render = Closure::fromCallable( $render );
	}
}
