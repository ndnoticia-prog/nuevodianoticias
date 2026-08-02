<?php

declare(strict_types=1);

return [
    /*
     * Repositorio de GitHub ("owner/repo") consultado para comprobar nuevas
     * versiones, ya que ND Core no se distribuye por WordPress.org.
     */
    'repository' => defined('ND_UPDATE_REPOSITORY') ? ND_UPDATE_REPOSITORY : 'ndnoticia-prog/nd-platform',
    'release_asset_name' => 'nd-core.zip',
];
