<?php

declare(strict_types=1);

namespace NDSeo\Schema\Contracts;

use NDSeo\Context\SeoContext;

interface SchemaProvider {

	public function supports( SeoContext $context ): bool;

	/**
	 * @return array<string, mixed>
	 */
	public function build( SeoContext $context ): array;
}
