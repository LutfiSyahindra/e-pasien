<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDONESIA_TIMEZONE = 'Asia/Jakarta';

    public function up(): void
    {
        $this->convertSchedules(self::INDONESIA_TIMEZONE, 'UTC');

        if (Schema::hasTable('promotion_configurations')) {
            DB::table('promotion_configurations')->update([
                'auto_delete_enabled' => true,
                'delete_grace_value' => 0,
                'delete_grace_unit' => 'hour',
            ]);
        }
    }

    public function down(): void
    {
        $this->convertSchedules('UTC', self::INDONESIA_TIMEZONE);
    }

    private function convertSchedules(string $sourceTimezone, string $targetTimezone): void
    {
        if (! Schema::hasTable('promotions')) {
            return;
        }

        DB::table('promotions')
            ->select(['id', 'starts_at', 'ends_at'])
            ->orderBy('id')
            ->chunkById(100, function ($promotions) use ($sourceTimezone, $targetTimezone): void {
                foreach ($promotions as $promotion) {
                    DB::table('promotions')
                        ->where('id', $promotion->id)
                        ->update([
                            'starts_at' => CarbonImmutable::parse($promotion->starts_at, $sourceTimezone)
                                ->setTimezone($targetTimezone)
                                ->format('Y-m-d H:i:s'),
                            'ends_at' => CarbonImmutable::parse($promotion->ends_at, $sourceTimezone)
                                ->setTimezone($targetTimezone)
                                ->format('Y-m-d H:i:s'),
                        ]);
                }
            });
    }
};
