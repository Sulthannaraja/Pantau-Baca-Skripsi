<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'pantaubaca.index');
Route::view('/index.html', 'pantaubaca.index');
Route::view('/login', 'pantaubaca.login');
Route::view('/login.html', 'pantaubaca.login');
Route::view('/register', 'pantaubaca.register');
Route::view('/register.html', 'pantaubaca.register');
Route::view('/katalog', 'pantaubaca.katalog');
Route::view('/katalog.html', 'pantaubaca.katalog');
Route::view('/reader', 'pantaubaca.reader');
Route::view('/reader.html', 'pantaubaca.reader');
Route::view('/dashboard-siswa', 'pantaubaca.dashboard-siswa');
Route::view('/dashboard-siswa.html', 'pantaubaca.dashboard-siswa');
Route::view('/dashboard-guru', 'pantaubaca.dashboard-guru');
Route::view('/dashboard-guru.html', 'pantaubaca.dashboard-guru');
Route::view('/dashboard-admin', 'pantaubaca.dashboard-admin');
Route::view('/dashboard-admin.html', 'pantaubaca.dashboard-admin');
