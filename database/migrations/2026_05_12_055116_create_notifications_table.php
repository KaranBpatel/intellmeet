<?php
// database/migrations/2026_05_12_055116_create_notifications_table.php
// This migration is superseded by 2026_04_02_182914_create_notifications_table.php
// which already creates the notifications table with the correct schema.
// Kept as a no-op to preserve migration history integrity.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No-op: notifications table is created by 2026_04_02_182914_create_notifications_table.php
    }

    public function down(): void
    {
        // No-op
    }
};
