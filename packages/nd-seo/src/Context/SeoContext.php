<?php

declare(strict_types=1);

namespace NDSeo\Context;

use WP_Post;

/**
 * Descripción SEO de la página actual, resuelta una única vez por
 * {@see SeoContextResolver} y consumida por los constructores de meta tags,
 * Schema.org y breadcrumbs para no repetir la misma lógica de resolución de
 * WordPress en cada uno.
 */
final class SeoContext {

	public function __construct(
		public readonly string $title,
		public readonly string $description,
		public readonly string $canonicalUrl,
		public readonly ?string $imageUrl,
		public readonly string $type,
		public readonly bool $isSingular,
		public readonly bool $noindex,
		public readonly ?WP_Post $post = null,
	) {
	}
}
