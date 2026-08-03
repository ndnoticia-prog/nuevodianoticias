<?php

declare(strict_types=1);

namespace NDWorkflow\Tests\Integration\Notes;

use NDCore\Database\DatabaseManager;
use NDWorkflow\Notes\EditorialNote;
use NDWorkflow\Notes\EditorialNoteRepository;
use WP_UnitTestCase;

/**
 * Prueba EditorialNoteRepository contra un MySQL real: es el caso
 * documentado como no cubrible con Brain Monkey desde alpha.1.
 *
 * La tabla `editorial_notes` NO se crea/destruye aquí: ya existe para
 * cuando esta clase arranca, creada automáticamente por
 * NDCore\Providers\CoreServiceProvider::maybeRunUpgrade() en `init` (ver
 * el docblock de SearchIndexRepositoryTest en nd-search para la
 * explicación completa de por qué gestionar su ciclo de vida aquí
 * rompería esa invariante).
 */
final class EditorialNoteRepositoryTest extends WP_UnitTestCase {

	private function repository(): EditorialNoteRepository {
		return new EditorialNoteRepository( new DatabaseManager() );
	}

	public function test_create_then_for_post_returns_it_with_correct_fields(): void {
		$repository = $this->repository();

		$note = $repository->create( 101, 5, 'Falta la fuente de este dato', EditorialNote::TYPE_CORRECTION_REQUEST );

		$notes = $repository->forPost( 101 );

		self::assertCount( 1, $notes );
		self::assertSame( $note->id, $notes[0]->id );
		self::assertSame( 101, $notes[0]->postId );
		self::assertSame( 5, $notes[0]->authorId );
		self::assertSame( EditorialNote::TYPE_CORRECTION_REQUEST, $notes[0]->type );
		self::assertSame( 'Falta la fuente de este dato', $notes[0]->body );
	}

	public function test_for_post_with_no_notes_returns_empty(): void {
		self::assertSame( array(), $this->repository()->forPost( 999 ) );
	}

	public function test_delete_removes_it_and_for_post_no_longer_returns_it(): void {
		$repository = $this->repository();

		$note = $repository->create( 102, 5, 'Nota temporal' );

		self::assertCount( 1, $repository->forPost( 102 ) );

		$deleted = $repository->delete( $note->id );

		self::assertTrue( $deleted );
		self::assertSame( array(), $repository->forPost( 102 ) );
	}

	public function test_for_post_scopes_to_the_given_post_id_only(): void {
		$repository = $this->repository();

		$repository->create( 201, 5, 'Nota del post 201' );
		$repository->create( 202, 5, 'Nota del post 202' );

		$notesFor201 = $repository->forPost( 201 );

		self::assertCount( 1, $notesFor201 );
		self::assertSame( 'Nota del post 201', $notesFor201[0]->body );
	}
}
