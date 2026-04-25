<?php

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
        Schema::create('pengeluaran_kas', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tanggal_pengeluaran');
            $table->integer('jumlah_pengeluaran');
            $table->text('keterangan')->nullable();
            $table->string('bukti_foto')->nullable(); // Path
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_kas');
    }
};
