<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Files extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('feedid')->default('');
            $table->mediumText('ufile');
            $table->mediumText('pfile')->nullable();
            $table->mediumText('rfile')->nullable();
            $table->integer('success')->default(0);
            $table->integer('total')->default(0);
            $table->tinyInteger('mp_id');
            $table->tinyInteger('type');
            $table->tinyInteger('to_fetch')->default(0);
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
        Schema::drop('files');
    }
}
