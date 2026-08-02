<?php

declare(strict_types=1);

namespace NDCore\Tests\Integration\Queue;

use NDCore\Database\DatabaseManager;
use NDCore\Migrator\Migrations\CreateJobsTable;
use NDCore\Migrator\Migrator;
use NDCore\Queue\Job;
use NDCore\Queue\QueueManager;
use WP_UnitTestCase;

/**
 * Prueba QueueManager contra un $wpdb/MySQL reales (tabla `nd_jobs`
 * gestionada por Migrator): es el caso documentado como no cubrible con
 * Brain Monkey desde alpha.1.
 */
final class QueueManagerTest extends WP_UnitTestCase {

	public static function wpSetUpBeforeClass( $factory ): void {
		( new Migrator( new DatabaseManager() ) )->run( array( CreateJobsTable::class ) );
	}

	protected function setUp(): void {
		parent::setUp();

		EchoJobFixture::$handled = array();
	}

	private function queue(): QueueManager {
		return new QueueManager( new DatabaseManager() );
	}

	public function test_push_and_count_pending(): void {
		$queue = $this->queue();

		$queue->push( new EchoJobFixture( 'alpha' ) );
		$queue->push( new EchoJobFixture( 'beta' ) );

		self::assertSame( 2, $queue->countPending() );
		self::assertSame( 0, $queue->countFailed() );
	}

	public function test_process_due_jobs_executes_and_removes_successful_jobs(): void {
		$queue = $this->queue();

		$queue->push( new EchoJobFixture( 'gamma' ) );

		$processed = $queue->processDueJobs();

		self::assertSame( 1, $processed );
		self::assertSame( array( 'gamma' ), EchoJobFixture::$handled );
		self::assertSame( 0, $queue->countPending() );
	}

	public function test_delayed_jobs_are_not_processed_before_their_time(): void {
		$queue = $this->queue();

		$queue->push( new EchoJobFixture( 'delta' ), 3600 );

		$processed = $queue->processDueJobs();

		self::assertSame( 0, $processed );
		self::assertSame( array(), EchoJobFixture::$handled );
		self::assertSame( 1, $queue->countPending() );
	}

	public function test_failing_job_is_marked_failed_after_max_attempts(): void {
		$queue = $this->queue();

		$queue->push( new FailingJobFixture() );

		// maxAttempts = 1 en el fixture: un único intento ya debe marcarlo
		// como fallido definitivamente.
		$queue->processDueJobs();

		self::assertSame( 0, $queue->countPending() );
		self::assertSame( 1, $queue->countFailed() );
	}

	public function test_retry_failed_makes_the_job_pending_again(): void {
		$queue = $this->queue();

		$id = $queue->push( new FailingJobFixture() );

		$queue->processDueJobs();
		self::assertSame( 1, $queue->countFailed() );

		$retried = $queue->retryFailed( $id );

		self::assertTrue( $retried );
		self::assertSame( 1, $queue->countPending() );
		self::assertSame( 0, $queue->countFailed() );
	}
}

/**
 * Fixture de trabajo real, usada únicamente por QueueManagerTest.
 */
final class EchoJobFixture extends Job {

	/**
	 * @var list<string>
	 */
	public static array $handled = array();

	public function __construct( public string $label = '' ) {
	}

	public function handle(): void {
		self::$handled[] = $this->label;
	}
}

/**
 * Fixture de trabajo que siempre falla, usada únicamente por QueueManagerTest.
 */
final class FailingJobFixture extends Job {

	public int $maxAttempts = 1;

	public function handle(): void {
		throw new \RuntimeException( 'Fallo intencional del fixture de prueba.' );
	}
}
