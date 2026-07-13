<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (! Schema::hasColumn('articles', 'audience')) {
                $table->string('audience', 40)->default('patient')->after('source');
            }

            if (! Schema::hasColumn('articles', 'article_type')) {
                $table->string('article_type', 50)->default('health_article')->after('audience');
            }

            if (! Schema::hasColumn('articles', 'generated_by_ai')) {
                $table->boolean('generated_by_ai')->default(false)->after('is_featured');
            }

            if (! Schema::hasColumn('articles', 'reviewed_by_admin')) {
                $table->boolean('reviewed_by_admin')->default(false)->after('generated_by_ai');
            }

            if (! Schema::hasColumn('articles', 'source_title')) {
                $table->string('source_title')->nullable()->after('reviewed_by_admin');
            }

            if (! Schema::hasColumn('articles', 'source_url')) {
                $table->string('source_url', 500)->nullable()->after('source_title');
            }

            if (! Schema::hasColumn('articles', 'source_year')) {
                $table->unsignedSmallInteger('source_year')->nullable()->after('source_url');
            }

            if (! Schema::hasColumn('articles', 'medical_disclaimer')) {
                $table->text('medical_disclaimer')->nullable()->after('source_year');
            }

            if (! Schema::hasColumn('articles', 'ai_prompt')) {
                $table->text('ai_prompt')->nullable()->after('medical_disclaimer');
            }

            if (! Schema::hasColumn('articles', 'ai_generation_metadata')) {
                $table->json('ai_generation_metadata')->nullable()->after('ai_prompt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            foreach ([
                'ai_generation_metadata',
                'ai_prompt',
                'medical_disclaimer',
                'source_year',
                'source_url',
                'source_title',
                'reviewed_by_admin',
                'generated_by_ai',
                'article_type',
                'audience',
            ] as $column) {
                if (Schema::hasColumn('articles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
