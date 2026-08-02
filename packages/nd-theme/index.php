<?php
/**
 * Plantilla de respaldo obligatoria para WordPress. Con front-page.php,
 * home.php, single.php y archive.php cubriendo el resto de contextos, esta
 * solo se usa como último recurso de la jerarquía de plantillas.
 *
 * @package NDTheme
 */

get_header();
get_template_part('template-parts/content/post-grid');
get_footer();
