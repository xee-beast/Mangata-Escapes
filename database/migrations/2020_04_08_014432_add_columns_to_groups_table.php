<?php

use App\Models\Group;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->enum('transportation_type', ['private', 'shared'])->default('private')->after('insurance_rate_id');
            $table->decimal('transportation_rate', 6, 2)->nullable()->after('insurance_rate_id');
            $table->enum('deposit_type', ['fixed', 'per person'])->default('fixed')->after('deposit');
            $table->text('notes')->nullable()->after('balance_due_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['notes', 'deposit_type', 'transportation_rate', 'transportation_type']);
        });
    }
}
