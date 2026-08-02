<?php

declare(strict_types=1);

namespace NDCore\Events;

interface Listener {

	public function handle( Event $event ): void;
}
