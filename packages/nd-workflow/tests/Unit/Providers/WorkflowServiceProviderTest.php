<?php

declare(strict_types=1);

namespace NDWorkflow\Tests\Unit\Providers;

use NDCore\Container\Container;
use NDWorkflow\Assignments\AssignmentManager;
use NDWorkflow\Calendar\CalendarRepository;
use NDWorkflow\Migrations\CreateEditorialNotesTable;
use NDWorkflow\PostStatus\EditorialStatuses;
use NDWorkflow\Providers\WorkflowServiceProvider;
use PHPUnit\Framework\TestCase;

final class WorkflowServiceProviderTest extends TestCase {

	/**
	 * Nota: no se resuelve NotesController/AssignmentController aquí porque
	 * arrastran EditorialNoteRepository -> DatabaseManager, que requiere un
	 * $wpdb real (WordPress) para no lanzar un TypeError; ver la limitación
	 * ya documentada para DatabaseManager/Migrator en nd-core.
	 */
	public function test_register_binds_services_that_do_not_need_a_database(): void {
		$container = new Container();
		( new WorkflowServiceProvider( $container ) )->register();

		self::assertInstanceOf( AssignmentManager::class, $container->make( AssignmentManager::class ) );
		self::assertInstanceOf( CalendarRepository::class, $container->make( CalendarRepository::class ) );
		self::assertInstanceOf( EditorialStatuses::class, $container->make( EditorialStatuses::class ) );
	}

	public function test_migrations_includes_editorial_notes_table(): void {
		$provider = new WorkflowServiceProvider( new Container() );

		self::assertSame( array( CreateEditorialNotesTable::class ), $provider->migrations() );
	}
}
