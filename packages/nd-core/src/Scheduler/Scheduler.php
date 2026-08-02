<?php

declare(strict_types=1);

namespace NDCore\Scheduler;

use NDCore\Hooks\HookManager;

/**
 * Única puerta de entrada de ND Platform hacia WP-Cron.
 */
final class Scheduler {

	public function __construct( private readonly HookManager $hooks ) {
	}

	/**
	 * Registra un intervalo de cron personalizado (WordPress solo trae
	 * hourly/twicedaily/daily por defecto).
	 */
	public function registerSchedule( string $name, int $intervalSeconds, string $display ): void {
		$this->hooks->addFilter(
			'cron_schedules',
			static function ( array $schedules ) use ( $name, $intervalSeconds, $display ): array {
				$schedules[ $name ] = array(
					'interval' => $intervalSeconds,
					'display'  => $display,
				);

				return $schedules;
			}
		);
	}

	public function scheduleRecurring( string $hook, string $schedule, ?int $startTimestamp = null ): bool {
		if ( wp_next_scheduled( $hook ) !== false ) {
			return true;
		}

		return wp_schedule_event( $startTimestamp ?? time(), $schedule, $hook ) !== false;
	}

	/**
	 * @param list<mixed> $args
	 */
	public function scheduleOnce( string $hook, int $timestamp, array $args = array() ): bool {
		return wp_schedule_single_event( $timestamp, $hook, $args ) !== false;
	}

	public function unschedule( string $hook ): void {
		$timestamp = wp_next_scheduled( $hook );

		if ( $timestamp !== false ) {
			wp_unschedule_event( $timestamp, $hook );
		}

		wp_clear_scheduled_hook( $hook );
	}

	public function isScheduled( string $hook ): bool {
		return wp_next_scheduled( $hook ) !== false;
	}
}
