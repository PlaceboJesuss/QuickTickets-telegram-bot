<?php

use App\Http\Controllers\RedirectToBotController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RedirectToBotController::class, 'redirect']);
