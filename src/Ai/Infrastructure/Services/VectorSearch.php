<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services;

class VectorSearch
{
    /**
     * Performs vector search on embeddings and returns sorted results
     */
    public function searchVectors(
        array $searchVector,
        array $embeddings,
        int $limit = 5
    ): array {
        $results = [];

        foreach ($embeddings as $embedding) {
            if (!$embedding) {
                continue;
            }

            foreach ($embedding as $em) {
                $similarity = $this->cosineSimilarity(
                    $em['embedding'],
                    $searchVector
                );

                $results[] = [
                    'content' => $em['content'],
                    'similarity' => $similarity
                ];
            }
        }

        usort($results, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return array_slice($results, 0, $limit);
    }

    private function cosineSimilarity($vec1, $vec2)
    {
        $dot_product = 0.0;
        $vec1_magnitude = 0.0;
        $vec2_magnitude = 0.0;

        for ($i = 0; $i < count($vec1); $i++) {
            $dot_product += $vec1[$i] * $vec2[$i];
            $vec1_magnitude += $vec1[$i] * $vec1[$i];
            $vec2_magnitude += $vec2[$i] * $vec2[$i];
        }

        $vec1_magnitude = sqrt($vec1_magnitude);
        $vec2_magnitude = sqrt($vec2_magnitude);

        if ($vec1_magnitude == 0.0 || $vec2_magnitude == 0.0) {
            return 0.0; // to handle division by zero
        }

        return $dot_product / ($vec1_magnitude * $vec2_magnitude);
    }
}
