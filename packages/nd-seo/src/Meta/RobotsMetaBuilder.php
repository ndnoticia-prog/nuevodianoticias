<?php

declare(strict_types=1);

namespace NDSeo\Meta;

use NDSeo\Context\SeoContext;

/**
 * Construye el valor de la meta `robots`. Incluye `max-image-preview:large`
 * siempre que la página es indexable: es un requisito técnico de elegibilidad
 * para Google Discover.
 */
final class RobotsMetaBuilder {

	public function build( SeoContext $context ): string {
		if ( $context->noindex ) {
			return 'noindex, nofollow';
		}

		return implode(
			', ',
			array(
				'index',
				'follow',
				'max-image-preview:large',
				'max-snippet:-1',
				'max-video-preview:-1',
			)
		);
	}
}
