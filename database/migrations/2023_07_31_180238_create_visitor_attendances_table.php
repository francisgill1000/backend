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
        Schema::create('visitor_attendances', function (Blueprint $table) {
            $table->id();
            $table->date("date")->nullable();
            $table->string("visitor_id")->default(0);
            $table->string("status")->default("A");
            $table->string("in")->default("---");
            $table->string("out")->default("---");
            $table->string("total_hrs")->default("---");
            $table->string("device_id_in")->default("---");
            $table->string("device_id_out")->default("---");
            $table->string("date_in")->default("---");
            $table->string("date_out")->default("---");
            $table->integer("company_id")->default(0);
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
        Schema::dropIfExists('visitor_attendances');
    }
};
