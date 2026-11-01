<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->date('borrowed_at');
            $table->date('due_at');
            $table->date('returned_at')->nullable();
            // Late fee in whole rupiah. Kept as integer to avoid float drift.
            $table->unsignedInteger('fine')->default(0);
            $table->timestamps();

            // A book copy set can only be lent as many times as its stock;
            // this index keeps the "active loans per book" lookup cheap.
            $table->index(['book_id', 'returned_at']);
            $table->index(['member_id', 'returned_at']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
