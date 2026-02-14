<?php

use App\Http\Controllers\PasteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('documents', [PasteController::class, 'storeDocumentApi'])->name('api.documents.store');
    Route::get('documents/{key}', [PasteController::class, 'showDocumentApi'])
        ->where('key', '[A-Za-z0-9]{32}')
        ->name('api.documents.show');
});
