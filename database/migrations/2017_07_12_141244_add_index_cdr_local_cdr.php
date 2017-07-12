<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexCdrLocalCdr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('local_cdrs', function (Blueprint $table) {
            $table->index(['acctid', 'session']);
        });

        Schema::connection('sqlsrv')->table('cdrs', function (Blueprint $table) {
            $table->index(['acctid', 'session']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
