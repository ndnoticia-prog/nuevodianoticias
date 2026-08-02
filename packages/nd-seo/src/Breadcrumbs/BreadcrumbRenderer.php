<?php

declare(strict_types=1);

namespace NDSeo\Breadcrumbs;

/**
 * Versión HTML del mismo camino de migas de pan que
 * {@see \NDSeo\Schema\BreadcrumbListSchema} expone como JSON-LD.
 */
final class BreadcrumbRenderer {

	public function __construct( private readonly BreadcrumbBuilder $builder ) {
	}

	public function render(): string {
		$trail = $this->builder->build();

		if ( count( $trail ) < 2 ) {
			return '';
		}

		$lastIndex = count( $trail ) - 1;
		$items     = '';

		foreach ( $trail as $index => $crumb ) {
			$items .= '<li class="nd-breadcrumbs__item">';

			if ( $index === $lastIndex ) {
				$items .= '<span aria-current="page">' . esc_html( $crumb->label ) . '</span>';
			} else {
				$items .= '<a href="' . esc_url( $crumb->url ) . '">' . esc_html( $crumb->label ) . '</a>';
			}

			$items .= '</li>';
		}

		return sprintf(
			'<nav class="nd-breadcrumbs" aria-label="%s"><ol class="nd-breadcrumbs__list">%s</ol></nav>',
			esc_attr__( 'Ruta de navegación', 'nd-seo' ),
			$items
		);
	}
}
