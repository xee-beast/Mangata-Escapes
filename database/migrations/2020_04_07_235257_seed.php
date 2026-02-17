<?php

use App\Models\Country;
use App\Models\Provider;
use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Seed extends Migration
{
    /**
     * Setup the migration so that all cached permissions can be reset.
     */
    public function __construct()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * Create Permissions
         */
        // Roles & Permissions
        Permission::create(['name' => 'manage roles', 'description' => 'Can create, update & delete roles.']);
        // Users
        Permission::create(['name' => 'view users', 'description' => 'Can view any user.']);
        Permission::create(['name' => 'create users', 'description' => 'Can create new users.']);
        Permission::create(['name' => 'update users', 'description' => 'Can update existing users.']);
        Permission::create(['name' => 'delete users', 'description' => 'Can delete users.']);
        Permission::create(['name' => 'manage user roles', 'description' => 'Can assign & remove roles from users.']);
        Permission::create(['name' => 'manage user permissions', 'description' => 'Can assign & remove direct permissions from users.']);
        // Destinations
        Permission::create(['name' => 'view destinations', 'description' => 'Can view any destination.']);
        Permission::create(['name' => 'create destinations', 'description' => 'Can add destinations.']);
        Permission::create(['name' => 'update destinations', 'description' => 'Can update existing destination details.']);
        Permission::create(['name' => 'delete destinations', 'description' => 'Can remove destinations from the catalog.']);
        // Hotels
        Permission::create(['name' => 'view hotels', 'description' => 'Can view any hotels information.']);
        Permission::create(['name' => 'create hotels', 'description' => 'Can add new hotels.']);
        Permission::create(['name' => 'update hotels', 'description' => 'Can update existing hotels.']);
        Permission::create(['name' => 'delete hotels', 'description' => 'Can delete hotels.']);
        // Providers
        Permission::create(['name' => 'view providers', 'description' => 'Can view provider information.']);
        Permission::create(['name' => 'create providers', 'description' => 'Can add new providers.']);
        Permission::create(['name' => 'update providers', 'description' => 'Can update existing provider information.']);
        Permission::create(['name' => 'delete providers', 'description' => 'Can remove providers.']);
        Permission::create(['name' => 'update insurance rates', 'description' => 'Can update insurance rates linked to at least one group.']);
        // Travel Agents
        Permission::create(['name' => 'view travel agents', 'description' => 'Can view any travel agent.']);
        Permission::create(['name' => 'create travel agents', 'description' => 'Can add new travel agents.']);
        Permission::create(['name' => 'update travel agents', 'description' => 'Can update existing travel agents.']);
        Permission::create(['name' => 'delete travel agents', 'description' => 'Can delete travel agents.']);
        // Groups
        Permission::create(['name' => 'view groups', 'description' => 'Can view a listing of all groups.']);
        Permission::create(['name' => 'create groups', 'description' => 'Can create new groups.']);
        Permission::create(['name' => 'update groups', 'description' => 'Can update existing groups information.']);
        Permission::create(['name' => 'delete groups', 'description' => 'Can delete existing groups.']);
        /**
         * Create roles and assign permissions
         */
        // Super Admin
        Role::create([
            'name' => 'super admin',
            'description' => 'Gives the user all the permissions.',
        ])->givePermissionTo(
            Permission::all()
        );
        // Admin
        Role::create([
            'name' => 'admin',
            'description' => 'Can manage other users.',
        ])->givePermissionTo([
            'view users', 'create users', 'update users', 'delete users',
            'manage user roles', 'manage user permissions',
        ]);
        // Travel Agent
        Role::create([
            'name' => 'Travel Agent',
            'description' => 'Responsible for managing wedding groups & keeping the brides satisfied.',
        ])->givePermissionTo([
            'view destinations', 'create destinations', 'update destinations', 'delete destinations',
            'view hotels', 'create hotels', 'update hotels', 'delete hotels',
            'view groups', 'create groups',
        ]);
        // Bookings Agent
        Role::create([
            'name' => 'Bookings Agent',
            'description' => 'Processes bookings & payments in 3rd party systems.',
        ])->givePermissionTo([
            'view groups'
        ]);

        /**
         * Create Super Admin User
         */
            $user = User::create([
                'first_name' => 'Sharon',
                'last_name' => 'Kopp',
                'username' => 's.kopp',
                'email' => 'sharon@barefootbridal.com',
                'password' => '$2y$10$ZH3sy6l8JucwcsDFPZoLXe4IQlKR3zAagMd8SYV2aEGUcMzh6cK8G',
                'email_verified_at' => now(),
            ]);
            $user->assignRole('super admin');
            $user->travel_agent()->create([
                'first_name' => 'Sharon',
                'last_name' => 'Kopp',
        ]);

            /*
            * Create Countries and States
            */
            Country::create(['name' => 'United States Of America'])->states()->createMany([
                [
                    'name' => 'Alabama',
                    'abbreviation' => 'AL',
                ],
                [
                    'name' => 'Alaska',
                    'abbreviation' => 'AK',
                ],
                [
                    'name' => 'Arizona',
                    'abbreviation' => 'AZ',
                ],
                [
                    'name' => 'Arkansas',
                    'abbreviation' => 'AR',
                ],
                [
                    'name' => 'California',
                    'abbreviation' => 'CA',
                ],
                [
                    'name' => 'Colorado',
                    'abbreviation' => 'CO',
                ],
                [
                    'name' => 'Connecticut',
                    'abbreviation' => 'CT',
                ],
                [
                    'name' => 'Delaware',
                    'abbreviation' => 'DE',
                ],
                [
                    'name' => 'Florida',
                    'abbreviation' => 'FL',
                ],
                [
                    'name' => 'Georgia',
                    'abbreviation' => 'GA',
                ],
                [
                    'name' => 'Hawaii',
                    'abbreviation' => 'HI',
                ],
                [
                    'name' => 'Idaho',
                    'abbreviation' => 'ID',
                ],
                [
                    'name' => 'Illinois',
                    'abbreviation' => 'IL',
                ],
                [
                    'name' => 'Indiana',
                    'abbreviation' => 'IN',
                ],
                [
                    'name' => 'Iowa',
                    'abbreviation' => 'IA',
                ],
                [
                    'name' => 'Kansas',
                    'abbreviation' => 'KS',
                ],
                [
                    'name' => 'Kentucky',
                    'abbreviation' => 'KY',
                ],
                [
                    'name' => 'Louisiana',
                    'abbreviation' => 'LA',
                ],
                [
                    'name' => 'Maine',
                    'abbreviation' => 'ME',
                ],
                [
                    'name' => 'Maryland',
                    'abbreviation' => 'MD',
                ],
                [
                    'name' => 'Massachusetts',
                    'abbreviation' => 'MA',
                ],
                [
                    'name' => 'Michigan',
                    'abbreviation' => 'MI',
                ],
                [
                    'name' => 'Minnesota',
                    'abbreviation' => 'MN',
                ],
                [
                    'name' => 'Mississippi',
                    'abbreviation' => 'MS',
                ],
                [
                    'name' => 'Missouri',
                    'abbreviation' => 'MO',
                ],
                [
                    'name' => 'Montana',
                    'abbreviation' => 'MT',
                ],
                [
                    'name' => 'Nebraska',
                    'abbreviation' => 'NE',
                ],
                [
                    'name' => 'Nevada',
                    'abbreviation' => 'NV',
                ],
                [
                    'name' => 'New Hampshire',
                    'abbreviation' => 'NH',
                ],
                [
                    'name' => 'New Jersey',
                    'abbreviation' => 'NJ',
                ],
                [
                    'name' => 'New Mexico',
                    'abbreviation' => 'NM',
                ],
                [
                    'name' => 'New York',
                    'abbreviation' => 'NY',
                ],
                [
                    'name' => 'North Carolina',
                    'abbreviation' => 'NC',
                ],
                [
                    'name' => 'North Dakota',
                    'abbreviation' => 'ND',
                ],
                [
                    'name' => 'Ohio',
                    'abbreviation' => 'OH',
                ],
                [
                    'name' => 'Oklahoma',
                    'abbreviation' => 'OK',
                ],
                [
                    'name' => 'Oregon',
                    'abbreviation' => 'OR',
                ],
                [
                    'name' => 'Pennsylvania',
                    'abbreviation' => 'PA',
                ],
                [
                    'name' => 'Rhode Island',
                    'abbreviation' => 'RI',
                ],
                [
                    'name' => 'South Carolina',
                    'abbreviation' => 'SC',
                ],
                [
                    'name' => 'South Dakota',
                    'abbreviation' => 'SD',
                ],
                [
                    'name' => 'Tennessee',
                    'abbreviation' => 'TN',
                ],
                [
                    'name' => 'Texas',
                    'abbreviation' => 'TX',
                ],
                [
                    'name' => 'Utah',
                    'abbreviation' => 'UT',
                ],
                [
                    'name' => 'Vermont',
                    'abbreviation' => 'VT',
                ],
                [
                    'name' => 'Virginia',
                    'abbreviation' => 'VA',
                ],
                [
                    'name' => 'Washington',
                    'abbreviation' => 'WA',
                ],
                [
                    'name' => 'West Virginia',
                    'abbreviation' => 'WV',
                ],
                [
                    'name' => 'Wisconsin',
                    'abbreviation' => 'WI',
                ],
                [
                    'name' => 'Wyoming',
                    'abbreviation' => 'WY',
                ],
            ]);
            Country::create([
                'name' => 'Canada',
                'division' => 'Province',
            ])->states()->createMany([
                [
                    'name' => 'Alberta',
                    'abbreviation' => 'AB',
                ],
                [
                    'name' => 'British Columbia',
                    'abbreviation' => 'BC',
                ],
                [
                    'name' => 'Manitoba',
                    'abbreviation' => 'MB',
                ],
                [
                    'name' => 'New Brunswick',
                    'abbreviation' => 'NB',
                ],
                [
                    'name' => 'Newfoundland and Labrador',
                    'abbreviation' => 'NL',
                ],
                [
                    'name' => 'Northwest Territories',
                    'abbreviation' => 'NT',
                ],
                [
                    'name' => 'Nova Scotia',
                    'abbreviation' => 'NS',
                ],
                [
                    'name' => 'Nunavut',
                    'abbreviation' => 'NU',
                ],
                [
                    'name' => 'Ontario',
                    'abbreviation' => 'ON',
                ],
                [
                    'name' => 'Prince Edward Island',
                    'abbreviation' => 'PE',
                ],
                [
                    'name' => 'Quebec',
                    'abbreviation' => 'QC',
                ],
                [
                    'name' => 'Saskatchewan',
                    'abbreviation' => 'SK',
                ],
                [
                    'name' => 'Yukon',
                    'abbreviation' => 'YT',
                ],
            ]);
            Country::create(['name' => 'Dominican Republic'])->destinations()->create([
                'name' => 'Punta Cana',
                'airport_code' => 'PUJ',
            ]);
            Country::create(['name' => 'Mexico'])->destinations()->create([
                'name' => 'Cancun',
                'airport_code' => 'CUN',
            ]);


            Provider::create([
                'name' => 'Classic Vacations',
                'abbreviation' => 'CV',
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No going back.
    }
}
