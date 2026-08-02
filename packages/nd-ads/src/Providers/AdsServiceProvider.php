<?php

declare(strict_types=1);

namespace NDAds\Providers;

use NDAds\Admin\CampaignsAdminPage;
use NDAds\Http\ClickRedirectController;
use NDAds\Migrations\CreateAdCampaignsTable;
use NDAds\Migrations\CreateAdEventsTable;
use NDAds\Rendering\AdRenderer;
use NDAds\Rendering\AdZoneRenderer;
use NDAds\Repository\CampaignRepository;
use NDAds\RestApi\CampaignController;
use NDAds\Selection\CampaignSelector;
use NDAds\Shortcode\AdShortcode;
use NDAds\Stats\StatsRecorder;
use NDAds\Stats\StatsRepository;
use NDCore\Hooks\HookManager;
use NDCore\Providers\ServiceProvider;

final class AdsServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( CampaignRepository::class );
		$this->container->singleton( CampaignSelector::class );
		$this->container->singleton( StatsRecorder::class );
		$this->container->singleton( StatsRepository::class );
		$this->container->singleton( AdRenderer::class );
		$this->container->singleton( AdZoneRenderer::class );
		$this->container->singleton( ClickRedirectController::class );
		$this->container->singleton( AdShortcode::class );
		$this->container->singleton( CampaignController::class );
		$this->container->singleton( CampaignsAdminPage::class );
	}

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->make( HookManager::class );

		$hooks->addAction(
			'init',
			function (): void {
				/** @var AdShortcode $shortcode */
				$shortcode = $this->container->make( AdShortcode::class );
				add_shortcode( 'nd_ad', $shortcode->render( ... ) );
			}
		);

		/** @var ClickRedirectController $click */
		$click = $this->container->make( ClickRedirectController::class );

		$hooks->addAction( 'init', $click->registerRewriteRule( ... ) );
		$hooks->addFilter( 'query_vars', $click->registerQueryVar( ... ) );
		$hooks->addAction( 'template_redirect', $click->maybeRedirect( ... ) );

		$hooks->addFilter(
			'nd_core/rest_controllers',
			static function ( array $controllers ): array {
				$controllers[] = CampaignController::class;

				return $controllers;
			}
		);

		$hooks->addFilter(
			'nd_core/admin_pages',
			static function ( array $pages ): array {
				$pages[] = CampaignsAdminPage::class;

				return $pages;
			}
		);
	}

	public function migrations(): array {
		return array( CreateAdCampaignsTable::class, CreateAdEventsTable::class );
	}
}
