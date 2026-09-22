<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait AccountDatabase
{
    protected function setUpAccountDatabase(): void
    {
        config(['app.key' => str_repeat('x', 32), 'cache.default' => 'array', 'session.connection' => 'acore_auth']);
        $auth = Schema::connection('acore_auth');
        $auth->create('account', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email');
            $table->string('reg_mail')->default('');
            $table->binary('salt');
            $table->binary('verifier');
            $table->timestamp('joindate')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->string('last_ip')->default('');
            $table->string('last_attempt_ip')->default('');
            $table->integer('expansion')->default(2);
            $table->integer('locale')->default(0);
            $table->integer('online')->default(0);
            $table->integer('mutetime')->default(0);
            $table->string('mutereason')->default('');
        });
        $auth->create('account_access', function (Blueprint $table) {
            $table->integer('id');
            $table->integer('gmlevel');
            $table->integer('RealmID');
        });
        $auth->create('account_banned', function (Blueprint $table) {
            $table->integer('id');
            $table->integer('bandate');
            $table->integer('unbandate');
            $table->integer('active');
            $table->string('banreason');
            $table->string('bannedby');
        });

        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_09_07_160000_create_account_operations_and_sessions.php'))->up();

        $characters = Schema::connection('acore_characters');
        $characters->create('characters', function (Blueprint $table) {
            $table->integer('guid')->primary();
            $table->string('name');
            foreach (['account', 'race', 'class', 'level', 'money', 'totaltime', 'totalHonorPoints', 'online', 'at_login'] as $column) {
                $table->integer($column)->default(0);
            }
        });
        $characters->create('character_homebind', function (Blueprint $table) {
            $table->integer('guid')->primary();
            $table->integer('mapId')->default(0);
            $table->integer('zoneId')->default(12);
            foreach (['posX', 'posY', 'posZ'] as $column) {
                $table->float($column)->default(0);
            }
        });
        $characters->create('guild', function (Blueprint $table) {
            $table->integer('guildid')->primary();
            $table->string('name');
        });
        $characters->create('guild_member', function (Blueprint $table) {
            $table->integer('guid')->primary();
            $table->integer('guildid');
        });

        $this->withoutVite();
    }
}
