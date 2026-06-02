<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SearchService;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(
        SearchService $searchService
    ) {
        $this->searchService = $searchService;
    }

    public function search(Request $request)
    {
        $results = $this->searchService->search(
            $request->search
        );

        return response()->json([
            'search' => $request->search,
            'results' => $results
        ]);
    }
}