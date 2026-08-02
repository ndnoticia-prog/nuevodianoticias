<?php

declare(strict_types=1);

namespace NDSeo\Schema;

use NDSeo\Breadcrumbs\BreadcrumbBuilder;
use NDSeo\Context\SeoContext;
use NDSeo\Schema\Contracts\SchemaProvider;

final class BreadcrumbListSchema implements SchemaProvider {

	public function __construct( private readonly BreadcrumbBuilder $breadcrumbs ) {
	}

	public function supports( SeoContext $context ): bool {
		// Un único elemento ("Inicio") no aporta nada como BreadcrumbList.
		return count( $this->breadcrumbs->build() ) > 1;
	}

	public function build( SeoContext $context ): array {
		$items    = array();
		$position = 1;

		foreach ( $this->breadcrumbs->build() as $crumb ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => $crumb->label,
				'item'     => $crumb->url,
			);
		}

		return array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}
}
