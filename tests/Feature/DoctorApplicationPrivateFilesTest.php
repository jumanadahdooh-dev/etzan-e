<?php

namespace Tests\Feature;

use App\Models\DoctorApplication;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ملفات الترخيص والسيرة الذاتية لازم تنحفظ على القرص الخاص، ما توصلها روابط /storage
 * المباشرة، وتضل تنفتح للأدمن (بما فيها الطلبات القديمة اللي لسا على public).
 */
class DoctorApplicationPrivateFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake(DoctorApplication::PRIVATE_DISK);
    }

    private function submitApplication(): DoctorApplication
    {
        Specialty::create(['name' => 'تغذية علاجية', 'slug' => 'clinical-nutrition', 'is_active' => true]);

        $this->post(route('join-doctor.store'), [
            'full_name' => 'د. اختبار',
            'email' => 'new-doctor@example.com',
            'phone' => '0790000000',
            'workplace' => 'عيادة',
            'specialty' => 'تغذية علاجية',
            'experience_years' => 5,
            'license_number' => 'LIC-123',
            'bio' => 'نبذة',
            'profile_photo' => UploadedFile::fake()->create('me.jpg', 20, 'image/jpeg'),
            'license_file' => UploadedFile::fake()->create('license.pdf', 50, 'application/pdf'),
            'cv_file' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf'),
            'agreement' => '1',
        ])->assertRedirect(route('join-doctor'));

        return DoctorApplication::where('email', 'new-doctor@example.com')->firstOrFail();
    }

    public function test_licence_and_cv_are_stored_privately_and_photo_stays_public(): void
    {
        $application = $this->submitApplication();

        foreach ([$application->license_file_path, $application->cv_file_path] as $path) {
            Storage::disk(DoctorApplication::PRIVATE_DISK)->assertExists($path);
            Storage::disk('public')->assertMissing($path);
        }

        Storage::disk('public')->assertExists($application->profile_photo_path);
    }

    public function test_licence_is_not_reachable_by_direct_storage_url_but_opens_for_admin(): void
    {
        $application = $this->submitApplication();
        $licence = $application->license_file_path;

        // الرابط العام القديم ما بيرجع الملف
        $direct = $this->get('/storage/' . $licence);
        $this->assertContains($direct->status(), [403, 404]);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.doctor-applications-file-preview', [$application, 'license']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($admin)
            ->get(route('admin.doctor-applications-file-download', [$application, 'cv']))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($admin)
            ->get(route('admin.doctor-applications-file-viewer', [$application, 'license']))
            ->assertOk()
            ->assertSee('data:application/pdf;base64,', false);
    }

    public function test_non_admins_cannot_open_the_licence(): void
    {
        $application = $this->submitApplication();
        $url = route('admin.doctor-applications-file-preview', [$application, 'license']);

        $this->get($url)->assertRedirect();

        foreach (['doctor', 'patient'] as $role) {
            $response = $this->actingAs(User::factory()->{$role}()->create())->get($url);
            $this->assertNotEquals(200, $response->status(), "{$role} should not be able to open the licence");
        }
    }

    public function test_legacy_public_files_still_open_and_command_moves_them_private(): void
    {
        Storage::disk('public')->put('doctor-applications/license-files/old.pdf', '%PDF-legacy');
        Storage::disk('public')->put('doctor-applications/cv-files/old.pdf', '%PDF-legacy-cv');

        $application = DoctorApplication::create([
            'full_name' => 'د. قديم',
            'email' => 'legacy@example.com',
            'phone' => '0790000001',
            'workplace' => 'عيادة',
            'specialty' => 'تغذية علاجية',
            'experience_years' => 3,
            'license_number' => 'LIC-OLD',
            'bio' => 'نبذة',
            'profile_photo_path' => 'doctor-applications/profile-photos/old.jpg',
            'license_file_path' => 'doctor-applications/license-files/old.pdf',
            'cv_file_path' => 'doctor-applications/cv-files/old.pdf',
            'status' => 'pending',
        ]);

        $admin = User::factory()->admin()->create();
        $preview = route('admin.doctor-applications-file-preview', [$application, 'license']);

        // قبل النقل: الاحتياط على public شغال
        $this->actingAs($admin)->get($preview)->assertOk();

        $this->artisan('doctor-applications:make-files-private', ['--dry-run' => true])
            ->expectsOutputToContain('Would move 2 file(s)')
            ->assertSuccessful();
        Storage::disk('public')->assertExists('doctor-applications/license-files/old.pdf');

        $this->artisan('doctor-applications:make-files-private')
            ->expectsOutputToContain('Moved 2 file(s)')
            ->assertSuccessful();

        Storage::disk('public')->assertMissing('doctor-applications/license-files/old.pdf');
        Storage::disk('public')->assertMissing('doctor-applications/cv-files/old.pdf');
        $this->assertSame('%PDF-legacy', Storage::disk(DoctorApplication::PRIVATE_DISK)->get('doctor-applications/license-files/old.pdf'));

        // بعد النقل: نفس المسار بقاعدة البيانات وبيضل ينفتح للأدمن
        $this->actingAs($admin)->get($preview)->assertOk();

        // تشغيله مرة ثانية ما بيعمل شي
        $this->artisan('doctor-applications:make-files-private')
            ->expectsOutputToContain('Moved 0 file(s); already private: 2')
            ->assertSuccessful();
    }
}
