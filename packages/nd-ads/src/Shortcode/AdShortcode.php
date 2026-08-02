<?php

declare(strict_types=1);

namespace NDAds\Shortcode;

use NDAds\Rendering\AdZoneRenderer;

final class AdShortcode
{
    public function __construct(private readonly AdZoneRenderer $zones)
    {
    }

    /**
     * @param array<string, mixed>|string $atts
     */
    public function render(array|string $atts): string
    {
        $atts = shortcode_atts(['zone' => ''], is_array($atts) ? $atts : []);
        $zone = (string) $atts['zone'];

        if ($zone === '') {
            return '';
        }

        $categorySlugs = array_map(
            static fn (object $category): string => (string) $category->slug,
            get_the_category()
        );

        return $this->zones->render($zone, $categorySlugs);
    }
}
