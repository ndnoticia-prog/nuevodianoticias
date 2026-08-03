<?php

declare(strict_types=1);

namespace NDWorkflow\Tests\Integration\Calendar;

use NDWorkflow\Calendar\CalendarRepository;
use WP_UnitTestCase;

/**
 * Prueba CalendarRepository contra WordPress y MySQL reales: consulta
 * wp_posts a través de WP_Query con un date_query y una lista de estados
 * editoriales (incluidos los dos personalizados de nd-workflow), algo que
 * Brain Monkey no puede simular.
 */
final class CalendarRepositoryTest extends WP_UnitTestCase {

	public static function wpSetUpBeforeClass( $factory ): void {
		// get_edit_post_link() solo devuelve una URL si el usuario actual
		// puede editar el post; sin un usuario con capacidades, siempre
		// vuelve null y esa parte del contrato quedaría sin probar.
		wp_set_current_user( $factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	private function repository(): CalendarRepository {
		return new CalendarRepository();
	}

	/**
	 * @param array<string, list<array{id: int, title: string, status: string, edit_link: string}>> $byDate
	 * @return list<int>
	 */
	private function allIds( array $byDate ): array {
		$ids = array();

		foreach ( $byDate as $entries ) {
			array_push( $ids, ...array_column( $entries, 'id' ) );
		}

		return $ids;
	}

	public function test_posts_within_the_queried_month_are_returned(): void {
		$post = self::factory()->post->create_and_get(
			array(
				'post_date'   => '2026-03-15 10:00:00',
				'post_status' => 'publish',
				'post_title'  => 'Dentro del mes',
			)
		);

		$byDate = $this->repository()->postsForMonth( 2026, 3 );

		self::assertArrayHasKey( '2026-03-15', $byDate );

		$ids = array_column( $byDate['2026-03-15'], 'id' );

		self::assertContains( $post->ID, $ids );

		$entry = $byDate['2026-03-15'][ array_search( $post->ID, $ids, true ) ];

		self::assertSame( 'Dentro del mes', $entry['title'] );
		self::assertSame( 'publish', $entry['status'] );
		self::assertStringContainsString( 'post.php', $entry['edit_link'] );
	}

	public function test_posts_outside_the_queried_month_are_excluded(): void {
		$post = self::factory()->post->create_and_get(
			array(
				'post_date'   => '2026-04-01 10:00:00',
				'post_status' => 'publish',
				'post_title'  => 'Fuera del mes',
			)
		);

		$byDate = $this->repository()->postsForMonth( 2026, 3 );

		self::assertNotContains( $post->ID, $this->allIds( $byDate ) );
	}

	public function test_posts_in_a_status_outside_the_editorial_workflow_are_excluded(): void {
		$post = self::factory()->post->create_and_get(
			array(
				'post_date'   => '2026-03-20 10:00:00',
				'post_status' => 'trash',
				'post_title'  => 'En la papelera',
			)
		);

		$byDate = $this->repository()->postsForMonth( 2026, 3 );

		self::assertNotContains( $post->ID, $this->allIds( $byDate ) );
	}

	public function test_posts_in_draft_and_custom_editorial_statuses_are_included(): void {
		$draft = self::factory()->post->create_and_get(
			array(
				'post_date'   => '2026-03-05 10:00:00',
				'post_status' => 'draft',
				'post_title'  => 'Borrador',
			)
		);

		$inReview = self::factory()->post->create_and_get(
			array(
				'post_date'   => '2026-03-06 10:00:00',
				'post_status' => 'nd_in_review',
				'post_title'  => 'En revisión editorial',
			)
		);

		$byDate = $this->repository()->postsForMonth( 2026, 3 );

		self::assertContains( $draft->ID, array_column( $byDate['2026-03-05'], 'id' ) );
		self::assertContains( $inReview->ID, array_column( $byDate['2026-03-06'], 'id' ) );
	}
}
