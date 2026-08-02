<?php

declare(strict_types=1);

namespace NDDiscover;

/**
 * Google exige imágenes de al menos 1200px de ancho para que un artículo
 * sea elegible para el carrusel visual grande de Discover. El tamaño
 * "large" nativo de WordPress solo llega a 1024px, así que no sirve: se
 * registra un tamaño propio y nd-seo/nd-theme lo usan para la imagen
 * destacada en lugar de "large".
 */
final class ImageSizes {

	public const string FEATURED     = 'nd-discover-featured';
	public const int FEATURED_WIDTH  = 1200;
	public const int FEATURED_HEIGHT = 675;

	private function __construct() {
	}
}
