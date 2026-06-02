<?php

namespace App\Services;

use App\Models\NaicsCode;
use App\Models\NaicsIndex;

class SearchService
{
    protected AiSearchService $aiSearchService;

    public function __construct(
        AiSearchService $aiSearchService
    ) {
        $this->aiSearchService = $aiSearchService;
    }

    public function search(string $keyword): array
    {
        $originalKeyword = trim($keyword);
        $keyword = strtolower($originalKeyword);

        $words = array_filter(explode(' ', $keyword));

        if ($originalKeyword === '' || empty($words)) {
            return [
                'NAICS' => 0,
                'names' => [],
                'search' => $originalKeyword,
                'image_path' => null,
            ];
        }

        $naicsScores = [];

        /*
        |--------------------------------------------------------------------------
        | Search Index Data
        |--------------------------------------------------------------------------
        */
        $indexMatches = NaicsIndex::with('naicsCode')
            ->where('index_description', 'LIKE', "%{$keyword}%")
            ->orWhere(function ($query) use ($words) {

                foreach ($words as $word) {
                    $query->orWhere(
                        'index_description',
                        'LIKE',
                        "%{$word}%"
                    );
                }
            })
            ->get();

        foreach ($indexMatches as $match) {

            $score = 50;

            $text = strtolower($match->index_description);

            $matchedWords = 0;

            foreach ($words as $word) {

                if (str_contains($text, $word)) {
                    $matchedWords++;
                    $score += 20;
                }
            }

            if (str_contains($text, $keyword)) {
                $score += 100;
            }

            if (
                count($words) > 1 &&
                $matchedWords === count($words)
            ) {
                $score += 60;
            }

            $naicsCode = $match->naicsCode->naics_code;

            if (!isset($naicsScores[$naicsCode])) {

                $naicsScores[$naicsCode] = [
                    'naics_code' => $naicsCode,
                    'description' => $match->naicsCode->description,
                    'score' => $score,
                    'matched_from' => [
                        $match->index_description
                    ]
                ];

            } else {

                $naicsScores[$naicsCode]['score'] = max(
                    $naicsScores[$naicsCode]['score'],
                    $score
                );

                $naicsScores[$naicsCode]['matched_from'][] =
                    $match->index_description;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Search Description Data
        |--------------------------------------------------------------------------
        */
        $descriptionMatches = NaicsCode::where(
                'description',
                'LIKE',
                "%{$keyword}%"
            )
            ->orWhere(function ($query) use ($words) {

                foreach ($words as $word) {
                    $query->orWhere(
                        'description',
                        'LIKE',
                        "%{$word}%"
                    );
                }
            })
            ->get();

        foreach ($descriptionMatches as $match) {

            $score = 30;

            $text = strtolower($match->description);

            $matchedWords = 0;

            foreach ($words as $word) {

                if (str_contains($text, $word)) {
                    $matchedWords++;
                    $score += 20;
                }
            }

            if (str_contains($text, $keyword)) {
                $score += 100;
            }

            if (
                count($words) > 1 &&
                $matchedWords === count($words)
            ) {
                $score += 60;
            }

            $naicsCode = $match->naics_code;

            if (!isset($naicsScores[$naicsCode])) {

                $naicsScores[$naicsCode] = [
                    'naics_code' => $naicsCode,
                    'description' => $match->description,
                    'score' => $score,
                    'matched_from' => [
                        $match->description
                    ]
                ];

            } else {

                $naicsScores[$naicsCode]['score'] = max(
                    $naicsScores[$naicsCode]['score'],
                    $score
                );

                $naicsScores[$naicsCode]['matched_from'][] =
                    $match->description;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Sort Results
        |--------------------------------------------------------------------------
        */
        $results = array_values($naicsScores);

        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        /*
        |--------------------------------------------------------------------------
        | Give AI More Candidates
        |--------------------------------------------------------------------------
        */
        $results = array_slice($results, 0, 20);

        /*
        |--------------------------------------------------------------------------
        | AI Ranking Layer
        |--------------------------------------------------------------------------
        */
        return $this->aiSearchService->rankResults(
            $originalKeyword,
            $results
        );
    }
}
