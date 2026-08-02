<?php

declare(strict_types=1);

namespace NDCore\Providers;

use NDCore\Cache\CacheManager;
use NDCore\Config\Config;
use NDCore\Database\DatabaseManager;
use NDCore\Filesystem\Filesystem;
use NDCore\Hooks\HookManager;
use NDCore\Http\Client;
use NDCore\Migrator\Migrations\CreateJobsTable;
use NDCore\Migrator\Migrator;
use NDCore\Permissions\PermissionManager;
use NDCore\Queue\QueueManager;
use NDCore\Scheduler\Scheduler;
use NDCore\Security\Encryption;
use NDCore\Security\Nonce;
use NDCore\Security\Sanitizer;
use NDCore\Settings\SettingsRepository;
use NDCore\Updater\UpdateChecker;

/**
 * Registra los servicios transversales del núcleo: base de datos, caché,
 * colas, scheduler, seguridad, permisos y ajustes.
 */
final class CoreServiceProvider extends ServiceProvider
{
    private const QUEUE_TICK_HOOK = 'nd_core/queue/tick';
    private const QUEUE_SCHEDULE = 'nd_every_minute';
    private const QUEUE_BATCH_SIZE = 20;

    public function register(): void
    {
        $this->container->singleton(DatabaseManager::class, static fn (): DatabaseManager => new DatabaseManager());
        $this->container->singleton(Migrator::class);
        $this->container->singleton(Filesystem::class);
        $this->container->singleton(Client::class);
        $this->container->singleton(Nonce::class);
        $this->container->singleton(Sanitizer::class);
        $this->container->singleton(Encryption::class, static fn (): Encryption => Encryption::fromWordPressSalts());
        $this->container->singleton(PermissionManager::class);
        $this->container->singleton(SettingsRepository::class);
        $this->container->singleton(CacheManager::class);
        $this->container->singleton(QueueManager::class);
        $this->container->singleton(Scheduler::class);

        if (defined('NDCORE_PLUGIN_FILE') && defined('NDCORE_VERSION')) {
            $this->container->singleton(UpdateChecker::class, function (): UpdateChecker {
                /** @var Config $config */
                $config = $this->container->make(Config::class);

                return new UpdateChecker(
                    http: $this->container->make(Client::class),
                    cache: $this->container->make(CacheManager::class),
                    pluginFile: NDCORE_PLUGIN_FILE,
                    currentVersion: NDCORE_VERSION,
                    repository: (string) $config->get('updater.repository', 'ndnoticia-prog/nd-platform'),
                    releaseAssetName: (string) $config->get('updater.release_asset_name', 'nd-core.zip'),
                );
            });
        }
    }

    public function boot(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->container->make(HookManager::class);

        /** @var Scheduler $scheduler */
        $scheduler = $this->container->make(Scheduler::class);

        $scheduler->registerSchedule(self::QUEUE_SCHEDULE, MINUTE_IN_SECONDS, 'ND Platform: cada minuto');

        $hooks->addAction('init', static function () use ($scheduler): void {
            $scheduler->scheduleRecurring(self::QUEUE_TICK_HOOK, self::QUEUE_SCHEDULE);
        });

        $hooks->addAction(self::QUEUE_TICK_HOOK, function (): void {
            /** @var QueueManager $queue */
            $queue = $this->container->make(QueueManager::class);
            $queue->processDueJobs(self::QUEUE_BATCH_SIZE);
        });

        if (defined('NDCORE_PLUGIN_FILE') && defined('NDCORE_VERSION')) {
            $this->container->make(UpdateChecker::class)->register($hooks);
        }
    }

    public function migrations(): array
    {
        return [CreateJobsTable::class];
    }
}
