<?php

namespace App\Services\LandingPage;

use App\Models\LandingPageSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class LandingPageContentService
{
    public function defaults(): array
    {
        $content = [];

        foreach (config('landing-page.sections') as $key => $section) {
            $content[$key] = array_map(fn (array $field) => $field['default'], $section['fields']);
        }

        foreach (config('landing-page.collections') as $key => $collection) {
            $content[$key] = $collection['default'];
        }

        return $content;
    }

    public function current(): array
    {
        $setting = null;

        // Halaman publik tetap tersedia selama migrasi belum terpasang.
        try {
            if (Schema::hasTable('landing_page_settings')) {
                $setting = LandingPageSetting::query()->where('key', LandingPageSetting::DEFAULT_KEY)->first();
            }
        } catch (QueryException $exception) {
            Log::warning('Gagal memuat informasi landing page.', ['message' => $exception->getMessage()]);
        }

        $content = $this->defaults();
        foreach ($setting?->content ?? [] as $key => $value) {
            if (array_key_exists($key, config('landing-page.collections'))) {
                $content[$key] = $value;
            } elseif (isset($content[$key])) {
                $content[$key] = array_replace($content[$key], $value);
            }
        }

        $images = [];
        foreach (config('landing-page.images') as $key => $image) {
            $path = $setting?->images[$key] ?? null;
            $images[$key] = $path ? Storage::disk('public')->url($path) : asset($image['default']);
        }

        $contacts = [
            ['label' => $content['contact']['call_label'], 'number' => $content['contact']['call_number'], 'icon' => 'fa-headset', 'tone' => 'primary'],
            ['label' => $content['contact']['emergency_label'], 'number' => $content['contact']['emergency_number'], 'icon' => 'fa-truck-medical', 'tone' => 'emergency'],
            ...$content['additional_contacts'],
        ];
        $contacts = array_map(fn (array $contact) => [...$contact, 'href' => $this->telephoneUrl($contact['number'])], $contacts);

        return [
            'landingContent' => $content,
            'landingImages' => $images,
            'hospitalServices' => $content['services'],
            'otherServices' => $content['other_services'],
            'specialistClinics' => $content['clinics'],
            'hospitalContacts' => $contacts,
            'hospitalLocation' => $content['location'],
            'landingUpdatedAt' => $setting?->updated_at,
        ];
    }

    public function update(array $content, array $uploads, array $resets, ?int $userId): void
    {
        $stored = [];
        $replaced = [];

        try {
            foreach ($uploads as $key => $file) {
                $path = $file->store('landing-page', 'public');
                if (! $path) {
                    throw new RuntimeException('Gambar landing page tidak dapat disimpan.');
                }
                $stored[$key] = $path;
            }

            DB::transaction(function () use ($content, $resets, $stored, $userId, &$replaced): void {
                $setting = LandingPageSetting::query()->firstOrCreate(
                    ['key' => LandingPageSetting::DEFAULT_KEY],
                    ['content' => $this->defaults(), 'images' => []]
                );
                $setting = LandingPageSetting::query()->whereKey($setting->id)->lockForUpdate()->firstOrFail();
                $images = $setting->images ?? [];

                foreach (config('landing-page.images') as $key => $image) {
                    if (isset($stored[$key]) || ($resets[$key] ?? false)) {
                        if (isset($images[$key])) {
                            $replaced[] = $images[$key];
                        }
                        $images[$key] = $stored[$key] ?? null;
                    }
                }

                $setting->update(['content' => $content, 'images' => $images, 'updated_by' => $userId]);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete(array_values($stored));
            throw $exception;
        }

        // Hapus hanya file milik modul ini setelah perubahan database berhasil.
        try {
            Storage::disk('public')->delete(array_values(array_filter($replaced, fn (string $path): bool => str_starts_with($path, 'landing-page/'))));
        } catch (Throwable $exception) {
            Log::warning('Gagal membersihkan gambar landing page lama.', ['message' => $exception->getMessage()]);
        }
    }

    private function telephoneUrl(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number);
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return 'tel:+'.$digits;
    }
}
