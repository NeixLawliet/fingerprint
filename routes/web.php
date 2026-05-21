<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));
Route::get('/dashboard',    fn() => view('dashboard'))->name('dashboard');
Route::get('/employees',    fn() => view('employees.index'))->name('employees.index');
Route::get('/attendance',   fn() => view('attendance.index'))->name('attendance.index');
