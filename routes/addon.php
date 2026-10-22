<?php

use Illuminate\Support\Facades\Route;

/*
 | The Exam add-on is a paid GegoK12 marketplace module and is NOT installed in
 | the core application, so its controller class does not exist here. Registering
 | these routes unconditionally makes `php artisan route:list` and `route:cache`
 | crash with a ReflectionException (missing controller).
 |
 | Guarding with class_exists() keeps the routes dormant until the add-on (and its
 | controller) is actually installed, at which point they activate automatically —
 | without installing, activating, or bypassing the paid module.
 */
if (class_exists(\App\Http\Controllers\Admin\Addon\AddonInstallExamController::class)) {
    Route::get('/addon/install/exam', 'Addon\AddonInstallExamController@create');
    Route::post('/addon/install/exam', 'Addon\AddonInstallExamController@store');
}
