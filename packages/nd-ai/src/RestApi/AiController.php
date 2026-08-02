<?php

declare(strict_types=1);

namespace NDAi\RestApi;

use NDAi\Exceptions\AiProviderException;
use NDAi\Tasks\ContentAssistant;
use NDCore\Permissions\Capability;
use NDCore\RestApi\Contracts\RegistersRoutes;
use NDCore\RestApi\RestController;
use NDCore\Routing\Router;
use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

final class AiController extends RestController implements RegistersRoutes
{
    private const TASKS = [
        'headline',
        'seo_title',
        'meta_description',
        'tags',
        'categories',
        'summary',
        'excerpt',
        'social_facebook',
        'social_instagram',
        'social_x',
        'social_linkedin',
        'newsletter',
        'video_script',
    ];

    public function __construct(private readonly ContentAssistant $assistant)
    {
    }

    public function registerRoutes(Router $router): void
    {
        $router->post(
            'nd/v1',
            '/ai/posts/(?P<id>\d+)/generate',
            [$this, 'generate'],
            static fn (): bool => current_user_can(Capability::USE_ND_AI),
            [
                'id' => ['type' => 'integer', 'required' => true],
                'task' => ['type' => 'string', 'required' => true, 'enum' => self::TASKS],
            ]
        );
    }

    public function generate(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $post = get_post((int) $request->get_param('id'));

        if (! $post instanceof WP_Post) {
            return $this->error('nd_ai_post_not_found', __('Artículo no encontrado.', 'nd-ai'), 404);
        }

        $task = (string) $request->get_param('task');
        $articleText = wp_strip_all_tags(strip_shortcodes($post->post_content));
        $url = (string) get_permalink($post);

        try {
            $result = $this->runTask($task, $articleText, $url);
        } catch (AiProviderException $exception) {
            return $this->error('nd_ai_provider_error', $exception->getMessage(), 502);
        }

        if ($result === null) {
            return $this->error('nd_ai_unknown_task', __('Tarea de IA desconocida.', 'nd-ai'), 422);
        }

        return $this->success(['data' => ['task' => $task, 'result' => $result]]);
    }

    /**
     * @return string|list<string>|null
     */
    private function runTask(string $task, string $articleText, string $url): string|array|null
    {
        return match ($task) {
            'headline' => $this->assistant->generateHeadlines($articleText),
            'seo_title' => $this->assistant->generateSeoTitle($articleText),
            'meta_description' => $this->assistant->generateMetaDescription($articleText),
            'tags' => $this->assistant->generateTags($articleText),
            'categories' => $this->assistant->suggestCategories($articleText, $this->allCategoryNames()),
            'summary' => $this->assistant->generateSummary($articleText),
            'excerpt' => $this->assistant->generateExcerpt($articleText),
            'social_facebook' => $this->assistant->generateSocialPost('facebook', $articleText, $url),
            'social_instagram' => $this->assistant->generateSocialPost('instagram', $articleText, $url),
            'social_x' => $this->assistant->generateSocialPost('x', $articleText, $url),
            'social_linkedin' => $this->assistant->generateSocialPost('linkedin', $articleText, $url),
            'newsletter' => $this->assistant->generateNewsletterBlurb($articleText, $url),
            'video_script' => $this->assistant->generateVideoScript($articleText),
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    private function allCategoryNames(): array
    {
        $categories = get_categories(['hide_empty' => false]);

        return array_map(static fn (object $category): string => (string) $category->name, $categories);
    }
}
