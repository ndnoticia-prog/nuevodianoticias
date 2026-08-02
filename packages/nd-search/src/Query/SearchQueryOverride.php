<?php

declare(strict_types=1);

namespace NDSearch\Query;

use NDSearch\Repository\SearchIndexRepository;
use WP_Query;

/**
 * Sustituye el `LIKE` por defecto de WordPress en la consulta principal de
 * búsqueda por los resultados, ya ordenados por relevancia, del índice
 * FULLTEXT propio — sin tocar `get_search_query()`/`is_search()` (que
 * siguen funcionando con normalidad para la plantilla de resultados).
 */
final class SearchQueryOverride {

	public function __construct( private readonly SearchIndexRepository $repository ) {
	}

	public function overridePostIds( WP_Query $query ): void {
		if ( $this->shouldSkip( $query ) ) {
			return;
		}

		$postIds = $this->repository->search( (string) $query->get( 's' ) );

		$query->set( 'post__in', $postIds === array() ? array( 0 ) : $postIds );
		$query->set( 'orderby', 'post__in' );
	}

	/**
	 * `posts_search` es el filtro que genera la cláusula SQL de
	 * título/contenido a partir de "s"; devolverlo vacío evita que WordPress
	 * añada su propio LIKE por encima del post__in ya acotado (que además
	 * podría excluir resultados que el FULLTEXT sí considera relevantes).
	 */
	public function neutralizeDefaultSearchSql( string $search, WP_Query $query ): string {
		return $this->shouldSkip( $query ) ? $search : '';
	}

	private function shouldSkip( WP_Query $query ): bool {
		return ! $query->is_search()
			|| ! $query->is_main_query()
			|| is_admin()
			|| trim( (string) $query->get( 's' ) ) === '';
	}
}
