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
        Schema::create('employee_leaves', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('employee_id');
            $table->integer('leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason')->nullable();
            $table->integer('reporting_manager_id');
            $table->integer('status')->default(0);; //1 approved //0 pending // 2 rejected
            $table->string('status_notes')->nullable();
            $table->date('employee_cancelled_date')->nullable();
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
        Schema::dropIfExists('employee_leaves');
    }
};