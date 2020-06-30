<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableTelegramAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telegram_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment('用户 ID');
            $table->string('phone',20)->default('')->comment('手机号码');
            $table->string('madeline_file',200)->default('')->comment('Madeline文件地址');
            $table->string('api_id')->nullable()->comment('api_id');
            $table->string('api_hash')->nullable()->comment('api_hash');
            $table->jsonb('extra')->default('{}')->comment('存储基本用户信息');
            $table->timestamps();

            $table->unique(['user_id','phone']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('telegram_accounts');
    }
}
