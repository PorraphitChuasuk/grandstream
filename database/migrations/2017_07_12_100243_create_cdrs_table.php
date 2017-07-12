<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCdrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('sqlsrv')->create('cdrs', function (Blueprint $table) {
            $table->increments('id');

            $table->string('cdr', 255);
            $table->string('acctid', 255);
            $table->string('accountcode', 255);
            $table->string('src', 255);
            $table->string('dst', 255);
            $table->string('dcontext', 255);
            $table->string('clid', 255);
            $table->string('channel', 255);
            $table->string('dstchannel', 255);
            $table->string('lastapp', 255);
            $table->string('lastdata', 255);
            $table->string('start', 255);
            $table->string('answer', 255);
            $table->string('end', 255);
            $table->string('duration', 255);
            $table->string('billsec', 255);
            $table->string('disposition', 255);
            $table->string('amaflags', 255);
            $table->string('uniqueid', 255);
            $table->string('userfield', 255);
            $table->string('channel_ext', 255);
            $table->string('dstchannel_ext', 255);
            $table->string('service', 255);
            $table->string('caller_name', 255);
            $table->string('recordfiles', 255);
            $table->string('dstanswer', 255);
            $table->string('chanext', 255);
            $table->string('dstchanext', 255);
            $table->string('session', 255);
            $table->string('action_owner', 255);
            $table->string('action_type', 255);
            $table->string('src_trunk_name', 255);
            $table->string('dst_trunk_name', 255);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('sqlsrv')->dropIfExists('cdrs');
    }
}
