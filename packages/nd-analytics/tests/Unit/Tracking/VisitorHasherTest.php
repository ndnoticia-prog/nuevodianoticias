<?php

declare(strict_types=1);

namespace NDAnalytics\Tests\Unit\Tracking;

use Brain\Monkey\Functions;
use NDAnalytics\Tests\BrainMonkeyTestCase;
use NDAnalytics\Tracking\VisitorHasher;

final class VisitorHasherTest extends BrainMonkeyTestCase
{
    public function test_hash_delegates_to_wp_hash(): void
    {
        Functions\expect('wp_hash')->once()->andReturn('a-stable-hash');

        self::assertSame('a-stable-hash', (new VisitorHasher())->hash('203.0.113.5', 'Mozilla/5.0'));
    }

    public function test_hash_input_combines_ip_user_agent_and_todays_date_but_never_the_raw_ip_alone(): void
    {
        $capturedInput = null;

        Functions\expect('wp_hash')->once()->andReturnUsing(static function (string $input) use (&$capturedInput): string {
            $capturedInput = $input;

            return 'hashed';
        });

        (new VisitorHasher())->hash('203.0.113.5', 'Mozilla/5.0');

        self::assertMatchesRegularExpression(
            '/^203\.0\.113\.5\|Mozilla\/5\.0\|\d{4}-\d{2}-\d{2}$/',
            (string) $capturedInput
        );
    }
}
