<?php

use App\Events\DataUpdateEvent;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    try {
        DataUpdateEvent::dispatch(10);
        return view('welcome');
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Reverb is not running, please run `php artisan reverb:start`'
        ]);
    }
});
