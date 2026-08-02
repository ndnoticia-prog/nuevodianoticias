<?php

declare(strict_types=1);

namespace NDCore\Providers;

use NDCore\Container\Container;

/**
 * Clase base que todo paquete de ND Platform debe extender para integrarse
 * con el ciclo de vida de la aplicación.
 */
abstract class ServiceProvider
{
    public function __construct(protected readonly Container $container)
    {
    }

    /**
     * Registra bindings en el contenedor. No debe resolver ni usar servicios
     * de otros providers aquí: en esta fase no hay garantía de que ya estén
     * registrados. Usar {@see boot()} para eso.
     */
    abstract public function register(): void;

    /**
     * Se ejecuta después de que TODOS los providers han sido registrados.
     * Aquí es seguro resolver dependencias de otros paquetes y registrar
     * hooks, rutas o comandos.
     */
    public function boot(): void
    {
    }

    /**
     * Clases de migración (`NDCore\Migrator\Migration`) que este paquete
     * necesita aplicar. Se ejecutan en el orden devuelto.
     *
     * @return list<class-string<\NDCore\Migrator\Migration>>
     */
    public function migrations(): array
    {
        return [];
    }
}
