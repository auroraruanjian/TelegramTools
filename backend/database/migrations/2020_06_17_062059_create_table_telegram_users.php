<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableTelegramUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('user_id')->comment('对应User对象的id');
            $table->string('username',100)->default('')->comment('对应用户名');
            $table->string('phone',30)->default('')->comment('手机号码');
            $table->string('photo',255)->default('')->comment('头像路径');
            $table->jsonb('extra')->default('{}')->comment('用户详细信息');
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
        Schema::dropIfExists('telegram_users');
    }
}
