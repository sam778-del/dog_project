<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    HomeController,
    RaceController
};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// require __DIR__ . '/auth.php';

Route::get('/', [HomeController::class, 'index'])->name('home');

// Racing and Result
Route::resource('racing', RaceController::class);
Route::get('/dog', [RaceController::class, 'racing_dog']);
Route::get('get-racing-list', [RaceController::class, 'datatables'])
        ->name('race.datatables');
Route::get('form-racing-list', [RaceController::class, 'form_datatables'])
        ->name('form.datatables');

Route::get('get-dog-list', [RaceController::class, 'dog_datatables'])
        ->name('dog.datatables');
