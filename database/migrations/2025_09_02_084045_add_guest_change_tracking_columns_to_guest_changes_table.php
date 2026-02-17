<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGuestChangeTrackingColumnsToGuestChangesTable extends Migration
{
    public function up()
    {
        Schema::table('guest_changes', function (Blueprint $table) {
            if (!Schema::hasColumn('guest_changes', 'booking_client_id')) {
                $table->unsignedInteger('booking_client_id')->nullable()->after('id');
                $table->foreign('booking_client_id')->references('id')->on('booking_clients')->onDelete('cascade');
            }

            if (!Schema::hasColumn('guest_changes', 'admin_confirmed_at')) {
                $table->timestamp('admin_confirmed_at')->nullable()->after('confirmed_at');
            }

            if (!Schema::hasColumn('guest_changes', 'admin_cancel_at')) {
                $table->timestamp('admin_cancelled_at')->nullable()->after('admin_confirmed_at');
            }
        });
    }

    public function down()
    {
        Schema::table('guest_changes', function (Blueprint $table) {
            $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('guest_changes');
            $foreignKeyExists = collect($foreignKeys)->contains(function ($fk) {
                return in_array('booking_client_id', $fk->getLocalColumns());
            });

            if ($foreignKeyExists) {
                $table->dropForeign(['booking_client_id']);
            }

            if (Schema::hasColumn('guest_changes', 'booking_client_id')) {
                $table->dropColumn('booking_client_id');
            }

            if (Schema::hasColumn('guest_changes', 'admin_confirmed_at')) {
                $table->dropColumn('admin_confirmed_at');
            }

            if (Schema::hasColumn('guest_changes', 'admin_cancelled_at')) {
                $table->dropColumn('admin_cancelled_at');
            }
        });
    }
}
