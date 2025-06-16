<?php

use Illuminate\Support\Facades\Route;
use Src\Domain\Content\Enums\ContentType;

Route::get('/', function () {
    return view('welcome');
});


