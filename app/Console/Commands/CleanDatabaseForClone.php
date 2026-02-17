<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanDatabaseForClone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean-for-clone
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One-time cleanup: Remove all data except reference data and a single developer user. Only runs when APP_ENV is not production.';

    /**
     * Tables to preserve (keep all data intact).
     */
    protected const TABLES_TO_KEEP = [
        'permissions',
        'roles',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'airlines',
        'airport_destination',
        'airports',
        'calendar_events',
        'calendars',
        'contacted_us_options',
        'countries',
        'destinations',
        'faqs',
        'hotels',
        'imageables',
        'images',
        'insurance_rates',
        'hotel_airport_rates',
        'migrations',
        'providers',
        'room_blocks',
        'rooms',
        'specialists',
        'states',
        'transfers',
        'transportation_types',
        'travel_agents',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('This command cannot run in production environment.');
            return self::FAILURE;
        }

        $this->warn('This will permanently delete data from the database.');
        $this->warn('Kept: ' . implode(', ', self::TABLES_TO_KEEP));
        $this->warn('Users: only developers@softpyramid.dev will remain.');

        if (! $this->option('force') && ! $this->confirm('Do you want to continue?')) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        try {
            Schema::disableForeignKeyConstraints();

            $devUser = DB::table('users')->where('email', 'developers@softpyramid.dev')->first();

            if (! $devUser) {
                $this->error('User developers@softpyramid.dev not found. Aborting.');
                Schema::enableForeignKeyConstraints();
                return self::FAILURE;
            }

            $tablesToTruncate = $this->getTablesToTruncate();

            // 1. Remove travel_agent records for users we're about to delete (to satisfy FK)
            if (Schema::hasTable('travel_agents')) {
                $this->info('Cleaning travel_agents for non-developer users...');
                DB::table('travel_agents')->where('user_id', '!=', $devUser->id)->delete();
            }

            // 2. Remove model_has_roles and model_has_permissions for deleted users
            $this->info('Cleaning permission assignments for non-developer users...');
            if (Schema::hasTable('model_has_roles')) {
                DB::table('model_has_roles')
                    ->where('model_type', 'App\User')
                    ->where('model_id', '!=', $devUser->id)
                    ->delete();
            }
            if (Schema::hasTable('model_has_permissions')) {
                DB::table('model_has_permissions')
                    ->where('model_type', 'App\User')
                    ->where('model_id', '!=', $devUser->id)
                    ->delete();
            }

            // 3. Delete all users except developers@softpyramid.dev
            $this->info('Deleting non-developer users...');
            $deletedUsers = DB::table('users')->where('id', '!=', $devUser->id)->delete();
            $this->line("Deleted {$deletedUsers} user(s).");

            // 4. Truncate all other non-kept tables
            foreach ($tablesToTruncate as $table) {
                if (Schema::hasTable($table)) {
                    try {
                        DB::table($table)->truncate();
                        $this->line("Truncated: {$table}");
                    } catch (\Throwable $e) {
                        // If truncate fails (e.g. FK constraints), try delete
                        $count = DB::table($table)->count();
                        DB::table($table)->delete();
                        $this->line("Deleted {$count} row(s) from: {$table}");
                    }
                }
            }

            $this->newLine();
            $this->info('Database cleaned successfully. Only developers@softpyramid.dev and reference data remain.');
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            throw $e;
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return self::SUCCESS;
    }

    /**
     * Get all database tables that should be truncated (not in keep list, excluding users).
     */
    protected function getTablesToTruncate(): array
    {
        $database = config('database.connections.' . config('database.default') . '.database');
        $tables = [];

        if (config('database.default') === 'mysql') {
            $results = DB::select("SHOW TABLES FROM `{$database}`");
            $key = "Tables_in_{$database}";
            foreach ($results as $row) {
                $tables[] = $row->{$key};
            }
        } else {
            $tables = Schema::getTables();
            $tables = array_map(fn ($t) => $t['name'], $tables);
        }

        return array_values(array_diff($tables, self::TABLES_TO_KEEP, ['users']));
    }
}
