<?php

declare(strict_types=1);

namespace NDSeo\Breadcrumbs;

final class Breadcrumb {

	public function __construct(
		public readonly string $label,
		public readonly string $url,
	) {
	}
}
