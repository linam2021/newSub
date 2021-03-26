<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChallengesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('hero_instagram')->unique();
            $table->string('hero_target');
            $table->integer('points')->default(0);
            $table->timestamp('lastAddedDayDate')->nullable();
            $table->integer('capsules')->default(0);
            $table->timestamp('lastAddedCapsulesDate')->nullable();
            $table->boolean('in_leader_board')->default(false);
            $table->string('is_challengVerified')->default(false);
            $table->integer('priority')->default(0);
            $table->integer('challengeDaysCount')->default(0);
            $table->double('average')->default(0);
            $table->timestamps();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('challenges');
    }
}
