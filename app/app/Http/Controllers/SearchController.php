<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Services\SearchService;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(
        SearchService $searchService
    ) {
        $this->searchService = $searchService;
    }

    public function search(SearchRequest $request)
    {
        return response()->json(
            $this->searchService->search(
            $request->search
            )
        );
    }
}