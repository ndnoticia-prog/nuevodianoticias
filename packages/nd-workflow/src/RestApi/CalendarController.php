<?php

declare(strict_types=1);

namespace NDWorkflow\RestApi;

use NDCore\Permissions\Capability;
use NDCore\RestApi\Contracts\RegistersRoutes;
use NDCore\RestApi\RestController;
use NDCore\Routing\Router;
use NDWorkflow\Calendar\CalendarRepository;
use WP_REST_Request;
use WP_REST_Response;

final class CalendarController extends RestController implements RegistersRoutes
{
    public function __construct(private readonly CalendarRepository $calendar)
    {
    }

    public function registerRoutes(Router $router): void
    {
        $router->get(
            'nd/v1',
            '/workflow/calendar',
            [$this, 'index'],
            static fn (): bool => current_user_can(Capability::EDIT_ND_WORKFLOW),
            [
                'year' => ['type' => 'integer', 'required' => true],
                'month' => ['type' => 'integer', 'required' => true],
            ]
        );
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $year = (int) $request->get_param('year');
        $month = (int) $request->get_param('month');

        return $this->success(['data' => $this->calendar->postsForMonth($year, $month)]);
    }
}
