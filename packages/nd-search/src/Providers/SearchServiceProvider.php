<?php

declare(strict_types=1);

namespace NDSearch\Providers;

use NDCore\Hooks\HookManager;
use NDCore\Providers\ServiceProvider;
use NDSearch\Indexing\SearchIndexer;
use NDSearch\Migrations\CreateSearchIndexTable;
use NDSearch\Query\SearchQueryOverride;
use NDSearch\Repository\SearchIndexRepository;
use WP_Post;
use WP_Query;

final class SearchServiceProvider extends ServiceProvider {

	public function register(): void {
		$this->container->singleton( SearchIndexRepository::class );
		$this->container->singleton( SearchIndexer::class );
		$this->container->singleton( SearchQueryOverride::class );
	}

	public function boot(): void {
		/** @var HookManager $hooks */
		$hooks = $this->container->make( HookManager::class );

		$hooks->addAction(
			'save_post',
			function ( int $postId, WP_Post $post ): void {
				/** @var SearchIndexer $indexer */
				$indexer = $this->container->make( SearchIndexer::class );
				$indexer->handleSaved( $postId, $post );
			},
			10,
			2
		);

		$hooks->addAction(
			'before_delete_post',
			function ( int $postId ): void {
				/** @var SearchIndexer $indexer */
				$indexer = $this->container->make( SearchIndexer::class );
				$indexer->handleDeleted( $postId );
			}
		);

		$hooks->addAction(
			'pre_get_posts',
			function ( WP_Query $query ): void {
				/** @var SearchQueryOverride $override */
				$override = $this->container->make( SearchQueryOverride::class );
				$override->overridePostIds( $query );
			}
		);

		$hooks->addFilter(
			'posts_search',
			function ( string $search, WP_Query $query ): string {
				/** @var SearchQueryOverride $override */
				$override = $this->container->make( SearchQueryOverride::class );

				return $override->neutralizeDefaultSearchSql( $search, $query );
			},
			10,
			2
		);
	}

	public function migrations(): array {
		return array( CreateSearchIndexTable::class );
	}
}
