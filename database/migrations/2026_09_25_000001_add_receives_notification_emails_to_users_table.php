<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Whether this user receives Fleet Desk notification emails
            // (Help requests and maintenance alerts). Defaults to false so
            // existing users are not subscribed without an admin opting
            // them in from User Management.
            $table->boolean('receives_notification_emails')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('receives_notification_emails');
        });
    }
};
