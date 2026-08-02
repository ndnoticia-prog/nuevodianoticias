<?php

declare(strict_types=1);

namespace NDTheme\Providers;

use NDCore\Hooks\HookManager;
use NDCore\Providers\ServiceProvider;
use NDTheme\Content\HomeContentProvider;

/**
 * Registra los servicios del tema (theme supports, menús, encolado de
 * assets compilados por Vite) usando exclusivamente {@see HookManager}
 * para hablar con WordPress, igual que el resto de ND Platform.
 */
final class ThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(HomeContentProvider::class);
    }

    public function boot(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->container->make(HookManager::class);

        $hooks->addAction('after_setup_theme', $this->setupTheme(...));
        $hooks->addAction('wp_enqueue_scripts', $this->enqueueAssets(...));
    }

    public function setupTheme(): void
    {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('custom-logo');
        add_theme_support('responsive-embeds');
        add_theme_support('html5', [
            'comment-list',
            'comment-form',
            'gallery',
            'caption',
            'style',
            'script',
            'navigation-widgets',
        ]);

        register_nav_menus([
            'primary' => __('Menú principal', 'nd-theme'),
            'footer' => __('Menú de pie de página', 'nd-theme'),
        ]);
    }

    public function enqueueAssets(): void
    {
        $cssPath = ND_THEME_DIR . 'dist/app.css';
        $jsPath = ND_THEME_DIR . 'dist/app.js';

        if (file_exists($cssPath)) {
            wp_enqueue_style('nd-theme', ND_THEME_URI . '/dist/app.css', [], (string) filemtime($cssPath));
        }

        if (file_exists($jsPath)) {
            wp_enqueue_script('nd-theme', ND_THEME_URI . '/dist/app.js', [], (string) filemtime($jsPath), true);
        }
    }
}
