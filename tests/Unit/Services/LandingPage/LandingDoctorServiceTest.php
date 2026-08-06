<?php

namespace Tests\Unit\Services\LandingPage;

use App\Models\DoctorPhoto;
use App\Repositories\epasien\settings\DoctorDirectoryRepository;
use App\Repositories\epasien\settings\DoctorPhotoRepository;
use App\Services\LandingPage\LandingDoctorService;
use Tests\TestCase;

class LandingDoctorServiceTest extends TestCase
{
    public function test_only_active_doctors_with_uploaded_photos_are_featured(): void
    {
        $directory = $this->createMock(DoctorDirectoryRepository::class);
        $photos = $this->createMock(DoctorPhotoRepository::class);
        $photos
            ->expects($this->once())
            ->method('featured')
            ->with(12)
            ->willReturn(collect([
                new DoctorPhoto([
                    'doctor_code' => 'D001',
                    'image_path' => 'doctor-photos/d001.webp',
                ]),
                new DoctorPhoto([
                    'doctor_code' => 'D404',
                    'image_path' => 'doctor-photos/d404.webp',
                ]),
            ]));
        $directory
            ->expects($this->once())
            ->method('activeByCodes')
            ->with(['D001', 'D404'])
            ->willReturn(collect([
                (object) [
                    'kd_dokter' => 'D001',
                    'nm_dokter' => 'dr. Achmad Yunus, Sp.A',
                    'jk' => 'L',
                ],
            ]));

        $featured = (new LandingDoctorService($directory, $photos))->featured();

        $this->assertCount(1, $featured);
        $this->assertSame('D001', $featured[0]['doctor_code']);
        $this->assertSame('dr. Achmad Yunus, Sp.A', $featured[0]['doctor_name']);
        $this->assertSame('/storage/doctor-photos/d001.webp', $featured[0]['photo_url']);
    }

    public function test_empty_photo_configuration_skips_hospital_database_query(): void
    {
        $directory = $this->createMock(DoctorDirectoryRepository::class);
        $photos = $this->createMock(DoctorPhotoRepository::class);
        $photos->expects($this->once())->method('featured')->willReturn(collect());
        $directory->expects($this->never())->method('activeByCodes');

        $this->assertSame([], (new LandingDoctorService($directory, $photos))->featured());
    }
}
