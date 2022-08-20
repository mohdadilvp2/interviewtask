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
         Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('companies_file_path');
            $table->string('contacts_file_path');
            $table->tinyInteger('status')->default(0)->comment = '0-New | 1-Progress | 2-Done | 4-Error ';
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
        Schema::dropIfExists('files');
    }
};
