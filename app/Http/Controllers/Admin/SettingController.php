<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizRecommendationRule;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate(array_merge([
            'site_name' => ['nullable', 'string', 'max:255'],
            'site_description' => ['nullable', 'string'],

            'site_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'site_favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,ico', 'max:1024'],

            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_whatsapp' => ['nullable', 'string', 'max:50'],
            'contact_address' => ['nullable', 'string', 'max:255'],

            /*
            |--------------------------------------------------------------------------
            | روابط التواصل
            |--------------------------------------------------------------------------
            | خليتها string بدل url حتى لو الأدمن كتب الرابط بدون https
            | بنضيف https تلقائيًا قبل الحفظ.
            */
            'facebook_url' => ['nullable', 'string', 'max:255'],
            'instagram_url' => ['nullable', 'string', 'max:255'],
            'x_url' => ['nullable', 'string', 'max:255'],
            'youtube_url' => ['nullable', 'string', 'max:255'],

            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'seo_keywords' => ['nullable', 'string'],

            'theme_mode' => ['nullable', 'in:light,dark'],
            'enable_animations' => ['nullable', 'boolean'],

            'maintenance_mode' => ['nullable', 'boolean'],
            'maintenance_message' => ['nullable', 'string'],
        ], $this->homeValidationRules()));

        $siteTextSettings = [
            'site_name',
            'site_description',

            'contact_email',
            'contact_phone',
            'contact_whatsapp',
            'contact_address',

            'facebook_url',
            'instagram_url',
            'x_url',
            'youtube_url',

            'seo_title',
            'seo_description',
            'seo_keywords',

            'theme_mode',
            'maintenance_message',
        ];

        foreach ($siteTextSettings as $key) {
            $value = $request->input($key);

            if (in_array($key, $this->socialUrlSettings(), true)) {
                $value = $this->normalizeUrl($value);
            }

            if ($key === 'contact_whatsapp') {
                $value = $this->normalizeWhatsappNumber($value);
            }

            $this->saveSetting($key, $value);
        }

        $this->saveSetting('theme_mode', $request->input('theme_mode', 'light'));

        $this->saveSetting(
            'enable_animations',
            $request->boolean('enable_animations') ? '1' : '0',
            'boolean'
        );

        $this->saveSetting(
            'maintenance_mode',
            $request->boolean('maintenance_mode') ? '1' : '0',
            'boolean'
        );

        if ($request->hasFile('site_logo')) {
            $this->deleteOldFile('site_logo');

            $path = $request->file('site_logo')->store('settings', 'public');

            $this->saveSetting('site_logo', $path, 'image');
        }

        if ($request->hasFile('site_favicon')) {
            $this->deleteOldFile('site_favicon');

            $path = $request->file('site_favicon')->store('settings', 'public');

            $this->saveSetting('site_favicon', $path, 'image');
        }

        foreach ($this->homeTextSettings() as $key) {
            if ($request->has($key)) {
                $this->saveSetting($key, $request->input($key));
            }
        }

        foreach ($this->homeBooleanSettings() as $key) {
            $this->saveSetting(
                $key,
                $request->boolean($key) ? '1' : '0',
                'boolean'
            );
        }

        foreach ($this->homeImageSettings() as $imageKey) {
            if ($request->hasFile($imageKey)) {
                $this->deleteOldFile($imageKey);

                $path = $request->file($imageKey)->store('home', 'public');

                $this->saveSetting($imageKey, $path, 'image');
            }
        }

        $this->syncQuizRules($request);

        return back()->with('success', 'تم حفظ إعدادات الموقع بنجاح.');
    }

    private function socialUrlSettings(): array
    {
        return [
            'facebook_url',
            'instagram_url',
            'x_url',
            'youtube_url',
        ];
    }

    private function normalizeUrl($url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $url = trim($url);

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return 'https://' . $url;
    }

    private function normalizeWhatsappNumber($number): ?string
    {
        if (blank($number)) {
            return null;
        }

        $number = trim($number);

        return preg_replace('/[^0-9]/', '', $number);
    }

    private function syncQuizRules(Request $request): void
    {
        $rules = $request->input('quiz_rules', []);

        foreach ($rules as $rule) {
            $id = $rule['id'] ?? null;

            if (!empty($rule['delete'])) {
                if ($id) {
                    QuizRecommendationRule::where('id', $id)->delete();
                }

                continue;
            }

            $contentType = $rule['content_type'] ?? 'recommendation';
            $text = $rule['text'] ?? null;
            $articleId = $rule['article_id'] ?? null;

            if ($contentType === 'article' && blank($articleId)) {
                continue;
            }

            if ($contentType !== 'article' && blank($text)) {
                continue;
            }

            $data = [
                'content_type' => $contentType,
                'result_type' => !empty($rule['result_type']) ? $rule['result_type'] : null,
                'goal' => !empty($rule['goal']) ? $rule['goal'] : null,
                'condition' => !empty($rule['condition']) ? $rule['condition'] : null,
                'activity' => !empty($rule['activity']) ? $rule['activity'] : null,
                'symptoms' => !empty($rule['symptoms']) ? $rule['symptoms'] : null,
                'medication' => !empty($rule['medication']) ? $rule['medication'] : null,
                'article_id' => $contentType === 'article' ? $articleId : null,
                'text' => $contentType === 'article' ? null : $text,
                'priority' => (int) ($rule['priority'] ?? 1),
                'is_active' => !empty($rule['is_active']),
            ];

            if ($id) {
                QuizRecommendationRule::where('id', $id)->update($data);
            } else {
                QuizRecommendationRule::create($data);
            }
        }
    }

    private function homeValidationRules(): array
    {
        $rules = [];

        foreach ($this->homeBooleanSettings() as $key) {
            $rules[$key] = ['nullable', 'boolean'];
        }

        foreach ($this->homeImageSettings() as $key) {
            $rules[$key] = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'];
        }

        foreach ($this->homeTextSettings() as $key) {
            $rules[$key] = ['nullable', 'string'];
        }

        return $rules;
    }

    private function homeBooleanSettings(): array
    {
        return [
            'home_hero_enabled',
            'home_how_enabled',
            'home_features_enabled',
            'home_services_enabled',
            'home_doctors_enabled',
            'home_articles_enabled',
            'home_quiz_enabled',
            'home_faq_enabled',
            'home_cta_enabled',
            'home_doctors_only_active',
            'home_doctor_join_enabled',
            'home_articles_only_published',
            'home_articles_featured_enabled',
        ];
    }

    private function homeImageSettings(): array
    {
        return [
            'home_hero_slide_1',
            'home_hero_slide_2',
            'home_hero_slide_3',
            'home_how_image',
            'home_features_image',
            'home_quiz_image',
            'home_doctor_1_image',
            'home_doctor_2_image',
            'home_doctor_3_image',
            'home_doctor_4_image',
            'home_article_1_image',
            'home_article_2_image',
            'home_article_3_image',
            'home_article_4_image',
        ];
    }

    private function homeTextSettings(): array
    {
        return [
            'home_hero_badge',
            'home_hero_title',
            'home_hero_highlight',
            'home_hero_description',
            'home_hero_primary_btn_text',
            'home_hero_primary_btn_url',
            'home_hero_secondary_btn_text',
            'home_hero_secondary_btn_url',
            'home_hero_card_1',
            'home_hero_card_2',
            'home_hero_card_3',

            'home_how_badge',
            'home_how_title',
            'home_how_highlight',
            'home_how_description',
            'home_how_step_1_title',
            'home_how_step_1_description',
            'home_how_step_2_title',
            'home_how_step_2_description',
            'home_how_step_3_title',
            'home_how_step_3_description',
            'home_how_step_4_title',
            'home_how_step_4_description',

            'home_features_badge',
            'home_features_title',
            'home_features_highlight',
            'home_features_description',
            'home_feature_mini_label',
            'home_feature_title',
            'home_feature_description',
            'home_feature_note',
            'home_feature_chip_1',
            'home_feature_chip_2',
            'home_feature_chip_3',
            'home_feature_bubble',

            'home_services_badge',
            'home_services_title',
            'home_services_highlight',
            'home_services_description',
            'home_service_1_badge',
            'home_service_1_title',
            'home_service_1_description',
            'home_service_2_badge',
            'home_service_2_title',
            'home_service_2_description',
            'home_service_3_badge',
            'home_service_3_title',
            'home_service_3_description',
            'home_service_4_badge',
            'home_service_4_title',
            'home_service_4_description',
            'home_service_5_badge',
            'home_service_5_title',
            'home_service_5_description',
            'home_service_6_badge',
            'home_service_6_title',
            'home_service_6_description',

            'home_doctors_badge',
            'home_doctors_title',
            'home_doctors_highlight',
            'home_doctors_description',
            'home_doctors_limit',
            'home_doctors_order',
            'home_doctor_join_title',
            'home_doctor_join_description',
            'home_doctor_join_btn_text',
            'home_doctor_join_btn_url',

            'home_articles_badge',
            'home_articles_title',
            'home_articles_highlight',
            'home_articles_description',
            'home_articles_limit',
            'home_articles_order',
            'home_articles_category',
            'home_articles_btn_text',
            'home_articles_btn_url',

            'home_quiz_badge',
            'home_quiz_title',
            'home_quiz_highlight',
            'home_quiz_description',
            'home_quiz_bubble_text',
            'home_quiz_result_badge',
            'home_quiz_result_title',
            'home_quiz_result_description',
            'home_quiz_result_tag_1',
            'home_quiz_result_tag_2',
            'home_quiz_result_tag_3',
            'home_quiz_primary_btn_text',
            'home_quiz_primary_btn_url',
            'home_quiz_secondary_btn_text',
            'home_quiz_secondary_btn_url',

            'home_faq_badge',
            'home_faq_title',
            'home_faq_highlight',
            'home_faq_description',
            'home_faq_trust_title',
            'home_faq_trust_description',

            'home_faq_1_badge',
            'home_faq_1_question',
            'home_faq_1_short',
            'home_faq_1_answer',
            'home_faq_1_point_1',
            'home_faq_1_point_2',
            'home_faq_1_point_3',

            'home_faq_2_badge',
            'home_faq_2_question',
            'home_faq_2_short',
            'home_faq_2_answer',
            'home_faq_2_point_1',
            'home_faq_2_point_2',
            'home_faq_2_point_3',

            'home_faq_3_badge',
            'home_faq_3_question',
            'home_faq_3_short',
            'home_faq_3_answer',
            'home_faq_3_point_1',
            'home_faq_3_point_2',
            'home_faq_3_point_3',

            'home_faq_4_badge',
            'home_faq_4_question',
            'home_faq_4_short',
            'home_faq_4_answer',
            'home_faq_4_point_1',
            'home_faq_4_point_2',
            'home_faq_4_point_3',

            'home_faq_5_badge',
            'home_faq_5_question',
            'home_faq_5_short',
            'home_faq_5_answer',
            'home_faq_5_point_1',
            'home_faq_5_point_2',
            'home_faq_5_point_3',

            'home_cta_badge',
            'home_cta_title',
            'home_cta_highlight',
            'home_cta_description',
            'home_cta_point_1',
            'home_cta_point_2',
            'home_cta_point_3',
            'home_cta_primary_btn_text',
            'home_cta_primary_btn_url',
            'home_cta_secondary_btn_text',
            'home_cta_secondary_btn_url',
            'home_cta_card_title',
            'home_cta_card_description',
            'home_cta_float_1',
            'home_cta_float_2',
            'home_cta_float_3',
        ];
    }

    private function saveSetting(string $key, mixed $value, string $type = 'text'): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
            ]
        );
    }

    private function deleteOldFile(string $key): void
    {
        $oldFile = Setting::where('key', $key)->value('value');

        if ($oldFile && Storage::disk('public')->exists($oldFile)) {
            Storage::disk('public')->delete($oldFile);
        }
    }
}
