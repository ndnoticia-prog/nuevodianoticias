<?php

declare(strict_types=1);

namespace NDWorkflow\RestApi;

use NDCore\Permissions\Capability;
use NDCore\RestApi\Contracts\RegistersRoutes;
use NDCore\RestApi\RestController;
use NDCore\Routing\Router;
use NDWorkflow\Notes\EditorialNote;
use NDWorkflow\Notes\EditorialNoteRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class NotesController extends RestController implements RegistersRoutes
{
    public function __construct(private readonly EditorialNoteRepository $notes)
    {
    }

    public function registerRoutes(Router $router): void
    {
        $permission = static fn (): bool => current_user_can(Capability::EDIT_ND_WORKFLOW);
        $idArg = ['id' => ['type' => 'integer', 'required' => true]];

        $router->get('nd/v1', '/workflow/posts/(?P<id>\d+)/notes', [$this, 'index'], $permission, $idArg);

        $router->post('nd/v1', '/workflow/posts/(?P<id>\d+)/notes', [$this, 'store'], $permission, $idArg + [
            'body' => ['type' => 'string', 'required' => true],
            'type' => ['type' => 'string', 'required' => false],
        ]);

        $router->delete('nd/v1', '/workflow/notes/(?P<id>\d+)', [$this, 'destroy'], $permission, $idArg);
    }

    public function index(WP_REST_Request $request): WP_REST_Response
    {
        $notes = $this->notes->forPost((int) $request->get_param('id'));

        return $this->success(['data' => array_map($this->serialize(...), $notes)]);
    }

    public function store(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $body = (string) $request->get_param('body');

        if (trim($body) === '') {
            return $this->error('nd_workflow_empty_note', __('El comentario no puede estar vacío.', 'nd-workflow'), 422);
        }

        $type = (string) ($request->get_param('type') ?: EditorialNote::TYPE_NOTE);

        $note = $this->notes->create(
            (int) $request->get_param('id'),
            get_current_user_id(),
            sanitize_textarea_field($body),
            $type
        );

        return $this->success(['data' => $this->serialize($note)], 201);
    }

    public function destroy(WP_REST_Request $request): WP_REST_Response
    {
        $this->notes->delete((int) $request->get_param('id'));

        return $this->success([], 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(EditorialNote $note): array
    {
        return [
            'id' => $note->id,
            'post_id' => $note->postId,
            'author_id' => $note->authorId,
            'type' => $note->type,
            'body' => $note->body,
            'created_at' => $note->createdAt,
        ];
    }
}
