<?php

declare(strict_types=1);

namespace NDBuilder\Providers;

use NDBuilder\BlockRegistry;
use NDBuilder\Renderer;
use NDBuilder\TemplateBlockRenderer;
use NDCore\Providers\ServiceProvider;

/**
 * Registra el registro de bloques y el motor de renderizado en el
 * contenedor de la aplicación, con los tipos de bloque base de esta
 * versión (`hero`, `noticias`, `breaking`) apuntando a las plantillas que
 * el tema activo debe proveer bajo `template-parts/blocks/`.
 */
final class BuilderServiceProvider extends ServiceProvider {

	/**
	 * @var list<string>
	 */
	private const DEFAULT_BLOCK_TYPES = array( 'hero', 'noticias', 'breaking' );

	public function register(): void {
		$this->container->singleton(
			BlockRegistry::class,
			static function (): BlockRegistry {
				$registry = new BlockRegistry();

				foreach ( self::DEFAULT_BLOCK_TYPES as $type ) {
					$registry->register(
						$type,
						new TemplateBlockRenderer(
							array(
								"template-parts/blocks/{$type}",
							)
						)
					);
				}

				return $registry;
			}
		);

		$this->container->singleton( Renderer::class );
	}
}
