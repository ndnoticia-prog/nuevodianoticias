<?php

declare(strict_types=1);

namespace NDCore;

use NDBuilder\Providers\BuilderServiceProvider;
use NDCore\Config\Config;
use NDCore\Container\Container;
use NDCore\Events\EventDispatcher;
use NDCore\Hooks\HookManager;
use NDCore\Providers\CoreServiceProvider;
use NDCore\Providers\RestApiServiceProvider;
use NDCore\Providers\RoutingServiceProvider;
use NDCore\Providers\ServiceProvider;
use Psr\Container\ContainerInterface;

/**
 * Punto de entrada único de ND Platform. Es el contenedor raíz (extiende
 * {@see Container}) y orquesta el ciclo de vida completo: configuración,
 * registro y arranque de los `ServiceProvider` de todos los paquetes
 * instalados.
 */
final class Application extends Container
{
    private static ?self $instance = null;

    private bool $booted = false;

    /**
     * @var list<ServiceProvider>
     */
    private array $loadedProviders = [];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Restablece la instancia singleton. Únicamente pensado para aislar
     * pruebas entre sí; no debe usarse en tiempo de ejecución de WordPress.
     */
    public static function flush(): void
    {
        self::$instance = null;
    }

    private function __construct()
    {
        $this->instance(self::class, $this);
        $this->instance(Container::class, $this);
        $this->instance(ContainerInterface::class, $this);
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $config = new Config();
        $config->loadDirectory($this->configDirectory());
        $this->instance(Config::class, $config);

        $hooks = new HookManager();
        $this->instance(HookManager::class, $hooks);

        $events = new EventDispatcher($this);
        $this->instance(EventDispatcher::class, $events);

        foreach ($this->resolveProviderClasses($config, $hooks) as $providerClass) {
            /** @var ServiceProvider $provider */
            $provider = new $providerClass($this);
            $provider->register();
            $this->loadedProviders[] = $provider;
        }

        foreach ($this->loadedProviders as $provider) {
            $provider->boot();
        }

        $this->booted = true;

        $hooks->doAction('nd_core/booted', $this);
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * @return list<ServiceProvider>
     */
    public function providers(): array
    {
        return $this->loadedProviders;
    }

    /**
     * Resuelve `config/` de forma independiente de las constantes que define
     * el archivo principal del plugin, para seguir funcionando en contextos
     * donde ese archivo no se incluye (p. ej. `uninstall.php`).
     */
    private function configDirectory(): string
    {
        return dirname(__DIR__) . '/config';
    }

    /**
     * @return list<class-string<ServiceProvider>>
     */
    private function resolveProviderClasses(Config $config, HookManager $hooks): array
    {
        $default = [
            CoreServiceProvider::class,
            RoutingServiceProvider::class,
            RestApiServiceProvider::class,
        ];

        // nd-builder viene empaquetado dentro del vendor/ de nd-core (ver composer.json),
        // por eso se registra aquí en lugar de requerir que cada tema lo haga.
        if (class_exists(BuilderServiceProvider::class)) {
            $default[] = BuilderServiceProvider::class;
        }

        /** @var list<class-string<ServiceProvider>> $configured */
        $configured = $config->get('app.providers', []);

        /** @var list<class-string<ServiceProvider>> $providers */
        $providers = $hooks->applyFilters(
            'nd_core/providers',
            array_values(array_unique([...$default, ...$configured]))
        );

        return $providers;
    }
}
