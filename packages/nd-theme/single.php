<?php
/**
 * Artículo individual.
 *
 * @package NDTheme
 */

use NDAds\Rendering\AdZoneRenderer;
use NDSeo\Breadcrumbs\BreadcrumbRenderer;

get_header();

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BreadcrumbRenderer ya escapa cada valor internamente.
echo nd_app(BreadcrumbRenderer::class)->render();

while (have_posts()) :
	the_post();
	?>
	<article <?php post_class('nd-article'); ?> id="post-<?php the_ID(); ?>">
		<header class="nd-article__header">
			<?php $categories = get_the_category(); ?>
			<?php if ($categories !== []) : ?>
				<div class="nd-article__categories">
					<?php foreach ($categories as $category) : ?>
						<a class="nd-article__category" href="<?php echo esc_url(get_category_link($category)); ?>">
							<?php echo esc_html($category->name); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<h1 class="nd-article__title"><?php the_title(); ?></h1>

			<p class="nd-article__meta">
				<?php
				printf(
					/* translators: 1: nombre del autor, 2: fecha de publicación */
					esc_html__('Por %1$s · %2$s', 'nd-theme'),
					esc_html(get_the_author()),
					esc_html(get_the_date())
				);
				?>
			</p>
		</header>

		<?php if (has_post_thumbnail()) : ?>
			<figure class="nd-article__thumbnail">
				<?php the_post_thumbnail('large'); ?>
			</figure>
		<?php endif; ?>

		<div class="nd-article__content">
			<?php the_content(); ?>
		</div>

		<?php
		$ndInArticleAd = nd_app(AdZoneRenderer::class)->render(
			'in-article',
			array_map(static fn (\WP_Term $category): string => $category->slug, $categories)
		);
		?>
		<?php if ($ndInArticleAd !== '') : ?>
			<div class="nd-ad-zone nd-ad-zone--in-article">
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- AdRenderer/AdZoneRenderer ya generan HTML seguro a partir de campañas administradas por usuarios con capacidad MANAGE_ND_ADS. ?>
				<?php echo $ndInArticleAd; ?>
			</div>
		<?php endif; ?>

		<?php
		wp_link_pages([
			'before' => '<nav class="nd-article__pagination">' . esc_html__('Páginas:', 'nd-theme'),
			'after' => '</nav>',
		]);
		?>
	</article>
	<?php
	if (comments_open() || get_comments_number() !== 0) :
		comments_template();
	endif;
endwhile;

get_footer();
