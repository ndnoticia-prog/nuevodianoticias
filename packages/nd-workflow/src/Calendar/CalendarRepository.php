<?php

declare(strict_types=1);

namespace NDWorkflow\Calendar;

use NDWorkflow\PostStatus\EditorialStatuses;
use WP_Post;
use WP_Query;

/**
 * Datos del calendario editorial: qué artículos (publicados, programados o
 * en cualquier estado del flujo editorial) caen en cada día de un mes. Es
 * deliberadamente solo la capa de datos: la interfaz visual (calendario
 * arrastrable, etc.) queda fuera del alcance de esta versión.
 */
final class CalendarRepository
{
    /**
     * @return array<string, list<array{id: int, title: string, status: string, edit_link: string}>>
     */
    public function postsForMonth(int $year, int $month): array
    {
        $query = new WP_Query([
            'post_type' => 'post',
            'post_status' => [
                'publish',
                'future',
                'draft',
                'pending',
                EditorialStatuses::IN_REVIEW,
                EditorialStatuses::NEEDS_CHANGES,
            ],
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'date_query' => [
                ['year' => $year, 'month' => $month],
            ],
        ]);

        $byDate = [];

        foreach ($query->posts as $post) {
            if (! $post instanceof WP_Post) {
                continue;
            }

            $date = get_the_date('Y-m-d', $post);

            $byDate[$date][] = [
                'id' => $post->ID,
                'title' => get_the_title($post),
                'status' => (string) get_post_status($post),
                'edit_link' => (string) get_edit_post_link($post, 'raw'),
            ];
        }

        return $byDate;
    }
}
