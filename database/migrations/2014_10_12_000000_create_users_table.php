<?php

use App\Enums\UserRoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('firstname');
            $table->string('lastname');

            $table->string('email')->unique();

            $table->string('phonenumber')->unique()->nullable(false);

            $table->string('position')->nullable();
            $table->string('role')->default(UserRoleEnum::USER->value);

            $table->boolean('status')->default(true)->comment('1=Active, 0=Deactive');

            $table->string('password');

            $table->string('token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
