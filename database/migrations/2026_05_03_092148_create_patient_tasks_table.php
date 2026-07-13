
  <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up(): void
    {
        Schema::create('patient_tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('source', ['doctor', 'patient'])->default('patient');
            $table->enum('type', [
                'water',
                'walking',
                'weight',
                'article',
                'meal',
                'message',
                'custom'
            ])->default('custom');

            $table->enum('status', ['pending', 'completed', 'skipped'])->default('pending');

            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->string('action_url')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_tasks');
    }
};
