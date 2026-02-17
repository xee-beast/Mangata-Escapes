<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Dashboard
Route::middleware('auth:api', '2fa')->group(function () {
    Route::get('/otp/send', 'Auth\TwoFactorController@send')->name('otp.send');
    Route::post('/otp/verify', 'Auth\TwoFactorController@verify')->name('otp.verify');

    Route::get('/notifications', 'NotificationsController@index')->name('notifications.index');
    Route::get('/notifications/preview/{notification}', 'NotificationsController@preview')->name('notifications.preview');
    Route::prefix('notifications')->group(function() {
        Route::put('/status/{notificationClass}', 'NotificationsController@toggleNotificationStatus')->name('notifications.status.update');
        Route::get('/logs/{className}', 'NotificationsController@listNotificationLogs')->name('notifications.logs');
    });

    Route::get('/dashboard', 'DashboardController@data')->name('app.dashboard');

    Route::get('/results', 'ResultsController@index')->name('results');

    Route::put('/account/password', 'UserController@changePassword')->name('account.password');

    Route::apiResource('users', 'UserController');
    Route::put('/users/{user}/updatePasswordByAdmin', 'UserController@updatePasswordByAdmin')->name('users.updatePasswordByAdmin');
    Route::patch('/users/{user}/roles', 'UserController@syncRoles')->name('users.syncRoles');
    Route::patch('/users/{user}/permissions', 'UserController@syncPermissions')->name('users.syncPermissions');

    Route::apiResource('roles', 'RoleController');
    Route::patch('/roles/{role}/permissions', 'RoleController@syncPermissions')->name('roles.syncPermissions');

    Route::apiResource('destinations', 'DestinationController');
    Route::post('/destinations/validate-airport', 'DestinationController@validateAirport');

    Route::apiResource('hotels', 'HotelController');
    Route::prefix('hotels/{hotel}')->group(function() {
        Route::put('/images', 'HotelController@syncHotelImages')->name('hotels.syncImages');
        Route::apiResource('rooms', 'RoomController');
        Route::post('/enable', 'HotelController@enable')->name('hotels.enable');
        Route::apiResource('airport-rates', 'HotelAirportRateController', ['parameters' => [
            'airport-rates' => 'hotelAirportRate'
        ]])->only(['store', 'update', 'destroy']);

        Route::prefix('rooms/{room}')->group(function() {
            Route::patch('/beds', 'RoomController@updateBeds')->name('rooms.updateBeds');
        });
    });

    Route::apiResource('agents', 'TravelAgentController');
    Route::post('/agents/{agent}/enable', 'TravelAgentController@enable')->name('agents.enable');
    Route::post('/agents/{agent}/disable', 'TravelAgentController@disable')->name('agents.disable');

    Route::apiResource('providers', 'ProviderController');
    Route::prefix('providers/{provider}')->group(function() {
        Route::apiResource('insurance-rates', 'InsuranceRateController', ['parameters' => [
            'insurance-rates' => 'insuranceRate'
        ]]);

        Route::post('/specialists', 'ProviderController@updateSpecialists')->name('providers.updateSpecialists');
    });

    Route::get('/unpaid-bookings', 'UnpaidBookingController@index')->name('unpaid-bookings');

    Route::apiResource('groups', 'GroupController');
    Route::post('/send-bulk-email', 'GroupController@sendBulkEmail')->name('groups.sendBulkEmail');
    Route::post('/flight-details', 'BookingController@getFlightDetails');
    Route::prefix('groups/{group}')->group(function() {
        Route::patch('/toggle-booking-acceptance', 'GroupController@toggleBookingAcceptance')->name('groups.toggleBookingAcceptance');
        Route::patch('/due-dates', 'GroupController@syncDueDates')->name('groups.syncDueDates');
        Route::patch('/past-bride', 'GroupController@updatePastBride')->name('groups.updatePastBride');
        Route::post('/send-group-email', 'GroupController@sendGroupLeaderEmail')->name('groups.sendGroupEmail');
        Route::post('/send-group-leader-email', 'GroupController@sendGroupLeaderEmail')->name('groups.sendGroupLeaderEmailUnique');
        Route::patch('/attrition', 'GroupController@syncAttrition')->name('groups.syncAttrition');
        Route::patch('/faqs', 'GroupController@updateFaqs')->name('groups.updateFaqs');
        Route::patch('/terms-conditions', 'GroupController@updateTermsConditions')->name('groups.updateTermsConditions');
        Route::post('/restore', 'GroupController@restore')->name('groups.restore');
        Route::post('/send-couples-site-password-email', 'GroupController@sendCouplesSitePasswordEmail')->name('groups.sendCouplesSitePasswordEmail');
        Route::post('/send-email', 'GroupController@sendEmail')->name('groups.sendEmail');
        Route::post('/review-rooming-list', 'GroupController@reviewRoomingList')->name('groups.reviewRoomingList');
        Route::post('/export-rooming-list-comparison', 'GroupController@exportRoomingListComparison')->name('groups.exportRoomingListComparison');
        Route::post('/default-rates', 'GroupController@syncDefaultRates')->name('groups.syncDefaultRates');

        Route::apiResource('accomodations', 'AccomodationController', ['parameters' => [
            'accomodations' => 'roomBlock'
        ]]);
        Route::prefix('accomodations/{roomBlock}')->group(function() {
            Route::patch('/rates', 'AccomodationController@updateRates')->name('accomodations.updateRates');
            Route::patch('/room-toggle-active', 'AccomodationController@roomtoggleActive')->name('accomodations.toggleActive');
            Route::patch('/toggle-visibility', 'AccomodationController@toggleVisibility')->name('accomodations.toggleVisibility');
        });
        Route::prefix('accomodations/{hotelBlock}')->group(function() {
            Route::patch('/hotel-toggle-active', 'AccomodationController@hotelToggleActive')->name('accomodations.HoteltoggleActive');
        });

        Route::apiResource('bookings', 'BookingController');
        Route::prefix('bookings/{booking}')->group(function() {
            Route::patch('/update-notes', 'BookingController@updateNotes')->name('bookings.updateNotes');
            Route::patch('/confirm', 'BookingController@confirm')->name('booking.confirm');
            Route::patch('/restore', 'BookingController@restore')->name('booking.restore');
            Route::delete('/force', 'BookingController@forceDestroy')->name('booking.forceDelete');
            Route::patch('/move/up', 'BookingController@moveUp')->name('booking.moveUp');
            Route::patch('/move/down', 'BookingController@moveDown')->name('booking.moveDown');
            Route::patch('/guests', 'BookingController@updateGuests')->name('booking.guests');
            Route::patch('/update-travel-dates', 'BookingController@updateTravelDates')->name('booking.updateTravelDates');
            Route::patch('/update-departure-pickup-time', 'BookingController@updateDeparturePickupTime')->name('booking.updateDeparturePickupTime');
            Route::patch('/flight-manifests', 'BookingController@updateFlightManifests')->name('booking.flightManifests');
            Route::post('/send-invoice', 'BookingController@sendInvoice')->name('invoice.send');
            Route::post('/send-travel-documents', 'BookingController@sendTravelDocuments')->name('travelDocuments.send');
            Route::post('/send-fit-quote', 'BookingController@sendFitQuote')->name('fitQuote.send');
            Route::post('/cancel-fit-quote', 'BookingController@cancelFitQuote')->name('fitQuote.cancel');
            Route::get('/changes', 'BookingChangeController@index')->name('booking.changes');
            Route::patch('/confirm-changes', 'BookingChangeController@confirm')->name('booking.confirmChanges');
            Route::patch('/revert-changes', 'BookingChangeController@revert')->name('booking.revertChanges');
            Route::post('/payment-arrangements', 'BookingController@updatePaymentArrangements')->name('payment-arrangements.update');

            Route::get('/guest-changes/{guestChange?}', 'BookingChangeController@guestChanges')->name('booking.guestChanges');
            Route::patch('/confirm-guest-changes/{guestChange?}', 'BookingChangeController@confirmGuestChanges')->name('booking.confirmGuestChanges');
            Route::patch('/revert-guest-changes/{guestChange?}', 'BookingChangeController@revertGuestChanges')->name('booking.revertGuestChanges');

            Route::apiResource('clients', 'BookingClientController', ['parameters' => [
                'clients' => 'bookingClient'
            ]]);
            Route::prefix('clients/{bookingClient}')->group(function() {
                Route::patch('/card', 'BookingClientController@updateCard');
                Route::patch('/extras', 'BookingClientController@syncExtras');
            });

            Route::apiResource('payments', 'PaymentController')->except(['show']);
            Route::prefix('/payments/{payment}')->group(function () {
                Route::patch('/confirm', 'PaymentController@confirm')->name('payments.confirm');
                Route::delete('/force', 'PaymentController@forceDestroy')->name('payments.forceDelete');
            });
        });
    });

    Route::apiResource('individual-bookings', 'IndividualBookingController');
    Route::post('/send-individual-bookings-bulk-email', 'IndividualBookingController@sendBulkEmail')->name('individual-bookings.sendBulkEmail');
    Route::prefix('individual-bookings/{individual_booking}')->group(function() {
        Route::patch('/update-notes', 'IndividualBookingController@updateNotes')->name('individual-bookings.updateNotes');
        Route::patch('/confirm', 'IndividualBookingController@confirm')->name('individual-bookings.confirm');
        Route::patch('/restore', 'IndividualBookingController@restore')->name('individual-bookings.restore');
        Route::delete('/force', 'IndividualBookingController@forceDestroy')->name('individual-bookings.forceDelete');
        Route::put('/update-room-arrangements', 'IndividualBookingController@updateRoomArrangements')->name('individual-bookings.updateRoomArrangements');
        Route::patch('/booking-due-dates', 'IndividualBookingController@syncBookingDueDates')->name('individual-bookings.syncBookingDueDates');
        Route::patch('/update-travel-dates', 'IndividualBookingController@updateTravelDates')->name('individual-bookings.updateTravelDates');
        Route::post('/payment-arrangements', 'IndividualBookingController@updatePaymentArrangements')->name('individual-bookings.updatePaymentArrangements');
        Route::patch('/guests', 'IndividualBookingController@updateGuests')->name('individual-bookings.guests');
        Route::patch('/update-departure-pickup-time', 'IndividualBookingController@updateDeparturePickupTime')->name('individual-bookings.updateDeparturePickupTime');
        Route::patch('/flight-manifests', 'IndividualBookingController@updateFlightManifests')->name('individual-bookings.flightManifests');
        Route::patch('/terms-conditions', 'IndividualBookingController@updateTermsConditions')->name('individual-bookings.updateTermsConditions');
        Route::post('/send-fit-quote', 'IndividualBookingController@sendFitQuote')->name('individual-bookings.fitQuote.send');
        Route::post('/cancel-fit-quote', 'IndividualBookingController@cancelFitQuote')->name('individual-bookings.fitQuote.cancel');
        Route::post('/send-invoice', 'IndividualBookingController@sendInvoice')->name('individual-bookings.invoice.send');
        Route::post('/send-travel-documents', 'IndividualBookingController@sendTravelDocuments')->name('individual-bookings.travelDocuments.send');

        Route::apiResource('clients', 'IndividualBookingClientController', ['parameters' => ['clients' => 'bookingClient']]);
        Route::prefix('clients/{bookingClient}')->group(function() {
            Route::patch('/card', 'IndividualBookingClientController@updateCard');
            Route::patch('/extras', 'IndividualBookingClientController@syncExtras');
        });

        Route::apiResource('payments', 'IndividualBookingPaymentController')->except(['show'])->names('individual-bookings.payments');
        Route::prefix('/payments/{payment}')->group(function () {
            Route::patch('/confirm', 'IndividualBookingPaymentController@confirm')->name('individual-bookings.payments.confirm');
            Route::delete('/force', 'IndividualBookingPaymentController@forceDestroy')->name('individual-bookings.payments.forceDelete');
        });
    });

    Route::get('/pending', 'ToDoController@pending')->name('pending');

    Route::get('/calendar', 'CalendarController@index')->name('calendar');
    Route::get('/calendar/bookings', 'CalendarController@bookings')->name('calendar.bookings');
    Route::post('/calendar/events/store', 'CalendarController@store')->name('calendar.events.store');
    Route::post('/calendar/events/update', 'CalendarController@update')->name('calendar.events.update');
    Route::delete('/calendar/events/delete/{event_id}', 'CalendarController@delete')->name('calendar.events.delete');

    Route::apiResource('calendar-events', 'CalendarEventController');
    Route::get('/all/calendar-events', 'CalendarController@getAllEvents')->name('get.calendar.events');

    Route::apiResource('airlines', 'AirlineController');

    Route::apiResource('airports', 'AirportController');

    Route::apiResource('brands', 'BrandController');

    Route::apiResource('faqs', 'FaqController');

    Route::apiResource('transfers', 'TransferController');

    Route::get('/trash', 'TrashController@index')->name('trash');

    Route::apiResource('leads', 'LeadController');
    Route::patch('/sync-lead-options', 'LeadController@syncLeadOptions')->name('leads.syncLeadOptions');
    Route::prefix('/leads/{lead}')->group(function () {
        Route::patch('/update-notes', 'LeadController@updateNotes')->name('leads.updateNotes');
        Route::put('/hotel-requests', 'LeadController@updateHotelRequests')->name('leads.updateHotelRequests');
        Route::post('/supplier-email', 'LeadController@sendSupplierEmail')->name('leads.sendSupplierEmail');
        Route::post('/convert-proposal-document', 'LeadController@convertProposalDocument')->name('leads.convertProposalDocument');
    });
});

