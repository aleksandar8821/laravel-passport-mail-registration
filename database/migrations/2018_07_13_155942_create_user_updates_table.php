<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_updates', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            // Ovde ti mozda i ne treba primarni kljuc, jer npr password resets nema primarni kljuc, a tu migraciju nisam ja pravio nego dolazi po defaultu sa laravelom, tako da ocigledno moze bez primarnog kljuca, al ajd ostavicu ga
            $table->increments('id');
            // Pomocu ovoga unique() omogucujem da jedan user moze imati uvek samo jedan request za promenu podataka u bazi, dakle ne moze imati vise istovremeno. Video sam da se ovo radi i ovde https://laracasts.com/discuss/channels/laravel/trying-to-make-unique-foreign-key?page=1 i ovde https://stackoverflow.com/questions/36040419/mysql-laravel-making-foreign-key-unique . E DA, ALI TI NE ZNAS U KOJEM SU KONTEKSTU TO ONI RADILI. OVO SAD PISEM KASNIJE I MSM DA OVO BAS I NIJE NAJPAMETNIJE RESENJE (AL CU GA OSTAVITI ZBOG POUCNOG KODA I OVDE I U KONTROLERU (REGISTER)) JER DA BI UNEO NOVI REQUEST OD USERA KOJI VEC IMA POSTOJECI REQUEST U TABELI MORAS PRVO OBRISATI POSTOJECI, NE ZNAJUCI SIGURNO DAL CE TI SE NOVI UNETI, MADA CE TI SE NAJVEROVATNIJE UNETI AL AJD :D. (AKO TE ZANIMA DETALJNO SVE, POGLEDAJ U REGISTER CONTROLLERU U METODI updateUserDataWithMailConfirmation OBJASNJENJE PRE TRY CATCH BLOKA). MSM DA JE BOLJE DA RADIS KAO ZA PASSWORD RESETE. VIDI TU MIGRACIJU KOJA INACE DOLAZI UZ INSTALACIJU LARAVELA, TAKO DA BI MORALA BITI PRILICNO POUZDANA. VIDECES U NJOJ DA SE NE PRAVI UNIQUE POLJE ZA EMAIL, A KAKO SPRECAVAS DUPLIKATE VIDI U PASSWORD RESET KONTROLERU U METODI forgotPasswordRequest KAKO RADIS BRISANJE. MSM DA JE TAJ NACIN BOLJI JER MOZES U STARTU DA UNESES U TABELU REQUEST KOJI IMA ISTI EMAIL KAO I NEKI VEC POSTOJECI U TABELI - PA TEK KAD SI SIGURAN DA SI GA UNEO, TAD MOZES KOMOTNO OBRISATI OVAJ VEC POSTOJECI!
            $table->unsignedInteger('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('verify_token');
            $table->string('block_request_token')->nullable();//ovaj je nullable zato sto ovaj block request token jedino generisem ukoliko korisnik menja svoj email, pa mu na stari mail stize link sa ovim tokenom da blokira request i ponisti promene ukoliko nije on napravio request, nego mu je neki zlonamerni korisnik upao na nalog i napravio request za promenu podataka
            // Osim primarnog i spoljnog kljuca, stavljam sva polja koja spadaju u osetljive podatke (zapravo ISKLJUCIVO ta polja), jer se ovi neosetljivi updateuju bez potvrde sifre i maila
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
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
        Schema::dropIfExists('user_updates');
    }
}
