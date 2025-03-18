<?php

use App\Events\ResourceMonitorEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        ResourceMonitorEvent::dispatch(10);
        return view('welcome');
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Reverb is not running, please run `php artisan reverb:start`'
        ]);
    }
});
