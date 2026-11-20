<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryDashboardController;
use App\Http\Controllers\InventoryDispatchController;
use App\Http\Controllers\InventorySessionController;
use App\Http\Controllers\InventoryTicketController;
use App\Http\Middleware\SetInventoryLocale;
use Illuminate\Support\Facades\Route;

// dashboard pages
Route::get('/', function () {
    return redirect()->route('inventory.home');
})->name('dashboard');

Route::post('/inventory/language', [InventorySessionController::class, 'language'])
    ->middleware(SetInventoryLocale::class)->name('inventory.language');

Route::middleware(SetInventoryLocale::class, 'auth')->prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/', InventoryDashboardController::class)->name('home');

    Route::middleware('can:record-qc')->group(function () {
        Route::get('/qc', [InventoryController::class, 'qc'])->name('qc');
        Route::post('/qc/tickets', [InventoryTicketController::class, 'store'])->name('ticket.store');
        Route::get('/qc/tickets/{ticket}', [InventoryTicketController::class, 'show'])->name('ticket.show');
        Route::get('/qc/tickets/{ticket}/report/{copy}', [InventoryTicketController::class, 'report'])
            ->whereIn('copy', ['vendor', 'warehouse'])->name('ticket.report');
        Route::get('/qc/tickets/{ticket}/qr', [InventoryController::class, 'ticketQr'])->name('ticket.qr');
        Route::post('/vendors', [InventoryTicketController::class, 'addVendor'])->name('vendors.store');
        Route::post('/variants', [InventoryController::class, 'addVariant'])->name('variants.store');
        Route::get('/variants/{variant}/qr', [InventoryController::class, 'skuQr'])->name('variants.qr');
    });

    Route::middleware('can:receive-stock')->group(function () {
        Route::get('/receiving', [InventoryController::class, 'receiving'])->name('receiving');
        Route::view('/receiving/scan', 'pages.inventory.scan')->name('receiving.scan');
        Route::get('/receiving/lookup', [InventoryTicketController::class, 'lookup'])->name('receiving.lookup');
        Route::get('/receiving/tickets/{ticket}', [InventoryTicketController::class, 'receive'])->name('ticket.receive');
        Route::get('/receiving/tickets/{ticket}/report', [InventoryTicketController::class, 'report'])->name('ticket.warehouse-report');
        Route::post('/receiving/tickets/{ticket}/confirm', [InventoryTicketController::class, 'confirm'])->name('ticket.confirm');
    });

    Route::get('/stock', [InventoryController::class, 'stock'])->middleware('can:view-stock')->name('stock');

    Route::prefix('dispatch')->name('dispatch.')->controller(InventoryDispatchController::class)->group(function () {
        Route::middleware('can:view-stock')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/tickets/{dispatch}', 'show')->whereNumber('dispatch')->name('show');
            Route::get('/tickets/{dispatch}/qr', 'qr')->whereNumber('dispatch')->name('qr');
            Route::get('/tickets/{dispatch}/print/{copy}', 'printable')->whereNumber('dispatch')
                ->whereIn('copy', ['qr', 'report'])->name('print');
        });

        Route::middleware('can:request-dispatch')->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/tickets', 'store')->name('store');
            Route::post('/tickets/{dispatch}/cancel', 'cancel')->whereNumber('dispatch')->name('cancel');
        });

        Route::middleware('can:receive-stock')->group(function () {
            Route::view('/scan', 'pages.inventory.scan', ['dispatch' => true])->name('scan');
            Route::get('/lookup', 'lookup')->name('lookup');
            Route::post('/tickets/{dispatch}/{action}', 'advance')->whereNumber('dispatch')
                ->whereIn('action', ['start', 'pack', 'ship'])->name('advance');
        });
    });
});

Route::get('/ecommerce', function () {
    return view('pages.dashboard.ecommerce', ['title' => 'E-commerce Dashboard']);
})->name('ecommerce');

// calendar pages
Route::get('/calendar', function () {
    return view('pages.calendar', ['title' => 'Calendar']);
})->name('calendar');

// profile pages
Route::get('/profile', function () {
    return view('pages.profile', ['title' => 'Profile']);
})->name('profile');

// form pages
Route::get('/form-elements', function () {
    return view('pages.forms.form-elements', ['title' => 'Form Elements']);
})->name('form-elements');

// tables pages
Route::get('/basic-tables', function () {
    return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
})->name('basic-tables');

// pages
Route::get('/blank', function () {
    return view('pages.pages.blank', ['title' => 'Blank']);
})->name('blank');

// error pages
Route::get('/error-404', function () {
    return view('pages.errors.error-404', ['title' => 'Error 404']);
})->name('error-404');

// chart pages
Route::get('/line-chart', function () {
    return view('pages.chart.line-chart', ['title' => 'Line Chart']);
})->name('line-chart');

Route::get('/bar-chart', function () {
    return view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
})->name('bar-chart');

// authentication pages
Route::view('/signin', 'pages.auth.signin')->middleware(SetInventoryLocale::class, 'guest')->name('signin');
Route::post('/signin', [InventorySessionController::class, 'store'])->middleware(SetInventoryLocale::class, 'guest', 'throttle:10,1')->name('signin.store');

Route::get('/signup', function () {
    return view('pages.auth.signup', ['title' => 'Sign Up']);
})->name('signup');

Route::post('/logout', [InventorySessionController::class, 'destroy'])->middleware('auth')->name('logout');

// ui elements pages
Route::get('/alerts', function () {
    return view('pages.ui-elements.alerts', ['title' => 'Alerts']);
})->name('alerts');

Route::get('/avatars', function () {
    return view('pages.ui-elements.avatars', ['title' => 'Avatars']);
})->name('avatars');

Route::get('/badge', function () {
    return view('pages.ui-elements.badge', ['title' => 'Badges']);
})->name('badge');

Route::get('/buttons', function () {
    return view('pages.ui-elements.buttons', ['title' => 'Buttons']);
})->name('buttons');

Route::get('/images', function () {
    return view('pages.ui-elements.images', ['title' => 'Images']);
})->name('images');

Route::get('/videos', function () {
    return view('pages.ui-elements.videos', ['title' => 'Videos']);
})->name('videos');

// legacy URLs
Route::redirect('/badges', '/badge');
