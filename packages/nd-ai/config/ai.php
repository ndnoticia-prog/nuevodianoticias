<?php

declare(strict_types=1);

return [
    /*
     * Proveedor usado por defecto: "openai", "claude", "gemini", "deepseek"
     * o "local". Las claves de API se gestionan por separado (cifradas) a
     * través de NDAi\Settings\ApiKeyStore, nunca en este archivo.
     */
    'default_provider' => defined('ND_AI_DEFAULT_PROVIDER') ? ND_AI_DEFAULT_PROVIDER : 'openai',

    'local_base_url' => defined('ND_AI_LOCAL_BASE_URL') ? ND_AI_LOCAL_BASE_URL : '',

    'models' => [
        'openai' => defined('ND_AI_OPENAI_MODEL') ? ND_AI_OPENAI_MODEL : 'gpt-4o-mini',
        'claude' => defined('ND_AI_CLAUDE_MODEL') ? ND_AI_CLAUDE_MODEL : 'claude-sonnet-5',
        'gemini' => defined('ND_AI_GEMINI_MODEL') ? ND_AI_GEMINI_MODEL : 'gemini-2.0-flash',
        'deepseek' => defined('ND_AI_DEEPSEEK_MODEL') ? ND_AI_DEEPSEEK_MODEL : 'deepseek-chat',
        'local' => defined('ND_AI_LOCAL_MODEL') ? ND_AI_LOCAL_MODEL : 'llama3',
    ],
];
