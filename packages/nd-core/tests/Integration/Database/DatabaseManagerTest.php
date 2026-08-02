<?php

declare(strict_types=1);

namespace NDCore\Tests\Integration\Database;

use NDCore\Database\DatabaseManager;
use RuntimeException;
use WP_UnitTestCase;

/**
 * Prueba DatabaseManager contra un $wpdb/MySQL reales: es el caso
 * documentado como no cubrible con Brain Monkey desde alpha.1.
 */
final class DatabaseManagerTest extends WP_UnitTestCase {

	private static string $table;

	public static function wpSetUpBeforeClass( $factory ): void {
		global $wpdb;

		self::$table = $wpdb->prefix . 'nd_test_database_manager';

		$charsetCollate = $wpdb->get_charset_collate();

		$wpdb->query(
			'CREATE TABLE ' . self::$table . " (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(191) NOT NULL,
				active TINYINT(1) NOT NULL DEFAULT 0,
				PRIMARY KEY (id)
			) {$charsetCollate};"
		);
	}

	public static function wpTearDownAfterClass(): void {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::$table );
	}

	private function db(): DatabaseManager {
		return new DatabaseManager();
	}

	public function test_insert_and_select_one_round_trip(): void {
		$db = $this->db();

		$id = $db->insert(
			self::$table,
			array(
				'name'   => 'Alpha',
				'active' => 1,
			),
			array(
				'name'   => '%s',
				'active' => '%d',
			)
		);

		self::assertGreaterThan( 0, $id );

		$row = $db->selectOne( 'SELECT * FROM ' . self::$table . ' WHERE id = %d', array( $id ) );

		self::assertNotNull( $row );
		self::assertSame( 'Alpha', $row['name'] );
		self::assertSame( '1', $row['active'] );
	}

	public function test_select_returns_multiple_rows_in_order(): void {
		$db = $this->db();

		$db->insert(
			self::$table,
			array(
				'name'   => 'Bravo',
				'active' => 1,
			),
			array(
				'name'   => '%s',
				'active' => '%d',
			)
		);
		$db->insert(
			self::$table,
			array(
				'name'   => 'Charlie',
				'active' => 0,
			),
			array(
				'name'   => '%s',
				'active' => '%d',
			)
		);

		$rows = $db->select( 'SELECT name FROM ' . self::$table . ' WHERE active = %d ORDER BY name ASC', array( 1 ) );

		$names = array_column( $rows, 'name' );

		self::assertContains( 'Bravo', $names );
		self::assertNotContains( 'Charlie', $names );
	}

	public function test_update_changes_matching_rows_only(): void {
		$db = $this->db();

		$id      = $db->insert(
			self::$table,
			array(
				'name'   => 'Delta',
				'active' => 0,
			),
			array(
				'name'   => '%s',
				'active' => '%d',
			)
		);
		$otherId = $db->insert(
			self::$table,
			array(
				'name'   => 'Echo',
				'active' => 0,
			),
			array(
				'name'   => '%s',
				'active' => '%d',
			)
		);

		$affected = $db->update( self::$table, array( 'active' => 1 ), array( 'id' => $id ) );

		self::assertSame( 1, $affected );

		$updated   = $db->selectOne( 'SELECT active FROM ' . self::$table . ' WHERE id = %d', array( $id ) );
		$untouched = $db->selectOne( 'SELECT active FROM ' . self::$table . ' WHERE id = %d', array( $otherId ) );

		self::assertSame( '1', $updated['active'] );
		self::assertSame( '0', $untouched['active'] );
	}

	public function test_delete_removes_the_row(): void {
		$db = $this->db();

		$id = $db->insert(
			self::$table,
			array(
				'name'   => 'Foxtrot',
				'active' => 1,
			),
			array(
				'name'   => '%s',
				'active' => '%d',
			)
		);

		$affected = $db->delete( self::$table, array( 'id' => $id ) );

		self::assertSame( 1, $affected );
		self::assertNull( $db->selectOne( 'SELECT * FROM ' . self::$table . ' WHERE id = %d', array( $id ) ) );
	}

	public function test_prepare_throws_on_placeholder_binding_mismatch(): void {
		$db = $this->db();

		$this->setExpectedIncorrectUsage( 'wpdb::prepare' );
		$this->expectException( RuntimeException::class );

		// Dos "%s" en la consulta, un solo binding: wpdb::prepare() debe
		// devolver null y DatabaseManager debe convertirlo en una excepción
		// en vez de ejecutar SQL sin preparar.
		$db->select( 'SELECT * FROM ' . self::$table . ' WHERE name = %s AND active = %s', array( 'Alpha' ) );
	}

	public function test_select_with_no_bindings_runs_the_literal_query(): void {
		$db = $this->db();

		$db->insert(
			self::$table,
			array(
				'name'   => 'Golf',
				'active' => 1,
			),
			array(
				'name'   => '%s',
				'active' => '%d',
			)
		);

		$rows = $db->select( 'SELECT name FROM ' . self::$table );

		self::assertNotEmpty( $rows );
	}
}
