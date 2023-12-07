<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('host_companies', function (Blueprint $table) {
            
            $table->id();
            $table->string('logo')->nullable();
            $table->string('flat_number')->nullable();
            $table->string('floor_number')->nullable();
            $table->string('company_name')->nullable();
            $table->string('manager_name')->nullable();
            $table->string('number')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->string('email')->nullable();
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->integer('zone_id')->unsigned()->default(1);
            $table->boolean('weekend')->nullable();
            $table->boolean('webaccess')->default(false);
            $table->string('address')->nullable();
            $table->integer('company_id')->unsigned()->default(0);
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
        Schema::dropIfExists('hosts');
    }
};
