<?php

declare(strict_types=1);

namespace NDAi\Tests\Unit\Providers;

use NDAi\AiManager;
use NDAi\Providers\AiServiceProvider;
use NDAi\RestApi\AiController;
use NDAi\Tasks\ContentAssistant;
use NDCore\Config\Config;
use NDCore\Container\Container;
use NDCore\Security\Encryption;
use PHPUnit\Framework\TestCase;

final class AiServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sodium')) {
            self::markTestSkipped('La extensión "sodium" no está disponible en este entorno.');
        }
    }

    /**
     * NDCore\Security\Encryption se registra aquí manualmente porque en
     * producción lo hace CoreServiceProvider (nd-core), que no corre en
     * este contenedor de prueba aislado.
     */
    public function test_register_binds_the_five_providers_and_the_manager(): void
    {
        $container = new Container();
        $container->instance(Config::class, new Config());
        $container->instance(Encryption::class, new Encryption(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));

        (new AiServiceProvider($container))->register();

        $manager = $container->make(AiManager::class);

        self::assertInstanceOf(AiManager::class, $manager);
        self::assertSame('openai', $manager->provider('openai')->key());
        self::assertSame('claude', $manager->provider('claude')->key());
        self::assertSame('gemini', $manager->provider('gemini')->key());
        self::assertSame('deepseek', $manager->provider('deepseek')->key());
        self::assertSame('local', $manager->provider('local')->key());

        self::assertInstanceOf(ContentAssistant::class, $container->make(ContentAssistant::class));
        self::assertInstanceOf(AiController::class, $container->make(AiController::class));
    }

    public function test_register_loads_the_ai_config_file(): void
    {
        $container = new Container();
        $config = new Config();
        $container->instance(Config::class, $config);
        $container->instance(Encryption::class, new Encryption(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));

        (new AiServiceProvider($container))->register();

        self::assertSame('gpt-4o-mini', $config->get('ai.models.openai'));
    }
}
