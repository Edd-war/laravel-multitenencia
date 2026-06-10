<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuariosTable extends Migration
{
    public function up()
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('usuario');
            $table->string('correo')->unique();
            $table->timestamp('verificado')->nullable();
            $table->string('clave');
            $table->rememberToken();
            $table->timestamps();
        });
    }
}
