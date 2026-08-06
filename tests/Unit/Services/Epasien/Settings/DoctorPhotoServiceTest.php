<?php

namespace Tests\Unit\Services\Epasien\Settings;

use App\Models\DoctorPhoto;
use App\Repositories\epasien\settings\DoctorDirectoryRepository;
use App\Repositories\epasien\settings\DoctorPhotoRepository;
use App\Services\epasien\settings\DoctorPhotoService;
use App\Services\epasien\settings\DoctorPhotoStorageService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DoctorPhotoServiceTest extends TestCase
{
    public function test_cropped_data_url_is_stored_as_an_optimized_public_image(): void
    {
        Storage::fake('public');
        $storage = new DoctorPhotoStorageService;
        $onePixelPng = 'data:image/png;base64,'
            .'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6e4sAAAAASUVORK5CYII=';

        $path = $storage->storeDataUrl($onePixelPng, 'D001');

        $this->assertNotNull($path);
        $this->assertStringStartsWith('doctor-photos/d001-', $path);
        Storage::disk('public')->assertExists($path);

        $storage->delete($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_page_combines_active_doctors_with_configured_photos(): void
    {
        $directory = $this->createMock(DoctorDirectoryRepository::class);
        $photos = $this->createMock(DoctorPhotoRepository::class);
        $storage = $this->createMock(DoctorPhotoStorageService::class);
        $directory
            ->expects($this->once())
            ->method('paginate')
            ->with('yunus', 12)
            ->willReturn(new LengthAwarePaginator([
                (object) [
                    'kd_dokter' => 'D001',
                    'nm_dokter' => 'dr. Achmad Yunus, Sp.A',
                    'jk' => 'L',
                ],
                (object) [
                    'kd_dokter' => 'D002',
                    'nm_dokter' => 'dr. Siti Aminah, Sp.PD',
                    'jk' => 'P',
                ],
            ], 2, 12));
        $photos
            ->expects($this->once())
            ->method('urlsForCodes')
            ->with(['D001', 'D002'])
            ->willReturn(['D001' => '/storage/doctor-photos/d001.webp']);

        $page = (new DoctorPhotoService($directory, $photos, $storage))->page(' yunus ');
        $items = $page['doctors']->items();

        $this->assertSame('yunus', $page['search']);
        $this->assertSame('AY', $items[0]['doctor_initials']);
        $this->assertSame('/storage/doctor-photos/d001.webp', $items[0]['photo_url']);
        $this->assertNull($items[1]['photo_url']);
        $this->assertSame('Perempuan', $items[1]['gender']);
        $this->assertSame(1, $page['configuredPhotos']);
    }

    public function test_cropped_photo_replaces_old_file_after_database_save(): void
    {
        $directory = $this->createMock(DoctorDirectoryRepository::class);
        $photos = $this->createMock(DoctorPhotoRepository::class);
        $storage = $this->createMock(DoctorPhotoStorageService::class);
        $oldPhoto = new DoctorPhoto([
            'doctor_code' => 'D001',
            'image_path' => 'doctor-photos/old.webp',
        ]);
        $savedPhoto = new DoctorPhoto([
            'doctor_code' => 'D001',
            'image_path' => 'doctor-photos/new.webp',
            'uploaded_by' => 7,
        ]);
        $directory
            ->expects($this->once())
            ->method('findActive')
            ->with('D001')
            ->willReturn((object) ['kd_dokter' => 'D001']);
        $storage
            ->expects($this->once())
            ->method('storeDataUrl')
            ->with('data:image/jpeg;base64,valid', 'D001')
            ->willReturn('doctor-photos/new.webp');
        $photos
            ->expects($this->once())
            ->method('find')
            ->with('D001')
            ->willReturn($oldPhoto);
        $photos
            ->expects($this->once())
            ->method('save')
            ->with('D001', 'doctor-photos/new.webp', 7)
            ->willReturn($savedPhoto);
        $storage
            ->expects($this->once())
            ->method('delete')
            ->with('doctor-photos/old.webp');

        $result = (new DoctorPhotoService($directory, $photos, $storage))->update(
            ' D001 ',
            null,
            'data:image/jpeg;base64,valid',
            7
        );

        $this->assertSame('doctor-photos/new.webp', $result->image_path);
    }

    public function test_photo_is_rejected_when_doctor_is_not_active(): void
    {
        $directory = $this->createMock(DoctorDirectoryRepository::class);
        $photos = $this->createMock(DoctorPhotoRepository::class);
        $storage = $this->createMock(DoctorPhotoStorageService::class);
        $directory
            ->expects($this->once())
            ->method('findActive')
            ->with('D404')
            ->willReturn(null);
        $storage->expects($this->never())->method('storeDataUrl');
        $photos->expects($this->never())->method('save');

        $this->expectException(ValidationException::class);

        (new DoctorPhotoService($directory, $photos, $storage))->update(
            'D404',
            null,
            'data:image/jpeg;base64,valid',
            7
        );
    }
}
