<?php

namespace App\Services;

class AiSearchService
{
    public function rankResults(
        string $search,
        array $results
    ): array {

        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $results = array_slice($results, 0, 5);

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
            'image_path' => null
        ];
    }
}