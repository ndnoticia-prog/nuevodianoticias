<?php

declare(strict_types=1);

namespace NDAds\Tests\Unit\Rendering;

use Brain\Monkey\Functions;
use NDAds\Domain\Campaign;
use NDAds\Domain\CampaignType;
use NDAds\Rendering\AdRenderer;
use NDAds\Tests\BrainMonkeyTestCase;

final class AdRendererTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
	}

	private function campaign( CampaignType $type, array $creative ): Campaign {
		return new Campaign(
			id: 5,
			name: 'Campaña',
			advertiser: 'Anunciante',
			type: $type,
			active: true,
			priority: 10,
			zones: array( 'header' ),
			categorySlugs: array(),
			creative: $creative,
			startsAt: null,
			endsAt: null,
		);
	}

	public function test_renders_adsense_snippet(): void {
		$html = ( new AdRenderer() )->render(
			$this->campaign(
				CampaignType::AdSense,
				array(
					'adsense_client' => 'ca-pub-123',
					'adsense_slot'   => '456',
				)
			)
		);

		self::assertStringContainsString( 'data-ad-client="ca-pub-123"', $html );
		self::assertStringContainsString( 'data-ad-slot="456"', $html );
		self::assertStringContainsString( 'nd-ad--adsense', $html );
	}

	public function test_renders_nothing_when_adsense_data_is_incomplete(): void {
		self::assertSame(
			'',
			( new AdRenderer() )->render(
				$this->campaign(
					CampaignType::AdSense,
					array(
						'adsense_client' => 'ca-pub-123',
					)
				)
			)
		);
	}

	public function test_renders_gam_slot_definition(): void {
		$html = ( new AdRenderer() )->render(
			$this->campaign(
				CampaignType::GoogleAdManager,
				array(
					'gam_unit_path' => '/1234/news/header',
				)
			)
		);

		self::assertStringContainsString( 'id="nd-gam-slot-5"', $html );
		self::assertStringContainsString( 'googletag.defineSlot("\/1234\/news\/header"', $html );
		self::assertStringContainsString( 'googletag.display("nd-gam-slot-5")', $html );
	}

	public function test_renders_raw_html_campaigns(): void {
		$html = ( new AdRenderer() )->render(
			$this->campaign(
				CampaignType::Html,
				array(
					'html' => '<p>Anuncio patrocinado</p>',
				)
			)
		);

		self::assertStringContainsString( '<p>Anuncio patrocinado</p>', $html );
	}

	public function test_renders_image_without_link(): void {
		$html = ( new AdRenderer() )->render(
			$this->campaign(
				CampaignType::Image,
				array(
					'image_url' => 'https://example.test/ad.jpg',
					'alt_text'  => 'Publicidad',
				)
			)
		);

		self::assertStringContainsString( '<img src="https://example.test/ad.jpg" alt="Publicidad"', $html );
		self::assertStringNotContainsString( '<a href', $html );
	}

	public function test_renders_image_wrapped_in_click_tracking_link(): void {
		Functions\when( 'home_url' )->justReturn( 'https://example.test/nd-ads/click/5' );

		$html = ( new AdRenderer() )->render(
			$this->campaign(
				CampaignType::Image,
				array(
					'image_url' => 'https://example.test/ad.jpg',
					'link_url'  => 'https://advertiser.test/landing',
				)
			)
		);

		self::assertStringContainsString( '<a href="https://example.test/nd-ads/click/5"', $html );
		self::assertStringContainsString( 'rel="sponsored noopener"', $html );
	}

	public function test_renders_video_source(): void {
		$html = ( new AdRenderer() )->render(
			$this->campaign(
				CampaignType::Video,
				array(
					'video_url' => 'https://example.test/ad.mp4',
				)
			)
		);

		self::assertStringContainsString( '<source src="https://example.test/ad.mp4">', $html );
	}

	public function test_renders_sponsored_with_label(): void {
		$html = ( new AdRenderer() )->render(
			$this->campaign(
				CampaignType::Sponsored,
				array(
					'html'          => '<p>Artículo patrocinado</p>',
					'sponsor_label' => 'Contenido patrocinado',
				)
			)
		);

		self::assertStringContainsString( 'nd-ad__sponsor-label', $html );
		self::assertStringContainsString( 'Contenido patrocinado', $html );
	}

	public function test_returns_empty_string_for_incomplete_creative(): void {
		self::assertSame( '', ( new AdRenderer() )->render( $this->campaign( CampaignType::Video, array() ) ) );
	}
}
