<?php

declare(strict_types=1);

namespace NDAnalytics\RestApi;

use NDAnalytics\Repository\AnalyticsRepository;
use NDCore\Permissions\Capability;
use NDCore\RestApi\Contracts\RegistersRoutes;
use NDCore\RestApi\RestController;
use NDCore\Routing\Router;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Panel editorial de analítica (nd/v1/analytics/*). No hay interfaz visual
 * en esta versión: expone los datos para que un futuro panel de admin (o
 * cualquier cliente autenticado con la capacidad adecuada) los consuma.
 */
final class AnalyticsController extends RestController implements RegistersRoutes
{
    public function __construct(private readonly AnalyticsRepository $repository)
    {
    }

    public function registerRoutes(Router $router): void
    {
        $permission = static fn (): bool => current_user_can(Capability::VIEW_ND_ANALYTICS);

        $router->get('nd/v1', '/analytics/top-posts', [$this, 'topPosts'], $permission, [
            'days' => ['type' => 'integer', 'required' => false],
            'limit' => ['type' => 'integer', 'required' => false],
        ]);

        $router->get('nd/v1', '/analytics/active-now', [$this, 'activeNow'], $permission, [
            'minutes' => ['type' => 'integer', 'required' => false],
        ]);

        $router->get('nd/v1', '/analytics/top-authors', [$this, 'topAuthors'], $permission, [
            'days' => ['type' => 'integer', 'required' => false],
        ]);

        $router->get('nd/v1', '/analytics/top-categories', [$this, 'topCategories'], $permission, [
            'days' => ['type' => 'integer', 'required' => false],
        ]);

        $router->get('nd/v1', '/analytics/posts/(?P<id>\d+)/ctr', [$this, 'ctr'], $permission, [
            'id' => ['type' => 'integer', 'required' => true],
            'days' => ['type' => 'integer', 'required' => false],
        ]);
    }

    public function topPosts(WP_REST_Request $request): WP_REST_Response
    {
        $days = (int) ($request->get_param('days') ?: 7);
        $limit = (int) ($request->get_param('limit') ?: 10);

        return $this->success(['data' => $this->repository->topPosts($days, $limit)]);
    }

    public function activeNow(WP_REST_Request $request): WP_REST_Response
    {
        $minutes = (int) ($request->get_param('minutes') ?: 5);

        return $this->success(['data' => $this->repository->activeNow($minutes)]);
    }

    public function topAuthors(WP_REST_Request $request): WP_REST_Response
    {
        $days = (int) ($request->get_param('days') ?: 30);

        return $this->success(['data' => $this->repository->topAuthors($days)]);
    }

    public function topCategories(WP_REST_Request $request): WP_REST_Response
    {
        $days = (int) ($request->get_param('days') ?: 30);

        return $this->success(['data' => $this->repository->topCategories($days)]);
    }

    public function ctr(WP_REST_Request $request): WP_REST_Response
    {
        $postId = (int) $request->get_param('id');
        $days = (int) ($request->get_param('days') ?: 30);

        return $this->success(['data' => $this->repository->ctrForPost($postId, $days)]);
    }
}
