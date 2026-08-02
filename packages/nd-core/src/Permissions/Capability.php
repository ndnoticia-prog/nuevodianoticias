<?php

declare(strict_types=1);

namespace NDCore\Permissions;

/**
 * Capacidades personalizadas de ND Platform, registradas por
 * {@see PermissionManager} sobre los roles nativos de WordPress.
 */
final class Capability {

	public const string MANAGE_ND_SETTINGS  = 'manage_nd_settings';
	public const string EDIT_ND_WORKFLOW    = 'edit_nd_workflow';
	public const string PUBLISH_ND_ARTICLES = 'publish_nd_articles';
	public const string MANAGE_ND_ADS       = 'manage_nd_ads';
	public const string VIEW_ND_ANALYTICS   = 'view_nd_analytics';
	public const string USE_ND_AI           = 'use_nd_ai';

	private function __construct() {
	}
}
