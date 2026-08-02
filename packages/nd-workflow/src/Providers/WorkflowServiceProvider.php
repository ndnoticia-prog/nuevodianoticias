<?php

declare(strict_types=1);

namespace NDWorkflow\Providers;

use NDCore\Hooks\HookManager;
use NDCore\Providers\ServiceProvider;
use NDWorkflow\Assignments\AssignmentManager;
use NDWorkflow\Calendar\CalendarRepository;
use NDWorkflow\Migrations\CreateEditorialNotesTable;
use NDWorkflow\Notes\EditorialNoteRepository;
use NDWorkflow\PostStatus\EditorialStatuses;
use NDWorkflow\RestApi\AssignmentController;
use NDWorkflow\RestApi\CalendarController;
use NDWorkflow\RestApi\NotesController;

final class WorkflowServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( EditorialNoteRepository::class );
		$this->container->singleton( AssignmentManager::class );
		$this->container->singleton( CalendarRepository::class );
		$this->container->singleton( EditorialStatuses::class );

		$this->container->singleton( NotesController::class );
		$this->container->singleton( AssignmentController::class );
		$this->container->singleton( CalendarController::class );
	}

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->make( HookManager::class );

		$hooks->addAction(
			'init',
			function (): void {
				/** @var EditorialStatuses $editorialStatuses */
				$editorialStatuses = $this->container->make( EditorialStatuses::class );
				$editorialStatuses->register();
			}
		);

		$hooks->addFilter(
			'nd_core/rest_controllers',
			static function ( array $controllers ): array {
				$controllers[] = NotesController::class;
				$controllers[] = AssignmentController::class;
				$controllers[] = CalendarController::class;

				return $controllers;
			}
		);
	}

	public function migrations(): array {
		return array( CreateEditorialNotesTable::class );
	}
}
