<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDataVersions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_data_versions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // Ovaj user_update_id vucem iz tabele user_updates i treba mi da bi red u ovoj tabeli mogao sigurno jednoznacno da targetiram. Jeste i da ga id jednoznacno odredjuje, ali meni treba nesto sto ce ga jednoznacno odrediti jos pre nego sto sacuvam taj red u tabeli, da bi ga mogao koristiti u old emailu za rollback (revoke changes kako god) u ovoj tabeli
            $table->unsignedInteger('user_update_id');
            $table->string('rollback_revoke_changes_token')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('password');
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
        Schema::dropIfExists('user_data_versions');
    }
}
