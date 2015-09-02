<?php
 
//
// NOTE Migration Created: 2015-09-02 07:09:36
// --------------------------------------------------
 
class CreateRatesDatabase
{
    //
    // NOTE - Make changes to the database.
    // --------------------------------------------------
     
    public function up()
    {
        //
        // NOTE -- service_types
        // --------------------------------------------------

        Schema::create('service_types', function ($table) {
            $table->bigInteger('id')->primary();
            $table->string('name', 255)->unique();
            $table->boolean('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
        });

        //
        // NOTE -- regions
        // --------------------------------------------------

        Schema::create('regions', function ($table) {
            $table->bigInteger('id')->primary();
            $table->string('name', 255)->unique();
            $table->bigInteger('parent_id');
            $table->tinyInteger('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
        });

        //
        // NOTE -- currencies
        // --------------------------------------------------

        Schema::create('currencies', function ($table) {
            $table->bigIncrements('id');
            $table->string('code', 12)->unique();
            $table->string('name', 25)->unique();
            $table->string('symbol', 255)->unique();
            $table->boolean('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
        });

        //
        // NOTE -- suppliers
        // --------------------------------------------------
         
        Schema::create('suppliers', function ($table) {
            $table->bigIncrements('id');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->bigInteger('region_id');
            $table->boolean('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
            $table->foreign('region_id')->references('id')->on('regions');
        });

        //
        // NOTE -- services
        // --------------------------------------------------

        Schema::create('services', function ($table) {
            $table->bigInteger('id')->primary();
            $table->string('short_name', 25)->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->bigInteger('service_type_id');
            $table->bigInteger('region_id');
            $table->bigInteger('supplier_id')->unsigned();
            $table->bigInteger('currency_id')->unsigned();
            $table->boolean('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
            $table->foreign('service_type_id')->references('id')->on('service_types');
            $table->foreign('region_id')->references('id')->on('regions');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
            $table->foreign('currency_id')->references('id')->on('currencies');
        });

        //
        // NOTE -- exchange_rates
        // --------------------------------------------------

        Schema::create('exchange_rates', function ($table) {
            $table->bigIncrements('id');
            $table->string('from_currency', 12);
            $table->string('to_currency', 12);
            $table->float('rate');
            $table->boolean('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
        });

        //
        // NOTE -- occupancies
        // --------------------------------------------------

        Schema::create('occupancies', function ($table) {
            $table->bigIncrements('id');
            $table->string('name', 25)->unique();
            $table->unsignedInteger('min_adults');
            $table->unsignedInteger('max_adults');
            $table->boolean('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
        });

        //
        // NOTE -- meals
        // --------------------------------------------------

        Schema::create('meals', function ($table) {
            $table->bigIncrements('id');
            $table->string('name', 255)->unique();
            $table->boolean('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
        });

        //
        // NOTE -- service_options
        // --------------------------------------------------

        Schema::create('service_options', function ($table) {
            $table->bigIncrements('id');
            $table->bigInteger('service_id');
            $table->bigInteger('occupancy_id')->unsigned();
            $table->string('name', 255);
            $table->boolean('has_extra')->nullable();
            $table->boolean('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
            $table->foreign('service_id')->references('id')->on('services');
            $table->foreign('occupancy_id')->references('id')->on('occupancies');
        });

        //
        // NOTE -- service_extras
        // --------------------------------------------------

        Schema::create('service_extras', function ($table) {
            $table->bigIncrements('id');
            $table->bigInteger('service_option_id')->unsigned();
            $table->string('name', 255);
            $table->boolean('mandatory')->nullable();
            $table->boolean('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
            $table->foreign('service_option_id')->references('id')->on('service_options');
        });
        
        //
        // NOTE -- meal_options
        // --------------------------------------------------

        Schema::create('meal_options', function ($table) {
            $table->bigIncrements('id');
            $table->bigInteger('service_option_id')->unsigned();
            $table->bigInteger('meal_id')->unsigned();
            $table->boolean('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
            $table->foreign('service_option_id')->references('id')->on('service_options');
            $table->foreign('meal_id')->references('id')->on('meals');
        });

        //
        // NOTE -- seasons
        // --------------------------------------------------

        Schema::create('seasons', function ($table) {
            $table->bigIncrements('id');
            $table->bigInteger('service_id');
            $table->string('name', 255);
            $table->boolean('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
            $table->foreign('service_id')->references('id')->on('services');
        });

        //
        // NOTE -- season_periods
        // --------------------------------------------------

        Schema::create('season_periods', function ($table) {
            $table->bigIncrements('id');
            $table->bigInteger('season_id')->unsigned();
            $table->date('start');
            $table->date('end');
            $table->boolean('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
            $table->foreign('season_id')->references('id')->on('seasons');
        });

        //
        // NOTE -- prices
        // --------------------------------------------------

        Schema::create('prices', function ($table) {
            $table->bigIncrements('id');
            $table->bigInteger('priceable_id');
            $table->string('priceable_type', 255);
            $table->bigInteger('season_period_id')->unsigned();
            $table->bigInteger('service_id');
            $table->decimal('buy_price', 10, 2);
            $table->decimal('sell_price', 10, 2);
            $table->boolean('has_details')->nullable();
            $table->boolean('status')->nullable()->default("1");
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->engine = 'InnoDB';
            $table->foreign('season_period_id')->references('id')->on('season_periods');
            $table->foreign('service_id')->references('id')->on('services');
        });
    }
 
    //
    // NOTE - Revert the changes to the database.
    // --------------------------------------------------
 
    public function down()
    {
        Schema::drop('prices');
        Schema::drop('season_periods');
        Schema::drop('seasons');
        Schema::drop('meal_options');
        Schema::drop('service_extras');
        Schema::drop('service_options');
        Schema::drop('meals');
        Schema::drop('occupancies');
        Schema::drop('exchange_rates');
        Schema::drop('services');
        Schema::drop('suppliers');
        Schema::drop('currencies');
        Schema::drop('regions');
        Schema::drop('service_types');
    }
}
