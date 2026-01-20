<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::connection('pgsql')->create('avshocrecat_report', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('magazyn', 255)->nullable();
            $table->text('karz_alyjy')->nullable();
            $table->string('telefon_belgisi', 255)->nullable();
            $table->string('pasport_belgisi', 255)->nullable();
            $table->string('sertnama_nomeri', 255)->nullable();
            $table->string('tiger_kody', 255)->nullable();

            $table->decimal('kt_cykdajy', 18, 2)->nullable();
            $table->decimal('dt_girdeji', 18, 2)->nullable();

            $table->decimal('m1', 18, 2)->nullable();
            $table->decimal('m2', 18, 2)->nullable();
            $table->decimal('m3', 18, 2)->nullable();
            $table->decimal('m4', 18, 2)->nullable();
            $table->decimal('m5', 18, 2)->nullable();
            $table->decimal('m6', 18, 2)->nullable();

            $table->decimal('galyndy', 18, 2)->nullable();
            $table->decimal('aylyk_tolegi', 18, 2)->nullable();

            $table->string('karz_alan_senesi', 30)->nullable();
            $table->string('gutaryan_senesi', 30)->nullable();

            $table->string('kategoriyasy', 255)->nullable();
            $table->string('maglumat', 255)->nullable();
            $table->string('bellik', 255)->nullable();

            $table->date('tolejek_senesi')->nullable();
            $table->string('statusy', 255)->nullable();

            $table->timestamps();

            $table->index('pasport_belgisi');
            $table->index('tiger_kody');
            $table->index('magazyn');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('avshocrecat_report');
    }
};
