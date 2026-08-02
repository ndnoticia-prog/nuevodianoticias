<?php

declare(strict_types=1);

namespace NDSeo\Schema;

use NDCore\Config\Config;
use NDSeo\Context\SeoContext;
use NDSeo\Schema\Contracts\SchemaProvider;

/**
 * Presente en todas las páginas: es el `publisher` referenciado por
 * {@see WebSiteSchema} y {@see NewsArticleSchema} vía `@id`.
 */
final class OrganizationSchema implements SchemaProvider {

	public function __construct( private readonly Config $config ) {
	}

	public function supports( SeoContext $context ): bool {
		return true;
	}

	public function build( SeoContext $context ): array {
		$name   = $this->config->get( 'seo.organization.name' );
		$logo   = $this->config->get( 'seo.organization.logo' );
		$sameAs = $this->config->get( 'seo.organization.same_as', array() );

		$schema = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => is_string( $name ) && $name !== '' ? $name : (string) get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);

		if ( is_string( $logo ) && $logo !== '' ) {
			$schema['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $logo,
			);
		}

		if ( is_array( $sameAs ) && $sameAs !== array() ) {
			$schema['sameAs'] = array_values( array_filter( $sameAs, 'is_string' ) );
		}

		return $schema;
	}
}
