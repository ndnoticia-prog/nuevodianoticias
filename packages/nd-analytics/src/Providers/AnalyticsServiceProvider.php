<?php

declare(strict_types=1);

namespace NDAnalytics\Providers;

use NDBuilder\Events\BlockRendered;
use NDCore\Events\EventDispatcher;
use NDCore\Hooks\HookManager;
use NDCore\Providers\ServiceProvider;
use NDAnalytics\Admin\AnalyticsPanelAdminPage;
use NDAnalytics\Migrations\CreateImpressionsTable;
use NDAnalytics\Migrations\CreatePageviewsTable;
use NDAnalytics\Repository\AnalyticsRepository;
use NDAnalytics\RestApi\AnalyticsController;
use NDAnalytics\Tracking\ImpressionRecorder;
use NDAnalytics\Tracking\PageviewRecorder;
use NDAnalytics\Tracking\VisitorHasher;

final class AnalyticsServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( VisitorHasher::class );
		$this->container->singleton( PageviewRecorder::class );
		$this->container->singleton( ImpressionRecorder::class );
		$this->container->singleton( AnalyticsRepository::class );
		$this->container->singleton( AnalyticsController::class );
		$this->container->singleton( AnalyticsPanelAdminPage::class );
	}

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->make( HookManager::class );

		/** @var EventDispatcher $events */
		$events = $this->container->make( EventDispatcher::class );

		$hooks->addAction(
			'wp',
			function (): void {
				/** @var PageviewRecorder $recorder */
				$recorder = $this->container->make( PageviewRecorder::class );
				$recorder->recordForCurrentRequest();
			}
		);

		$events->listen(
			BlockRendered::class,
			function ( BlockRendered $event ): void {
				/** @var ImpressionRecorder $recorder */
				$recorder = $this->container->make( ImpressionRecorder::class );
				$recorder->handle( $event );
			}
		);

		$hooks->addFilter(
			'nd_core/rest_controllers',
			static function ( array $controllers ): array {
				$controllers[] = AnalyticsController::class;

				return $controllers;
			}
		);

		$hooks->addFilter(
			'nd_core/admin_pages',
			static function ( array $pages ): array {
				$pages[] = AnalyticsPanelAdminPage::class;

				return $pages;
			}
		);
	}

	public function migrations(): array {
		return array( CreatePageviewsTable::class, CreateImpressionsTable::class );
	}
}
