<?php
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::post('/search', [SearchController::class, 'search']);