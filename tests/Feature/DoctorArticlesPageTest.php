<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\DoctorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorArticlesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_articles_page_shows_the_doctors_own_articles_and_counts(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();

        Article::factory()->create(['user_id' => $doctorProfile->user_id, 'status' => 'published', 'title' => 'مقال منشور تجريبي']);
        Article::factory()->draft()->create(['user_id' => $doctorProfile->user_id, 'title' => 'مسودة تجريبية']);

        $response = $this->actingAs($doctorProfile->user)->get(route('doctor.articles'));

        $response->assertOk();
        $response->assertSee('مقال منشور تجريبي');
        $response->assertSee('مسودة تجريبية');
    }

    public function test_doctor_can_save_a_new_article_as_draft(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();

        $response = $this->actingAs($doctorProfile->user)->post(route('doctor.articles.store'), [
            'title' => 'مقال جديد عن التغذية',
            'excerpt' => 'ملخص قصير عن المقال',
            'content' => str_repeat('محتوى تجريبي طويل بما فيه الكفاية لتخطي حد الخمسين حرف. ', 3),
            'reading_minutes' => 4,
            'action' => 'draft',
        ]);

        $response->assertRedirect(route('doctor.articles'));

        $this->assertDatabaseHas('articles', [
            'user_id' => $doctorProfile->user_id,
            'title' => 'مقال جديد عن التغذية',
            'status' => 'draft',
        ]);
    }

    public function test_doctor_can_submit_an_article_for_review(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();

        $this->actingAs($doctorProfile->user)->post(route('doctor.articles.store'), [
            'title' => 'مقال بينتظر المراجعة',
            'excerpt' => 'ملخص قصير',
            'content' => str_repeat('محتوى تجريبي طويل بما فيه الكفاية لتخطي حد الخمسين حرف. ', 3),
            'reading_minutes' => 5,
            'action' => 'submit',
        ]);

        $this->assertDatabaseHas('articles', [
            'title' => 'مقال بينتظر المراجعة',
            'status' => 'pending_review',
        ]);
    }

    public function test_doctor_cannot_edit_another_doctors_article(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $otherDoctorProfile = DoctorProfile::factory()->create();

        $article = Article::factory()->draft()->create(['user_id' => $otherDoctorProfile->user_id]);

        $response = $this->actingAs($doctorProfile->user)->put(route('doctor.articles.update', $article), [
            'title' => 'محاولة تعديل',
            'excerpt' => 'محاولة',
            'content' => str_repeat('محاولة تعديل مقال دكتور تاني. ', 3),
            'reading_minutes' => 3,
            'action' => 'save',
        ]);

        $response->assertForbidden();
    }

    public function test_doctor_can_delete_own_draft_article(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();
        $article = Article::factory()->draft()->create(['user_id' => $doctorProfile->user_id]);

        $response = $this->actingAs($doctorProfile->user)->delete(route('doctor.articles.destroy', $article));

        $response->assertRedirect(route('doctor.articles'));
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    public function test_doctor_can_request_an_ai_draft_and_gets_prefilled_content(): void
    {
        $doctorProfile = DoctorProfile::factory()->create();

        $response = $this->actingAs($doctorProfile->user)->post(route('doctor.articles.ai-draft'), [
            'topic' => 'أهمية شرب الماء لمرضى السكري',
        ]);

        $response->assertRedirect(route('doctor.articles'));
        $response->assertSessionHas('open_new_article_form', true);
        $response->assertSessionHasInput('title');
        $response->assertSessionHasInput('content');
    }
}
