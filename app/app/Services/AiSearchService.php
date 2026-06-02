<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSearchService
{
    public function rankResults(
        string $search,
        array $results
    ): array {
        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $candidates = array_slice($results, 0, 20);
        $rankedByAi = $this->rankWithGemini($search, $candidates);

        if (!empty($rankedByAi)) {
            return [
                'NAICS' => count($rankedByAi),
                'names' => $rankedByAi,
                'search' => $search,
                'image_path' => null,
            ];
        }

        $results = array_slice($candidates, 0, 5);

        $names = [];

        foreach ($results as $result) {
            $reason =
                "Matched using NAICS Index and Description data. "
                . "Score: {$result['score']}.";

            if (!empty($result['matched_from'])) {
                $reason .=
                    " Example match: "
                    . $result['matched_from'][0];
            }

            $names[] =
                $result['naics_code']
                . " : "
                . $result['description']
                . " - "
                . $reason;
        }

        return [
            'NAICS' => count($names),
            'names' => $names,
            'search' => $search,
            'image_path' => null,
        ];
    }

    private function rankWithGemini(string $search, array $candidates): array
    {
        $apiKey = (string) config('services.gemini.key', '');

        if ($apiKey === '' || empty($candidates)) {
            return [];
        }

        $model = (string) config('services.gemini.model', 'gemini-1.5-flash');
        $timeout = (int) config('services.gemini.timeout', 8);
        $candidateLimit = (int) config('services.gemini.candidate_limit', 15);

        $candidates = array_slice($candidates, 0, max(5, $candidateLimit));

        $payloadCandidates = array_map(function (array $item): array {
            return [
                'naics_code' => $item['naics_code'] ?? '',
                'description' => $item['description'] ?? '',
                'score' => $item['score'] ?? 0,
                'matched_from' => array_slice($item['matched_from'] ?? [], 0, 2),
            ];
        }, $candidates);

        $prompt = $this->buildPrompt($search, $payloadCandidates);

        try {
            $response = Http::acceptJson()
                ->withHeaders(['X-goog-api-key' => $apiKey])
                ->timeout($timeout)
                ->connectTimeout(4)
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                    [
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.2,
                            'maxOutputTokens' => 700,
                        ],
                    ]
                );

            if (!$response->successful()) {
                Log::warning('Gemini ranking request failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $text = data_get(
                $response->json(),
                'candidates.0.content.parts.0.text',
                ''
            );

            if ($text === '') {
                return [];
            }

            $decoded = json_decode($text, true);

            if (!is_array($decoded) || !isset($decoded['names']) || !is_array($decoded['names'])) {
                return [];
            }

            return array_slice($decoded['names'], 0, 5);
        } catch (\Throwable $exception) {
            Log::warning('Gemini ranking exception. Falling back to local ranking.', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function buildPrompt(string $search, array $candidates): string
    {
        $candidateJson = json_encode($candidates, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are an NAICS classifier.
Task: rank the best 5 NAICS matches for the keyword "{$search}".

Rules:
1) Prefer exact relevance to the user's product/service/activity.
2) Use index-description and NAICS description evidence from candidates.
3) Return only valid NAICS codes from provided candidates.
4) Output strict JSON only. No markdown.
5) JSON schema:
{
  "names": [
    "NAICS_CODE : DESCRIPTION - REASON",
    "... total up to 5"
  ]
}

Candidate list:
{$candidateJson}
PROMPT;
    }
}