<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Route::bind('bookingClient', function ($bookingClient, $route) {
            $bookingId = $route->originalParameter('booking') ?? $route->originalParameter('individual_booking');

            return \App\Models\BookingClient::where('booking_id', $bookingId)->findOrFail($bookingClient);
        });

        Route::bind('roomBlock', function ($roomBlock, $route) {
            return \App\Models\RoomBlock::whereHas('hotel_block', function ($query) use ($route) {
                $query->where('group_id', $route->originalParameter('group'));
            })->findOrFail($roomBlock);
        });

        Route::bind('individual_booking', function ($individual_booking, $route) {
            return \App\Models\Booking::withTrashed()->findOrFail($individual_booking);
        });

        Route::bind('booking', function ($booking, $route) {
            return \App\Models\Booking::withTrashed()->where('group_id', $route->originalParameter('group'))->findOrFail($booking);
        });

        Route::bind('group', function ($group) {
            if (request()->is('vapor-ui/*')) {
                return $group;
            }
            
            return \App\Models\Group::withTrashed()->where('id', $group)->orWhere('slug', $group)->firstOrFail();
        });

        Route::bind('insuranceRate', function ($insuranceRate, $route) {
            return \App\Models\InsuranceRate::where('provider_id', $route->parameter('provider'))->findOrFail($insuranceRate);
        });

        Route::bind('payment', function ($payment, $route) {
            $bookingId = $route->originalParameter('booking') ?? $route->originalParameter('individual_booking');

            return \App\Models\Payment::whereHas('booking_client', function ($query) use ($bookingId) {
                $query->where('booking_id', $bookingId);
            })->findOrFail($payment);
        });

        Route::bind('hotel', function ($hotel, $route) {
            if (!empty($route->parameter('room'))) {
                return $hotel;
            }

            return \App\Models\Hotel::withTrashed()->where('id', $hotel)->firstOrFail();
        });        

        Route::bind('room', function ($room, $route) {
            return \App\Models\Room::where('hotel_id', $route->parameter('hotel'))->findOrFail($room);
        });
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }
}
