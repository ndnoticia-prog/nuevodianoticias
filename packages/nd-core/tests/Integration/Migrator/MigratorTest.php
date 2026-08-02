<?php

declare(strict_types=1);

namespace NDCore\Tests\Integration\Migrator;

use NDCore\Database\DatabaseManager;
use NDCore\Migrator\Migration;
use NDCore\Migrator\Migrator;
use WP_UnitTestCase;

/**
 * Prueba Migrator contra un $wpdb/MySQL reales: es el caso documentado
 * como no cubrible con Brain Monkey desde alpha.1.
 */
final class MigratorTest extends WP_UnitTestCase {

	public static function wpTearDownAfterClass(): void {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'nd_test_migrator_widgets' );
	}

	/**
	 * Migrator::rollback() elimina la tabla (DDL, se confirma de inmediato
	 * en MySQL) y desmarca la migración con un DELETE (DML, que la
	 * transacción por test de WP_UnitTestCase revierte al terminar cada
	 * test): sin este saneamiento explícito, un test posterior podría
	 * heredar "migración marcada como aplicada" + "tabla inexistente" al
	 * mismo tiempo. Se limpia el estado real antes de cada test en vez de
	 * confiar en el rollback automático para código que hace DDL.
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'nd_test_migrator_widgets' );
		$wpdb->delete( $wpdb->prefix . 'nd_migrations', array( 'migration' => CreateWidgetsTableFixture::class ) );
	}

	private function migrator(): Migrator {
		return new Migrator( new DatabaseManager() );
	}

	public function test_run_applies_a_migration_exactly_once(): void {
		$migrator = $this->migrator();

		$migrator->run( array( CreateWidgetsTableFixture::class ) );
		$migrator->run( array( CreateWidgetsTableFixture::class ) );

		self::assertContains( CreateWidgetsTableFixture::class, $migrator->appliedMigrations() );

		global $wpdb;

		// La migración solo debe haberse aplicado una vez: si up() se
		// hubiera ejecutado dos veces sin idempotencia real esto seguiría
		// pasando, así que lo relevante es que la tabla exista y que
		// appliedMigrations() no tenga duplicados.
		$appliedCount = count(
			array_filter(
				$migrator->appliedMigrations(),
				static fn ( string $migration ): bool => $migration === CreateWidgetsTableFixture::class
			)
		);

		self::assertSame( 1, $appliedCount );

		$tableExists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'nd_test_migrator_widgets' )
		);

		self::assertNotNull( $tableExists );
	}

	public function test_rollback_reverts_an_applied_migration(): void {
		$migrator = $this->migrator();

		$migrator->run( array( CreateWidgetsTableFixture::class ) );
		$migrator->rollback( array( CreateWidgetsTableFixture::class ) );

		self::assertNotContains( CreateWidgetsTableFixture::class, $migrator->appliedMigrations() );

		global $wpdb;

		$tableExists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'nd_test_migrator_widgets' )
		);

		self::assertNull( $tableExists );
	}

	public function test_rollback_of_an_unapplied_migration_is_a_no_op(): void {
		$migrator = $this->migrator();

		$migrator->rollback( array( CreateWidgetsTableFixture::class ) );

		self::assertNotContains( CreateWidgetsTableFixture::class, $migrator->appliedMigrations() );
	}
}

/**
 * Fixture de migración real, usada únicamente por MigratorTest.
 */
final class CreateWidgetsTableFixture extends Migration {

	public function up( DatabaseManager $db ): void {
		global $wpdb;

		$table          = $wpdb->prefix . 'nd_test_migrator_widgets';
		$charsetCollate = $db->charsetCollate();

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta(
			"CREATE TABLE {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(191) NOT NULL,
				PRIMARY KEY (id)
			) {$charsetCollate};"
		);
	}

	public function down( DatabaseManager $db ): void {
		global $wpdb;

		$db->statement( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'nd_test_migrator_widgets' );
	}
}
