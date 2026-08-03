<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminArticleReviewNotifiesDoctorTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_a_doctors_article_notifies_the_doctor_and_publishes_it(): void
    {
        $admin = User::factory()->admin()->create();
        $doctorProfile = DoctorProfile::factory()->create();

        $article = Article::factory()->create([
            'user_id' => $doctorProfile->user_id,
            'status' => 'pending_review',
            'title' => 'مقال بانتظار الموافقة',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.articles.approve', $article));

        $response->assertRedirect();

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'status' => 'published']);

        $this->assertDatabaseHas('app_notifications', [
            'recipient_user_id' => $doctorProfile->user_id,
            'type' => 'article_approved',
        ]);
    }

    public function test_rejecting_a_doctors_article_notifies_the_doctor_with_the_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $doctorProfile = DoctorProfile::factory()->create();

        $article = Article::factory()->create([
            'user_id' => $doctorProfile->user_id,
            'status' => 'pending_review',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.articles.reject', $article), [
            'rejection_reason' => 'المصدر غير موثوق كفاية.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('articles', ['id' => $article->id, 'status' => 'rejected']);

        $this->assertDatabaseHas('app_notifications', [
            'recipient_user_id' => $doctorProfile->user_id,
            'type' => 'article_rejected',
        ]);
    }
}
