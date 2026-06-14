<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\PosCheckout;

// Simply point to the component. Livewire handles the rest!
Route::get('/pos', PosCheckout::class);