<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Models\BookingClient' => 'App\Policies\BookingClientPolicy',
        'App\Models\Booking' => 'App\Policies\BookingPolicy',
        'App\Models\Destination' => 'App\Policies\DestinationPolicy',
        'App\Models\Group' => 'App\Policies\GroupPolicy',
        'App\Models\Hotel' => 'App\Policies\HotelPolicy',
        'App\Models\InsuranceRate' => 'App\Policies\InsuranceRatePolicy',
        'App\Models\Lead' => 'App\Policies\LeadPolicy',
        'App\Models\Payment' => 'App\Policies\PaymentPolicy',
        'App\Models\Provider' => 'App\Policies\ProviderPolicy',
        'Spatie\Permission\Models\Role' => 'App\Policies\RolePolicy',
        'App\Models\RoomBlock' => 'App\Policies\RoomBlockPolicy',
        'App\Models\Room' => 'App\Policies\RoomPolicy',
        'App\Models\TravelAgent' => 'App\Policies\TravelAgentPolicy',
        'App\User' => 'App\Policies\UserPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
