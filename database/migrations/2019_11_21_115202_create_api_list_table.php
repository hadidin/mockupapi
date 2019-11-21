<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApiListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_list', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',100)->default('name of api');
            $table->text('return_data')->comment('what shuld be the return in json format')->nullable();
            $table->string('http_status_code',3)->default('200')->comment('http status code');
            $table->string('enabled')->default(1)->nullable()->default(1)->comment('1=enable,0=disabled');
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
        Schema::dropIfExists('api_list');
    }
}
