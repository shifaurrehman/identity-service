<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get("user",[UserController::class,"index"]);
Route::get("getUsers",[UserController::class,"getUsers"]);
Route::get("getUserDetails/{name}",[UserController::class,"getUserDetails"]);