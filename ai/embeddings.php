<?php
// /matchymatchy/ai/embeddings.php
declare(strict_types=1);

/**
 * Minimal helper for generating embeddings and computing cosine similarity.
 * Model: text-embedding-3-small (good price/perf; multilingual).
 */

function mm_env_openai_api_key(): string {
    $key = getenv('OPENAI_API_KEY') ?: '';
    if (!$key) {
        throw new RuntimeException('OPENAI_API_KEY is not set in environment.');
    }
    return $key;
}

function mm_embed_text(string $text, string $model = 'text-embedding-3-small'): array {
    $key = mm_env_openai_api_key();

    $ch = curl_init('https://api.openai.com/v1/embeddings');
    $payload = json_encode([
        'model' => $model,
        'input' => $text,
    ], JSON_UNESCAPED_UNICODE);

    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$key}",
            "Content-Type: application/json",
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);

    $res = curl_exec($ch);
    if ($res === false) {
        throw new RuntimeException('cURL error: '.curl_error($ch));
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($res, true);
    if ($code >= 300 || !isset($data['data'][0]['embedding'])) {
        $msg = is_array($data) && isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
        throw new RuntimeException("OpenAI embeddings error: {$msg}");
    }
    return $data['data'][0]['embedding']; // float[]
}

/** Cosine similarity between two float arrays */
function mm_cosine(array $a, array $b): float {
    $n = min(count($a), count($b));
    if ($n === 0) return 0.0;
    $dot=0.0; $na=0.0; $nb=0.0;
    for ($i=0; $i<$n; $i++) {
        $ai = (float)$a[$i];
        $bi = (float)$b[$i];
        $dot += $ai * $bi;
        $na  += $ai * $ai;
        $nb  += $bi * $bi;
    }
    if ($na == 0.0 || $nb == 0.0) return 0.0;
    return $dot / (sqrt($na) * sqrt($nb));
}

/** Build indexable text for a product row (tweak as needed) */
function mm_build_product_index_text(array $p, string $categoryPath = ''): string {
    $parts = [
        $p['name'] ?? '',
        $p['sku'] ?? '',
        $categoryPath,          // e.g. "Baby > Baby Boy"
        $p['description'] ?? '',
    ];
    // normalize whitespace
    return trim(preg_replace('/\s+/u', ' ', implode(' ', array_filter($parts))));
}
