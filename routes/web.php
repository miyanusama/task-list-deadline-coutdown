<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TaskController;

Route::resource('tasks', TaskController::class)->except(['create']);
Route::get('/tasks/{task}', [TaskController::class, 'edit']);
Route::put('/tasks/{task}', [TaskController::class, 'update']);
