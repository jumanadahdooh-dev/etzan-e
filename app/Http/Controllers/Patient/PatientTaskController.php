<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\PatientTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PatientTaskController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = now();

        $tasks = PatientTask::query()
            ->where('patient_user_id', $user->id)
            ->whereDate('task_date', $today->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderByRaw('task_time IS NULL')
            ->orderBy('task_time')
            ->orderBy('created_at')
            ->get();

        $calendarStart = $today->copy()->startOfMonth()->subDays(7);
        $calendarEnd = $today->copy()->endOfMonth()->addDays(60);

        $calendarTasksSource = PatientTask::query()
            ->where('patient_user_id', $user->id)
            ->whereBetween('task_date', [
                $calendarStart->toDateString(),
                $calendarEnd->toDateString(),
            ])
            ->where('status', '!=', 'cancelled')
            ->orderBy('task_date')
            ->orderByRaw('task_time IS NULL')
            ->orderBy('task_time')
            ->orderBy('created_at')
            ->get();

        $isLateTask = function ($task): bool {
            if ($task->status === 'completed') {
                return false;
            }

            if (in_array($task->status, ['late', 'missed'], true)) {
                return true;
            }

            return ! is_null($task->late_notification_sent_at);
        };

        $lateTasksCount = $tasks->filter($isLateTask)->count();

        $stats = [
            'total' => $tasks->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'pending' => $tasks->filter(fn ($task) => $task->status === 'pending' && ! $isLateTask($task))->count(),
            'missed' => $lateTasksCount,
            'doctor' => $tasks->where('source', 'doctor')->count(),
            'patient' => $tasks->whereIn('source', ['patient', 'self'])->count(),
        ];

        $stats['progress'] = $stats['total'] > 0
            ? round(($stats['completed'] / $stats['total']) * 100)
            : 0;

        $nextTask = $tasks
            ->where('status', 'pending')
            ->filter(fn ($task) => ! $isLateTask($task))
            ->sortBy('task_time')
            ->first();

        $doctorNote = PatientTask::query()
            ->where('patient_user_id', $user->id)
            ->where(function ($query) {
                $query->where('source', 'doctor')
                    ->orWhere('created_by_type', 'doctor');
            })
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->latest('updated_at')
            ->value('description');

        return view('patient.journey', [
            'activePage' => 'journey',
            'tasks' => $tasks,
            'calendarTasksSource' => $calendarTasksSource,
            'stats' => $stats,
            'nextTask' => $nextTask,
            'doctorNote' => $doctorNote,
            'todayDate' => $today,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'task_date' => ['required', 'date'],
            'task_time' => ['nullable', 'date_format:H:i'],
            'reminder_minutes' => ['nullable', 'integer', 'in:0,10,15,30'],
            'repeat_type' => ['nullable', 'string', 'in:once,daily,weekly'],
        ], [
            'title.required' => 'اكتب عنوان المهمة.',
            'title.min' => 'عنوان المهمة يجب أن يكون 3 أحرف على الأقل.',
            'task_date.required' => 'اختر تاريخ المهمة.',
            'task_time.date_format' => 'صيغة الوقت غير صحيحة.',
            'reminder_minutes.in' => 'قيمة التذكير غير صحيحة.',
            'repeat_type.in' => 'نوع التكرار غير صحيح.',
        ]);

        $patientProfile = DB::table('patient_profiles')
            ->where('user_id', $user->id)
            ->first();

        if (! $patientProfile) {
            return redirect()
                ->route('patient.profile')
                ->with('error', 'أكمل ملفك الصحي أولًا قبل إضافة المهام.');
        }

        PatientTask::create([
            'patient_id' => $patientProfile->id,
            'patient_user_id' => $user->id,
            'doctor_user_id' => null,
            'created_by_id' => $user->id,
            'created_by_type' => 'patient',
            'source' => 'patient',

            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'task_date' => $validated['task_date'],
            'task_time' => $validated['task_time'] ?? null,

            'status' => 'pending',
            'repeat_type' => $validated['repeat_type'] ?? 'once',

            'reminder_minutes' => (int) ($validated['reminder_minutes'] ?? 0),
            'reminder_sent_at' => null,
            'due_notification_sent_at' => null,
            'late_notification_sent_at' => null,

            'requires_attachment' => false,
            'attachment_path' => null,
            'patient_note' => null,
            'completed_at' => null,
        ]);

        return redirect()
            ->route('patient.journey')
            ->with('success', 'تمت إضافة المهمة بنجاح.');
    }

    public function complete(PatientTask $task): RedirectResponse
    {
        $this->authorizePatientTask($task);

        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('patient.journey')
            ->with('success', 'أحسنت! تم إنجاز المهمة.');
    }

    public function updateNote(Request $request, PatientTask $task): RedirectResponse
    {
        $this->authorizePatientTask($task);

        $validated = $request->validate([
            'patient_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'patient_note.max' => 'الملاحظة يجب ألا تتجاوز 1000 حرف.',
        ]);

        $note = trim((string) ($validated['patient_note'] ?? ''));

        $task->forceFill([
            'patient_note' => $note !== '' ? $note : null,
        ])->save();

        return redirect()
            ->route('patient.journey')
            ->with('success', 'تم حفظ الملاحظة بنجاح.');
    }

    /*
    |--------------------------------------------------------------------------
    | رفع صورة نتيجة مهمة الطبيب فقط
    |--------------------------------------------------------------------------
    | لا نقبل ملفات.
    | لا نقبل PDF.
    | لا نقبل Word.
    | فقط صور: jpg, jpeg, png, webp.
    | ولا يظهر هذا الخيار أصلًا إلا في مهمة الطبيب من البليد.
    */
    public function uploadAttachment(Request $request, PatientTask $task): RedirectResponse
    {
        $this->authorizePatientTask($task);

        if (! $task->is_doctor_task) {
            return redirect()
                ->route('patient.journey')
                ->with('error', 'رفع صورة النتيجة متاح فقط لمهام الطبيب.');
        }

        $validated = $request->validate([
            'attachment' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'attachment.required' => 'اختر صورة النتيجة أولًا.',
            'attachment.image' => 'الملف يجب أن يكون صورة فقط.',
            'attachment.mimes' => 'الصورة يجب أن تكون بصيغة jpg أو jpeg أو png أو webp.',
            'attachment.max' => 'حجم الصورة يجب ألا يتجاوز 4MB.',
        ]);

        if ($task->attachment_path && Storage::disk('public')->exists($task->attachment_path)) {
            Storage::disk('public')->delete($task->attachment_path);
        }

        $path = $validated['attachment']->store('patient-task-results', 'public');

        $task->forceFill([
            'attachment_path' => $path,
            'requires_attachment' => true,
        ])->save();

        return redirect()
            ->route('patient.journey')
            ->with('success', 'تم رفع صورة النتيجة بنجاح.');
    }

    /*
    |--------------------------------------------------------------------------
    | عرض صورة النتيجة
    |--------------------------------------------------------------------------
    */
    public function showAttachment(PatientTask $task)
    {
        $this->authorizePatientTask($task);

        if (! $task->attachment_path) {
            abort(404, 'لا توجد صورة نتيجة لهذه المهمة.');
        }

        if (! Storage::disk('public')->exists($task->attachment_path)) {
            abort(404, 'صورة النتيجة غير موجودة.');
        }

        return Storage::disk('public')->response($task->attachment_path);
    }

    public function destroy(PatientTask $task): RedirectResponse
    {
        $this->authorizePatientTask($task);

        if ($task->is_doctor_task) {
            return redirect()
                ->route('patient.journey')
                ->with('error', 'لا يمكنك حذف مهمة أرسلها الطبيب.');
        }

        if ($task->attachment_path && Storage::disk('public')->exists($task->attachment_path)) {
            Storage::disk('public')->delete($task->attachment_path);
        }

        $task->delete();

        return redirect()
            ->route('patient.journey')
            ->with('success', 'تم حذف المهمة.');
    }

    private function authorizePatientTask(PatientTask $task): void
    {
        abort_unless((int) $task->patient_user_id === (int) auth()->id(), 403);
    }
}