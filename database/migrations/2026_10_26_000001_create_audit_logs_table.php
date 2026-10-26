<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ecosystem "Auditoría" screen: who did what, on which record and when.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor')->default('');
            $table->string('action', 60)->index();
            $table->string('subject_type', 60)->default('');
            $table->string('subject_id', 60)->default('');
            $table->json('details')->nullable();
            $table->string('ip', 45)->default('');
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
