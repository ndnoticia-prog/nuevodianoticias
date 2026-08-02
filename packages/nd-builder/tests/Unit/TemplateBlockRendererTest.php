<?php

declare(strict_types=1);

namespace NDBuilder\Tests\Unit;

use Brain\Monkey\Functions;
use NDBuilder\Block;
use NDBuilder\Tests\BrainMonkeyTestCase;
use NDBuilder\TemplateBlockRenderer;
use RuntimeException;

final class TemplateBlockRendererTest extends BrainMonkeyTestCase {

	private string $templateFile;

	protected function setUp(): void {
		parent::setUp();

		$this->templateFile = sys_get_temp_dir() . '/nd-builder-hero-' . bin2hex( random_bytes( 4 ) ) . '.php';
		file_put_contents(
			$this->templateFile,
			"<?php echo '<h1>' . htmlspecialchars((string) \$block->attribute('title', '')) . '</h1>';"
		);
	}

	protected function tearDown(): void {
		if ( file_exists( $this->templateFile ) ) {
			unlink( $this->templateFile );
		}

		parent::tearDown();
	}

	public function test_render_includes_the_located_template_with_block_in_scope(): void {
		$block = new Block( 'hero', 'hero-1', array( 'title' => 'Última hora' ) );

		Functions\expect( 'locate_template' )
			->once()
			->with( array( 'template-parts/blocks/hero.php' ), false )
			->andReturn( $this->templateFile );

		$renderer = new TemplateBlockRenderer( array( 'template-parts/blocks/hero' ) );

		self::assertSame( '<h1>Última hora</h1>', $renderer->render( $block ) );
	}

	public function test_render_throws_when_no_template_is_located(): void {
		Functions\expect( 'locate_template' )->once()->andReturn( '' );

		$renderer = new TemplateBlockRenderer( array( 'template-parts/blocks/missing' ) );

		$this->expectException( RuntimeException::class );

		$renderer->render( new Block( 'missing', 'x-1' ) );
	}
}
