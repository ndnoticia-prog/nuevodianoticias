<?php

declare(strict_types=1);

namespace NDCore\Admin\Contracts;

use NDCore\Admin\AdminPage;

/**
 * Implementado por cualquier `ServiceProvider` que deba registrar páginas
 * de submenú bajo "ND Platform", vía el filtro `nd_core/admin_pages`.
 */
interface RegistersAdminPages {

	/**
	 * @return list<AdminPage>
	 */
	public function adminPages(): array;
}
