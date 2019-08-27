<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveExcessReminderFieldsFromRegistrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('registrations', function($table) {
            $table->dropColumn('fm_chart_on');
            $table->dropColumn('fm_diary_on');
            $table->dropColumn('fm_privacy_on');
         });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('registrations', function($table) {
            $table->dateTime('fm_chart_on')->nullable();
            $table->dateTime('fm_diary_on')->nullable();
            $table->datetime('fm_privacy_on')->nullable();
         });
    }
}
