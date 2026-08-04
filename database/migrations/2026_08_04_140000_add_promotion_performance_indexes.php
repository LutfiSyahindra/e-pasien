<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table): void {
            $table->index(
                ['category', 'status', 'starts_at', 'ends_at'],
                'promotions_category_status_schedule_idx'
            );
            $table->index(
                ['status', 'notified_at', 'starts_at', 'ends_at'],
                'promotions_notification_schedule_idx'
            );
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(
                ['notifiable_type', 'notifiable_id', 'created_at'],
                'notifications_notifiable_created_idx'
            );
            $table->index(
                ['notifiable_type', 'notifiable_id', 'read_at'],
                'notifications_notifiable_unread_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_notifiable_created_idx');
            $table->dropIndex('notifications_notifiable_unread_idx');
        });

        Schema::table('promotions', function (Blueprint $table): void {
            $table->dropIndex('promotions_category_status_schedule_idx');
            $table->dropIndex('promotions_notification_schedule_idx');
        });
    }
};
