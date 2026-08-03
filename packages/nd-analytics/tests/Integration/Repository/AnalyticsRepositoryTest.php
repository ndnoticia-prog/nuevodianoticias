<?php

declare(strict_types=1);

namespace NDAnalytics\Tests\Integration\Repository;

use NDAnalytics\Repository\AnalyticsRepository;
use NDCore\Database\DatabaseManager;
use WP_UnitTestCase;

/**
 * Prueba AnalyticsRepository contra un $wpdb/MySQL reales: topAuthors() y
 * topCategories() cruzan `analytics_pageviews` con wp_posts y
 * wp_term_relationships/wp_term_taxonomy/wp_terms respectivamente, el caso
 * documentado como no cubrible con Brain Monkey desde alpha.4.
 *
 * Las tablas `analytics_pageviews`/`analytics_impressions` NO se crean ni
 * se destruyen aquí: ya existen para cuando esta clase arranca, creadas
 * automáticamente por NDCore\Providers\CoreServiceProvider::maybeRunUpgrade()
 * en `init` (ver el docblock de SearchIndexRepositoryTest en nd-search para
 * la explicación completa de por qué gestionar su ciclo de vida aquí
 * rompería la base de datos de pruebas compartida entre invocaciones del
 * proceso).
 */
final class AnalyticsRepositoryTest extends WP_UnitTestCase {

	private function repository(): AnalyticsRepository {
		return new AnalyticsRepository( new DatabaseManager() );
	}

	private function insertPageview( int $postId, string $viewedAt, string $visitorHash = 'visitor-a' ): void {
		$db = new DatabaseManager();

		$db->insert(
			$db->table( 'analytics_pageviews' ),
			array(
				'post_id'      => $postId,
				'url'          => 'https://example.test/post',
				'referrer'     => '',
				'visitor_hash' => $visitorHash,
				'viewed_at'    => $viewedAt,
			),
			array(
				'post_id'      => '%d',
				'url'          => '%s',
				'referrer'     => '%s',
				'visitor_hash' => '%s',
				'viewed_at'    => '%s',
			)
		);
	}

	private function insertImpression( int $postId, string $viewedAt, string $context = 'hero' ): void {
		$db = new DatabaseManager();

		$db->insert(
			$db->table( 'analytics_impressions' ),
			array(
				'post_id'   => $postId,
				'context'   => $context,
				'viewed_at' => $viewedAt,
			),
			array(
				'post_id'   => '%d',
				'context'   => '%s',
				'viewed_at' => '%s',
			)
		);
	}

	private function daysAgo( int $days ): string {
		return gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
	}

	private function minutesAgo( int $minutes ): string {
		return gmdate( 'Y-m-d H:i:s', time() - $minutes * MINUTE_IN_SECONDS );
	}

	public function test_top_posts_orders_by_views_descending(): void {
		$postA = self::factory()->post->create();
		$postB = self::factory()->post->create();

		$this->insertPageview( $postA, $this->daysAgo( 1 ) );
		$this->insertPageview( $postA, $this->daysAgo( 1 ) );
		$this->insertPageview( $postB, $this->daysAgo( 1 ) );

		$result = $this->repository()->topPosts( 7, 10 );

		self::assertSame(
			array(
				array(
					'post_id' => $postA,
					'views'   => 2,
				),
				array(
					'post_id' => $postB,
					'views'   => 1,
				),
			),
			array_values(
				array_filter(
					$result,
					static fn ( array $row ): bool => in_array( $row['post_id'], array( $postA, $postB ), true )
				)
			)
		);
	}

	public function test_top_posts_excludes_rows_outside_the_days_window(): void {
		$post = self::factory()->post->create();

		$this->insertPageview( $post, $this->daysAgo( 1 ) );
		$this->insertPageview( $post, $this->daysAgo( 10 ) );

		$result = $this->repository()->topPosts( 7, 10 );

		$row = current( array_filter( $result, static fn ( array $row ): bool => $row['post_id'] === $post ) );

		self::assertSame( 1, $row['views'] );
	}

	public function test_active_now_counts_distinct_visitors_and_posts_within_the_window(): void {
		$post = self::factory()->post->create();

		$this->insertPageview( $post, $this->minutesAgo( 1 ), 'visitor-a' );
		$this->insertPageview( $post, $this->minutesAgo( 1 ), 'visitor-b' );
		// Fuera de la ventana de 5 minutos: no debe contarse.
		$this->insertPageview( $post, $this->minutesAgo( 10 ), 'visitor-c' );

		$result = $this->repository()->activeNow( 5 );

		self::assertSame( 2, $result['visitors'] );

		$row = current( array_filter( $result['posts'], static fn ( array $row ): bool => $row['post_id'] === $post ) );

		self::assertSame( 2, $row['views'] );
	}

	public function test_top_authors_attributes_views_via_the_real_wp_posts_join(): void {
		$authorA = self::factory()->user->create( array( 'role' => 'author' ) );
		$authorB = self::factory()->user->create( array( 'role' => 'author' ) );

		$postA = self::factory()->post->create( array( 'post_author' => $authorA ) );
		$postB = self::factory()->post->create( array( 'post_author' => $authorB ) );

		$this->insertPageview( $postA, $this->daysAgo( 1 ) );
		$this->insertPageview( $postA, $this->daysAgo( 1 ) );
		$this->insertPageview( $postB, $this->daysAgo( 1 ) );

		$result = $this->repository()->topAuthors( 30, 10 );

		$byAuthor = array();

		foreach ( $result as $row ) {
			if ( in_array( $row['author_id'], array( $authorA, $authorB ), true ) ) {
				$byAuthor[ $row['author_id'] ] = $row['views'];
			}
		}

		self::assertSame( 2, $byAuthor[ $authorA ] );
		self::assertSame( 1, $byAuthor[ $authorB ] );
	}

	public function test_top_categories_attributes_views_via_the_real_term_relationships_join(): void {
		$categoryId = self::factory()->category->create( array( 'name' => 'Política' ) );
		$post       = self::factory()->post->create();

		wp_set_post_categories( $post, array( $categoryId ) );

		$this->insertPageview( $post, $this->daysAgo( 1 ) );
		$this->insertPageview( $post, $this->daysAgo( 1 ) );

		$result = $this->repository()->topCategories( 30, 10 );

		$row = current( array_filter( $result, static fn ( array $row ): bool => $row['term_id'] === $categoryId ) );

		self::assertNotFalse( $row );
		self::assertSame( 'Política', $row['name'] );
		self::assertSame( 2, $row['views'] );
	}

	public function test_ctr_for_post_computes_the_percentage_of_views_over_impressions(): void {
		$post = self::factory()->post->create();

		$this->insertPageview( $post, $this->daysAgo( 1 ) );
		$this->insertImpression( $post, $this->daysAgo( 1 ) );
		$this->insertImpression( $post, $this->daysAgo( 1 ) );
		$this->insertImpression( $post, $this->daysAgo( 1 ) );
		$this->insertImpression( $post, $this->daysAgo( 1 ) );

		$result = $this->repository()->ctrForPost( $post, 30 );

		self::assertSame(
			array(
				'post_id'     => $post,
				'views'       => 1,
				'impressions' => 4,
				'ctr'         => 25.0,
			),
			$result
		);
	}

	public function test_ctr_for_post_returns_zero_ctr_when_there_are_no_impressions(): void {
		$post = self::factory()->post->create();

		$this->insertPageview( $post, $this->daysAgo( 1 ) );

		$result = $this->repository()->ctrForPost( $post, 30 );

		self::assertSame( 0.0, $result['ctr'] );
	}
}
