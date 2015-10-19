<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FastbuildCounter extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
             Schema::create('fastbuild_counter', function ($table) {
                    $table->bigInteger('fastbuild_id')->primary();
                    $table->tinyInteger('status')->nullable()->default("1");
                    $table->dateTime('created_at')->nullable();
                    $table->dateTime('updated_at')->nullable();
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
