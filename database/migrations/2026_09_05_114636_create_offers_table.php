<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedTinyInteger('max_guests');
            $table->unsignedInteger('price');
            $table->char('currency', 3);
            $table->unsignedInteger('available_units');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['supplier_id', 'external_id']);
            $table->index(['property_id', 'price']);
            $table->index(['check_in', 'check_out']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
