<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_runs_without_errors(): void
    {
        // بمحاكاة "لا" على سؤال التأكيد، فقط المستخدم التجريبي الأساسي بينعمل.
        $this->artisan('db:seed', ['--force' => true])
            ->expectsConfirmation(
                'إنشاء بيانات تجريبية إضافية (أطباء، مرضى، مواعيد، مقالات)؟',
                'no'
            )
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_database_seeder_creates_linked_demo_data_when_confirmed(): void
    {
        $this->artisan('db:seed', ['--force' => true])
            ->expectsConfirmation(
                'إنشاء بيانات تجريبية إضافية (أطباء، مرضى، مواعيد، مقالات)؟',
                'yes'
            )
            ->assertExitCode(0);

        $this->assertSame(5, DB::table('doctor_profiles')->count());
        $this->assertSame(15, DB::table('patient_profiles')->count());
        $this->assertSame(15, DB::table('patient_appointments')->count());
        $this->assertSame(15, DB::table('patient_tasks')->count());
        $this->assertSame(15, DB::table('ai_chat_conversations')->count());
        $this->assertSame(10, DB::table('articles')->count());
    }
}
