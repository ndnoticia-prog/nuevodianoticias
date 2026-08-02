<?php

declare(strict_types=1);

namespace NDSeo\Schema;

use NDSeo\Context\SeoContext;
use NDSeo\Schema\Contracts\SchemaProvider;

final class WebSiteSchema implements SchemaProvider {

	public function supports( SeoContext $context ): bool {
		return ! $context->isSingular;
	}

	public function build( SeoContext $context ): array {
		return array(
			'@type'           => 'WebSite',
			'@id'             => home_url( '/#website' ),
			'url'             => home_url( '/' ),
			'name'            => (string) get_bloginfo( 'name' ),
			'publisher'       => array( '@id' => home_url( '/#organization' ) ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}' ),
				),
				'query-input' => 'required name=search_term_string',
			),
		);
	}
}
