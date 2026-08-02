<?php

declare(strict_types=1);

namespace NDSeo\Schema;

use NDSeo\Context\SeoContext;
use NDSeo\Schema\Contracts\SchemaProvider;

/**
 * `NewsArticle` en lugar de `Article`: es el tipo que Google recomienda
 * para elegibilidad en Google News/Discover en sitios editoriales.
 */
final class NewsArticleSchema implements SchemaProvider {

	public function supports( SeoContext $context ): bool {
		return $context->isSingular && $context->post !== null;
	}

	public function build( SeoContext $context ): array {
		// SchemaOutput solo llama a build() cuando supports() devolvió true,
		// lo que ya garantiza $context->post !== null (ver supports() arriba).
		/** @var \WP_Post $post */
		$post = $context->post;

		$schema = array(
			'@type'            => 'NewsArticle',
			'@id'              => $context->canonicalUrl . '#article',
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => $context->canonicalUrl,
			),
			'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
			'datePublished'    => get_the_date( DATE_W3C, $post ),
			'dateModified'     => get_the_modified_date( DATE_W3C, $post ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', (int) $post->post_author ),
			),
			'publisher'        => array( '@id' => home_url( '/#organization' ) ),
		);

		if ( $context->description !== '' ) {
			$schema['description'] = $context->description;
		}

		if ( $context->imageUrl !== null ) {
			$schema['image'] = array( $context->imageUrl );
		}

		return $schema;
	}
}
