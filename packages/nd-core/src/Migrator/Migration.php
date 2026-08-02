<?php

declare(strict_types=1);

namespace NDCore\Migrator;

use NDCore\Database\DatabaseManager;

abstract class Migration {

	abstract public function up( DatabaseManager $db ): void;

	abstract public function down( DatabaseManager $db ): void;
}
