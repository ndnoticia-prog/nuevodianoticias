<?php

declare(strict_types=1);

return [
    /*
     * "webp", "avif" o null para desactivar la conversión automática de
     * JPEG/PNG subidos. "avif" degrada a WebP si el servidor no soporta
     * codificación AVIF (requiere GD con PHP >= 8.1 o Imagick con libheif).
     */
    'modern_format' => defined('ND_MEDIA_MODERN_FORMAT') ? ND_MEDIA_MODERN_FORMAT : 'webp',

    'cdn_url' => defined('ND_MEDIA_CDN_URL') ? ND_MEDIA_CDN_URL : null,

    'podcast' => [
        'audio_meta_key' => '_nd_podcast_audio_url',
        'audio_length_meta_key' => '_nd_podcast_audio_length',
    ],
];
