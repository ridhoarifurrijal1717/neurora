<?php
declare(strict_types=1);
function ai_config(): array {
    $local = is_file(__DIR__ . '/ai.local.php') ? require __DIR__ . '/ai.local.php' : [];
    if (!is_array($local)) $local = [];
    $provider = (string)(getenv('NEURORA_AI_PROVIDER') ?: ($local['provider'] ?? 'xai'));
    $prefix = ['xai'=>'XAI','gemini'=>'GEMINI','ollama'=>'OLLAMA'][$provider] ?? 'NEURORA';
    $defaults = ['xai'=>'grok-4.7','gemini'=>'gemini-3.5-flash-lite','ollama'=>'qwen3:4b'];
    return [
        'provider'=>$provider,
        'api_key'=>(string)(getenv($prefix . '_API_KEY') ?: ($local['api_key'] ?? '')),
        'base_url'=>rtrim((string)(getenv('OLLAMA_BASE_URL') ?: ($local['base_url'] ?? 'http://127.0.0.1:11434')), '/'),
        'model'=>(string)(getenv($prefix . '_MODEL') ?: ($local['model'] ?? ($defaults[$provider] ?? ''))),
        'timeout'=>max(30,min(600,(int)($local['timeout'] ?? 120))),
        'context_length'=>max(4096,min(32768,(int)($local['context_length'] ?? 16384))),
    ];
}
