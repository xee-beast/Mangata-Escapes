<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Auth & Dashboard - path-based routing
Route::prefix('auth')->group(function () {
    Route::get('/refresh-token', function () {
        return response()->json('Token Refreshed!');
    })->middleware('auth');

    Auth::routes([
        'verify' => true,
        'register' => false
    ]);

    Route::get('/2fa', 'Auth\TwoFactorController@show')->name('2fa');
    Route::post('/2fa', 'Auth\TwoFactorController@verify')->name('2fa.verify');
    Route::get('/2fa/resend', 'Auth\TwoFactorController@resend')->name('2fa.resend');
    Route::get('/2fa/cancel', 'Auth\TwoFactorController@cancel')->name('2fa.cancel');
});

Route::prefix('dashboard')->middleware(['auth', 'verified', '2fa'])->group(function () {
    Route::get('individual-bookings/{booking}/invoice', 'IndividualBookingController@streamInvoice')->name('dashboard.individual-bookings-invoice');
    Route::get('individual-bookings/{booking}/travel-documents', 'IndividualBookingController@streamTravelDocuments')->name('dashboard.individual-bookings-travel-documents');

    Route::get('groups/{group}/bookings/{booking}/invoice', 'BookingController@streamInvoice')->name('dashboard.invoice');
    Route::get('groups/{group}/bookings/{booking}/travel-documents', 'BookingController@streamTravelDocuments')->name('dashboard.travel-documents');
    Route::get('groups/{group}/bookings-export', 'GroupController@exportBookingsToExcel')->name('dashboard.bookings-export');
    Route::get('groups/{group}/flight-manifests-export', 'GroupController@exportFlightManifestsToExcel')->name('dashboard.flight-manifests-export');

    Route::get('/{uri?}', function () {
        return view('dashboard');
    })->where('uri', '.*')->defaults('uri', '')->name('dashboard');
});

// Couples
Route::prefix('couples')->group(function () {
    Route::get('/', function () {
        return redirect(config('app.url'));
    });

    Route::prefix('/{group}')->middleware(['group', 'couples.password'])->group(function () {
        Route::get('/', 'Couples\PageController@index')->name('couples');
        Route::post('/invoice', 'Couples\InvoiceController@streamInvoice');
        Route::post('/quote-invoice', 'Couples\FitQuoteController@streamQuoteInvoice');
        Route::get('/terms-conditions', 'Couples\PageController@termsConditions')->name('termsConditions');
    });

    Route::get('/{group}/password', 'Couples\CouplesPasswordController@show')->name('couples.password');
    Route::post('/{group}/password', 'Couples\CouplesPasswordController@verify')->name('couples.password.verify');
});

// Bookings
Route::prefix('bookings')->group(function () {
    Route::get('/', 'Bookings\PageController@index')->name('individual-bookings.page');
    Route::post('/invoice', 'Bookings\InvoiceController@streamInvoice');
    Route::post('/quote-invoice', 'Bookings\FitQuoteController@streamQuoteInvoice');
    Route::get('/terms-conditions/{individual_booking}', 'Bookings\PageController@termsConditions')->name('individual-bookings.termsConditions');
});

// Main
Route::get('/', function () {
    // TODO: as we currently only cloning the barefootbridal.com website, we need to redirect to the dashboard url
    return redirect(config('app.dashboard_url'));
})->name('web.home');

Route::get('/auth', function () {
    return redirect()->route('login');
});
Route::get('/about', 'WebController@about')->name('web.about');
Route::get('/about/team', 'WebController@team')->name('web.about.team');
Route::get('/about/brides', 'WebController@brides')->name('web.about.brides');
Route::get('/services', 'WebController@services')->name('web.services');
Route::get('/contact', 'WebController@contact')->name('web.contact');
// Route::get('/blog', 'WebController@blog')->name('web.blog');
Route::post('/contact/submit', 'WebController@submit')->name('newLead');

Route::get('/{route}', function ($route) {
    return redirect(config('app.group_url') . '/' . $route);
})->where('route', '.*');
