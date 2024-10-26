<?php

//use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\CartProductsController;

use App\Http\Controllers\RecommendationController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/', function () {
    return response()->json(['message' => 'Hello World!']);
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::post('/cart/add', [CartProductsController::class, 'addToCart'])->name('cart.addToCart');

Route::get('/cart/{userId}', [CartProductsController::class, 'showCart'])->name('cart.showCart');
Route::post('/cart/remove', [CartProductsController::class, 'removeProductUnits'])->name('cart.remove');
Route::post('/cart/remove-product', [CartProductsController::class, 'removeProductFromCart'])->name('cart.remove.product');
Route::post('/cart/removeall', [CartProductsController::class, 'removeAllProductsFromCart'])->name('cart.removeAll.product');






Route::middleware(['auth:sanctum'])->get('/products/category/{categoryId}', [RecomendationController::class, 'getCombinedProductsInCategory']);


Route::get('/recommendations', [RecommendationController::class, 'getRecommendations']);

