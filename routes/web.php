<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

Route::get('/users', fn() => view('users.index'))->name('users.index');

Route::get('/fingerprints', fn() => view('fingerprints.index'))->name('fingerprints.index');

Route::get('/fingerprint-samples', fn() => view('fingerprint_samples.index'))->name('fingerprint_samples.index');

Route::get('/fingerprint-templates', fn() => view('fingerprint_templates.index'))->name('fingerprint_templates.index');

Route::get('/fingerprint-logs', fn() => view('fingerprint_logs.index'))->name('fingerprint_logs.index');
