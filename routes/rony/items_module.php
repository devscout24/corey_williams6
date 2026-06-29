<?php

use App\Http\Controllers\AttributeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryColumnSettingController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemKitController;
use App\Http\Controllers\ItemLabelController;
use App\Http\Controllers\PriceRuleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:employee')->group(function (): void {

    // labels
    Route::get('/items/labels', [ItemLabelController::class, 'index'])->name('labels.index');
    Route::get('/items/labels/search', [ItemLabelController::class, 'search'])->name('labels.search');
    Route::post('/items/labels/print', [ItemLabelController::class, 'print'])->name('labels.print');

    // inventory settings
    Route::post('/items/columns/save', [InventoryColumnSettingController::class, 'saveColumnPrefs'])->name('items.save_column_prefs');
    Route::get('/items/columns/reset', [InventoryColumnSettingController::class, 'resetColumnPrefs'])->name('items.reset_column_prefs');

    // core items
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::get('/items/export', [ItemController::class, 'export'])->name('items.export');
    Route::get('/items/import', [ItemController::class, 'importForm'])->name('items.import');
    Route::post('/items/import', [ItemController::class, 'import'])->name('items.import.store');
    Route::get('/items/import/review/{batch}', [ItemController::class, 'importReview'])->name('items.import.review');
    Route::post('/items/import/accept', [ItemController::class, 'importAccept'])->name('items.import.accept');
    Route::post('/items/import/accept-all', [ItemController::class, 'importAcceptAll'])->name('items.import.accept-all');
    Route::post('/items/import/skip', [ItemController::class, 'importSkip'])->name('items.import.skip');
    Route::get('/items/{itemId}/edit', [ItemController::class, 'edit'])->name('items.edit');
    Route::put('/items/{itemId}', [ItemController::class, 'update'])->name('items.update');
    Route::patch('/items/{itemId}/quick-update', [ItemController::class, 'quickUpdate'])->name('items.quick-update');
    Route::delete('/items/{itemId}', [ItemController::class, 'destroy'])->name('items.destroy');

    // item kits
    Route::get('/item-kits', [ItemKitController::class, 'index'])->name('item-kits.index');
    Route::get('/item-kits/create', [ItemKitController::class, 'create'])->name('item-kits.create');
    Route::post('/item-kits', [ItemKitController::class, 'store'])->name('item-kits.store');
    Route::get('/item-kits/{kitId}/edit', [ItemKitController::class, 'edit'])->name('item-kits.edit');
    Route::put('/item-kits/{kitId}', [ItemKitController::class, 'update'])->name('item-kits.update');
    Route::patch('/item-kits/{kitId}/quick-update', [ItemKitController::class, 'quickUpdate'])->name('item-kits.quick-update');
    Route::delete('/item-kits/{kitId}', [ItemKitController::class, 'destroy'])->name('item-kits.destroy');

    // categories, attributes, price rules
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{categoryId}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{categoryId}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('/categories/export', [CategoryController::class, 'export'])->name('categories.export');
    Route::get('/categories/import', [CategoryController::class, 'importForm'])->name('categories.import');
    Route::post('/categories/import', [CategoryController::class, 'import'])->name('categories.import.store');

    // resources routes
    Route::resource('attributes', AttributeController::class);
    Route::resource('price-rules', PriceRuleController::class);

    Route::get('/item-kits/search', [PriceRuleController::class, 'searchItemKits'])->name('item-kits.search');
    Route::get('/categories/search', [PriceRuleController::class, 'searchCategories'])->name('categories.search');
    Route::get('/tags/search', [PriceRuleController::class, 'searchTags'])->name('tags.search');
    Route::get('/items/search', [PriceRuleController::class, 'search'])->name('items.search');
});
