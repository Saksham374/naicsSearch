<?php

namespace Tests\Feature;

use Tests\TestCase;

class SearchApiTest extends TestCase
{
    /**
     * Valid search request.
     */
    public function test_search_returns_results(): void
    {
        $response = $this->postJson('/api/search', [
            'search' => 'dog food'
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'search',
            'results'
        ]);
    }

    /**
     * Missing search field.
     */
    public function test_search_field_is_required(): void
    {
        $response = $this->postJson('/api/search', []);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'search'
        ]);
    }

    /**
     * Search must be at least 2 characters.
     */
    public function test_search_must_be_at_least_two_characters(): void
    {
        $response = $this->postJson('/api/search', [
            'search' => 'd'
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'search'
        ]);
    }

    /**
     * Unknown keyword should return empty results.
     */
    public function test_unknown_keyword_returns_empty_results(): void
    {
        $response = $this->postJson('/api/search', [
            'search' => 'xyzabc123'
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'search' => 'xyzabc123',
            'results' => []
        ]);
    }

    /**
     * Results contain expected fields.
     */
    public function test_results_have_expected_structure(): void
    {
        $response = $this->postJson('/api/search', [
            'search' => 'dog food'
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'search',
            'results' => [
                '*' => [
                    'naics_code',
                    'description',
                    'score',
                    'matched_from'
                ]
            ]
        ]);
    }

    /**
     * Search is case insensitive.
     */
    public function test_search_is_case_insensitive(): void
    {
        $response1 = $this->postJson('/api/search', [
            'search' => 'dog food'
        ]);

        $response2 = $this->postJson('/api/search', [
            'search' => 'DOG FOOD'
        ]);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $this->assertEquals(
            $response1->json('results')[0]['naics_code'] ?? null,
            $response2->json('results')[0]['naics_code'] ?? null
        );
    }

    /**
     * Exact phrase should rank highest.
     */
    public function test_exact_match_is_ranked_first(): void
    {
        $response = $this->postJson('/api/search', [
            'search' => 'centrifugal pump'
        ]);

        $response->assertStatus(200);

        $results = $response->json('results');

        $this->assertNotEmpty($results);

        $this->assertEquals(
            '333914',
            $results[0]['naics_code']
        );
    }
}