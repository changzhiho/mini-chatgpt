<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Conversation;

return new class extends Migration
{
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id')->index();
        });

        // Générer des UUIDs pour les conversations existantes
        Conversation::whereNull('uuid')->each(function ($conversation) {
            $conversation->update(['uuid' => Str::uuid()]);
        });

        // Rendre la colonne NOT NULL après avoir peuplé les données
        Schema::table('conversations', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