// Couples
Route::post('/groups/{group}/new-booking/seperate-client', 'Couples\BookingController@addClient');
Route::post('/groups/{group}/new-booking/search', 'Couples\BookingController@search');
Route::post('/groups/{group}/new-booking/{step}', 'Couples\BookingController@newBooking');
Route::post('/groups/{group}/invoice', 'Couples\InvoiceController@streamInvoice');
Route::post('/groups/{group}/reservation', 'Couples\ReservationController@showReservation');
Route::post('/groups/{group}/reservation/update', 'Couples\ReservationController@updateReservation');
Route::post('/groups/{group}/reservation/seperate-client', 'Couples\ReservationController@addSeperateClient');
Route::post('/groups/{group}/update-card/{step}', 'Couples\UpdateCardController@updateCard');
Route::post('/groups/{group}/new-payment/{step}', 'Couples\PaymentController@newPayment');
Route::post('/groups/{group}/new-flight-manifest/{step}', 'Couples\FlightManifestController@newFlightManifest');
Route::post('/groups/{group}/accept-fit-quote/{step}', 'Couples\FitQuoteController@acceptFitQuote');
Route::post('/groups/{group}/resend-code', 'Couples\SendCodeController@sendCode');
Route::post('/groups/{group}/booking-details', 'Couples\BookingDetailController@index');
Route::post('/groups/{group}/resend-password', 'Couples\SendPasswordController@index');
Route::post('/get/flight-time', 'Couples\FlightManifestController@getFlightTime');

// Individual Bookings
Route::post('/individual-bookings/seperate-client', 'Bookings\BookingController@addClient');
Route::post('/individual-bookings/new-booking/{step}', 'Bookings\BookingController@newBooking');
Route::post('/individual-bookings/invoice', 'Bookings\InvoiceController@streamInvoice');
Route::post('/individual-bookings/update-card/{step}', 'Bookings\UpdateCardController@updateCard');
Route::post('/individual-bookings/new-payment/{step}', 'Bookings\PaymentController@newPayment');
Route::post('/individual-bookings/new-flight-manifest/{step}', 'Bookings\FlightManifestController@newFlightManifest');
Route::post('/individual-bookings/accept-fit-quote/{step}', 'Bookings\FitQuoteController@acceptFitQuote');
Route::post('/individual-bookings/resend-code', 'Bookings\SendCodeController@sendCode');

// Lead Webhook
Route::post('/webhook', 'LeadController@handleWebhook');
