<?php
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\CacheController;
use App\Http\Controllers\API\CacheLockController;
use App\Http\Controllers\API\JobController;
use App\Http\Controllers\API\JobBatchController;
use App\Http\Controllers\API\FailedJobController;
use App\Http\Controllers\API\FingerprintController;
use App\Http\Controllers\API\FingerprintSampleController;
use App\Http\Controllers\API\FingerprintTemplateController;
use App\Http\Controllers\API\FingerprintLogController;
use App\Http\Controllers\API\FingerprintProcessController;
use Illuminate\Support\Facades\Route;

// ---- Route Use Generator ----

// Fingerprint processing pipeline
Route::post('fingerprints/{id}/process', [FingerprintProcessController::class, 'process'])->name('fingerprints.process');
Route::post('fingerprints/{id}/verify',  [FingerprintProcessController::class, 'verify'])->name('fingerprints.verify');


Route::controller(UserController::class)->group(function() {
    Route::get('users/{id?}', 'get')->name('get.users');
    Route::post('users', 'post')->name('post.users');
    Route::patch('users/{id}', 'patch')->name('patch.users');
    Route::put('users/{id}', 'put')->name('put.users');
    Route::delete('users/{id}', 'delete')->name('delete.users');
    Route::post('users_datatables', 'datatables')->name('datatable.users');
    Route::patch('users/{id}/approve', 'approve')->name('approve.users');
});
Route::controller(CacheController::class)->group(function() {
    Route::get('cache/{id?}', 'get')->name('get.cache');
    Route::post('cache', 'post')->name('post.cache');
    Route::patch('cache/{id}', 'patch')->name('patch.cache');
    Route::put('cache/{id}', 'put')->name('put.cache');
    Route::delete('cache/{id}', 'delete')->name('delete.cache');
    Route::post('cache_datatables', 'datatables')->name('datatable.cache');
    Route::patch('cache/{id}/approve', 'approve')->name('approve.cache');
});
Route::controller(CacheLockController::class)->group(function() {
    Route::get('cache_locks/{id?}', 'get')->name('get.cache_locks');
    Route::post('cache_locks', 'post')->name('post.cache_locks');
    Route::patch('cache_locks/{id}', 'patch')->name('patch.cache_locks');
    Route::put('cache_locks/{id}', 'put')->name('put.cache_locks');
    Route::delete('cache_locks/{id}', 'delete')->name('delete.cache_locks');
    Route::post('cache_locks_datatables', 'datatables')->name('datatable.cache_locks');
    Route::patch('cache_locks/{id}/approve', 'approve')->name('approve.cache_locks');
});
Route::controller(JobController::class)->group(function() {
    Route::get('jobs/{id?}', 'get')->name('get.jobs');
    Route::post('jobs', 'post')->name('post.jobs');
    Route::patch('jobs/{id}', 'patch')->name('patch.jobs');
    Route::put('jobs/{id}', 'put')->name('put.jobs');
    Route::delete('jobs/{id}', 'delete')->name('delete.jobs');
    Route::post('jobs_datatables', 'datatables')->name('datatable.jobs');
    Route::patch('jobs/{id}/approve', 'approve')->name('approve.jobs');
});
Route::controller(JobBatchController::class)->group(function() {
    Route::get('job_batches/{id?}', 'get')->name('get.job_batches');
    Route::post('job_batches', 'post')->name('post.job_batches');
    Route::patch('job_batches/{id}', 'patch')->name('patch.job_batches');
    Route::put('job_batches/{id}', 'put')->name('put.job_batches');
    Route::delete('job_batches/{id}', 'delete')->name('delete.job_batches');
    Route::post('job_batches_datatables', 'datatables')->name('datatable.job_batches');
    Route::patch('job_batches/{id}/approve', 'approve')->name('approve.job_batches');
});
Route::controller(FailedJobController::class)->group(function() {
    Route::get('failed_jobs/{id?}', 'get')->name('get.failed_jobs');
    Route::post('failed_jobs', 'post')->name('post.failed_jobs');
    Route::patch('failed_jobs/{id}', 'patch')->name('patch.failed_jobs');
    Route::put('failed_jobs/{id}', 'put')->name('put.failed_jobs');
    Route::delete('failed_jobs/{id}', 'delete')->name('delete.failed_jobs');
    Route::post('failed_jobs_datatables', 'datatables')->name('datatable.failed_jobs');
    Route::patch('failed_jobs/{id}/approve', 'approve')->name('approve.failed_jobs');
});
Route::controller(FingerprintController::class)->group(function() {
    Route::get('fingerprints/{id?}', 'get')->name('get.fingerprints');
    Route::post('fingerprints', 'post')->name('post.fingerprints');
    Route::patch('fingerprints/{id}', 'patch')->name('patch.fingerprints');
    Route::put('fingerprints/{id}', 'put')->name('put.fingerprints');
    Route::delete('fingerprints/{id}', 'delete')->name('delete.fingerprints');
    Route::post('fingerprints_datatables', 'datatables')->name('datatable.fingerprints');
    Route::patch('fingerprints/{id}/approve', 'approve')->name('approve.fingerprints');
});
Route::controller(FingerprintSampleController::class)->group(function() {
    Route::get('fingerprint_samples/{id?}', 'get')->name('get.fingerprint_samples');
    Route::post('fingerprint_samples', 'post')->name('post.fingerprint_samples');
    Route::patch('fingerprint_samples/{id}', 'patch')->name('patch.fingerprint_samples');
    Route::put('fingerprint_samples/{id}', 'put')->name('put.fingerprint_samples');
    Route::delete('fingerprint_samples/{id}', 'delete')->name('delete.fingerprint_samples');
    Route::post('fingerprint_samples_datatables', 'datatables')->name('datatable.fingerprint_samples');
    Route::patch('fingerprint_samples/{id}/approve', 'approve')->name('approve.fingerprint_samples');
});
Route::controller(FingerprintTemplateController::class)->group(function() {
    Route::get('fingerprint_templates/{id?}', 'get')->name('get.fingerprint_templates');
    Route::post('fingerprint_templates', 'post')->name('post.fingerprint_templates');
    Route::patch('fingerprint_templates/{id}', 'patch')->name('patch.fingerprint_templates');
    Route::put('fingerprint_templates/{id}', 'put')->name('put.fingerprint_templates');
    Route::delete('fingerprint_templates/{id}', 'delete')->name('delete.fingerprint_templates');
    Route::post('fingerprint_templates_datatables', 'datatables')->name('datatable.fingerprint_templates');
    Route::patch('fingerprint_templates/{id}/approve', 'approve')->name('approve.fingerprint_templates');
});
Route::controller(FingerprintLogController::class)->group(function() {
    Route::get('fingerprint_logs/{id?}', 'get')->name('get.fingerprint_logs');
    Route::post('fingerprint_logs', 'post')->name('post.fingerprint_logs');
    Route::patch('fingerprint_logs/{id}', 'patch')->name('patch.fingerprint_logs');
    Route::put('fingerprint_logs/{id}', 'put')->name('put.fingerprint_logs');
    Route::delete('fingerprint_logs/{id}', 'delete')->name('delete.fingerprint_logs');
    Route::post('fingerprint_logs_datatables', 'datatables')->name('datatable.fingerprint_logs');
    Route::patch('fingerprint_logs/{id}/approve', 'approve')->name('approve.fingerprint_logs');
});
// ---- Route Controller Generator ---- 