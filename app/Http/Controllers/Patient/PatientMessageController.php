<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Patient\Concerns\PatientContextHelpers;
use App\Http\Requests\Patient\BookAppointmentRequest;
use App\Http\Requests\Patient\CompleteProfileRequest;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\PatientDailyCalorieGoal;
use App\Models\PatientMeal;
use App\Services\AiMealAnalysisService;
use App\Services\AppNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PatientMessageController extends Controller
{
    use PatientContextHelpers;

    public function messages(): View
    {
        return view('patient.messages', $this->dashboardData([
            'pageTitle' => 'الرسائل',
            'activePage' => 'messages',
        ]));
    }


    public function sendDoctorMessage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$this->tableExists('messages') || !$this->tableExists('conversations')) {
            return back()
                ->withInput()
                ->with('error', 'جداول الرسائل غير جاهزة.');
        }

        $profile = $this->patientProfile($user->id);
        $doctor = $this->selectedDoctor($profile);

        if (empty($doctor['is_selected']) || empty($doctor['user_id'])) {
            return back()
                ->withInput()
                ->with('error', 'لا يوجد طبيب متابعة لإرسال الرسالة.');
        }

        $bodyColumn = $this->firstExistingColumn('messages', ['body', 'message', 'content', 'text']);

        if (!$bodyColumn) {
            return back()
                ->withInput()
                ->with('error', 'لا يوجد عمود مناسب لحفظ نص الرسالة في جدول messages.');
        }

        $conversationId = $this->ensureConversation(
            patientUserId: $user->id,
            doctorUserId: (int) $doctor['user_id'],
            patientProfileId: $profile?->id,
            doctorProfileId: $doctor['id'] ?? null
        );

        if (!$conversationId) {
            return back()
                ->withInput()
                ->with('error', 'لم يتم إنشاء محادثة للطبيب، لذلك لا يمكن حفظ الرسالة.');
        }

        $messageText = trim((string) $validated['message']);

        $payload = [
            'conversation_id' => $conversationId,
            $bodyColumn => $messageText,
        ];

        if ($this->columnExists('messages', 'sender_id')) {
            $payload['sender_id'] = $user->id;
        }

        if ($this->columnExists('messages', 'receiver_id')) {
            $payload['receiver_id'] = (int) $doctor['user_id'];
        }

        if ($this->columnExists('messages', 'from_user_id')) {
            $payload['from_user_id'] = $user->id;
        }

        if ($this->columnExists('messages', 'to_user_id')) {
            $payload['to_user_id'] = (int) $doctor['user_id'];
        }

        if ($this->columnExists('messages', 'user_id')) {
            $payload['user_id'] = $user->id;
        }

        if ($this->columnExists('messages', 'patient_profile_id') && $profile) {
            $payload['patient_profile_id'] = $profile->id;
        }

        if ($this->columnExists('messages', 'doctor_profile_id') && !empty($doctor['id'])) {
            $payload['doctor_profile_id'] = $doctor['id'];
        }

        if ($this->columnExists('messages', 'sender_type')) {
            $payload['sender_type'] = 'patient';
        }

        if ($this->columnExists('messages', 'sender_role')) {
            $payload['sender_role'] = 'patient';
        }

        if ($this->columnExists('messages', 'direction')) {
            $payload['direction'] = 'outgoing';
        }

        if ($this->columnExists('messages', 'type')) {
            $payload['type'] = 'doctor_message';
        }

        if ($this->columnExists('messages', 'metadata')) {
            $payload['metadata'] = json_encode([
                'source' => 'patient_doctor_chat',
                'patient_id' => $user->id,
                'doctor_user_id' => (int) $doctor['user_id'],
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($this->columnExists('messages', 'is_read')) {
            $payload['is_read'] = false;
        }

        if ($this->columnExists('messages', 'read_at')) {
            $payload['read_at'] = null;
        }

        if ($this->columnExists('messages', 'created_at')) {
            $payload['created_at'] = now();
        }

        if ($this->columnExists('messages', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('messages')->insert($payload);

        $conversationPayload = [];

        if ($this->columnExists('conversations', 'last_message_at')) {
            $conversationPayload['last_message_at'] = now();
        }

        if ($this->columnExists('conversations', 'updated_at')) {
            $conversationPayload['updated_at'] = now();
        }

        if ($this->columnExists('conversations', 'unread_by_admin')) {
            $conversationPayload['unread_by_admin'] = DB::raw('unread_by_admin + 1');
        }

        if (!empty($conversationPayload)) {
            DB::table('conversations')
                ->where('id', $conversationId)
                ->update($conversationPayload);
        }

        $this->createAppNotification(
            recipientUserId: (int) $doctor['user_id'],
            actorUserId: $user->id,
            type: 'message_received',
            title: 'رسالة جديدة من المريض',
            body: 'وصلتك رسالة جديدة من المريض داخل صفحة الرسائل.',
            url: url('/doctor/messages'),
            recipientRole: 'doctor'
        );

        return redirect()
            ->route('patient.messages')
            ->with('success', 'تم إرسال رسالتك للطبيب.');
    }


    public function support(): View
    {
        return view('patient.support', $this->dashboardData([
            'pageTitle' => 'الدعم',
            'activePage' => 'support',
        ]));
    }


    public function sendSupportMessage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$this->tableExists('conversations') || !$this->tableExists('messages')) {
            return back()
                ->withInput()
                ->with('error', 'جداول الرسائل غير جاهزة.');
        }

        $conversationId = $this->ensureSupportConversation($user->id);

        if (!$conversationId) {
            return back()
                ->withInput()
                ->with('error', 'لم يتم إنشاء محادثة الدعم.');
        }

        $bodyColumn = $this->firstExistingColumn('messages', ['body', 'message', 'content', 'text']);

        if (!$bodyColumn) {
            return back()
                ->withInput()
                ->with('error', 'لا يوجد عمود مناسب لحفظ نص الرسالة في جدول messages.');
        }

        $messageText = trim((string) $validated['message']);

        $payload = [
            'conversation_id' => $conversationId,
            $bodyColumn => $messageText,
        ];

        if ($this->columnExists('messages', 'user_id')) {
            $payload['user_id'] = $user->id;
        }

        if ($this->columnExists('messages', 'sender_id')) {
            $payload['sender_id'] = $user->id;
        }

        if ($this->columnExists('messages', 'from_user_id')) {
            $payload['from_user_id'] = $user->id;
        }

        if ($this->columnExists('messages', 'sender_type')) {
            $payload['sender_type'] = 'patient';
        }

        if ($this->columnExists('messages', 'sender_role')) {
            $payload['sender_role'] = 'patient';
        }

        if ($this->columnExists('messages', 'direction')) {
            $payload['direction'] = 'incoming';
        }

        if ($this->columnExists('messages', 'type')) {
            $payload['type'] = 'text';
        }

        if ($this->columnExists('messages', 'metadata')) {
            $payload['metadata'] = json_encode([
                'source' => 'patient_support',
                'patient_id' => $user->id,
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($this->columnExists('messages', 'is_read')) {
            $payload['is_read'] = false;
        }

        if ($this->columnExists('messages', 'read_at')) {
            $payload['read_at'] = null;
        }

        if ($this->columnExists('messages', 'created_at')) {
            $payload['created_at'] = now();
        }

        if ($this->columnExists('messages', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('messages')->insert($payload);

        $conversationPayload = [];

        if ($this->columnExists('conversations', 'status')) {
            $statusValue = $this->conversationStatusValue();

            if ($statusValue !== null) {
                $conversationPayload['status'] = $statusValue;
            }
        }

        if ($this->columnExists('conversations', 'last_message_at')) {
            $conversationPayload['last_message_at'] = now();
        }

        if ($this->columnExists('conversations', 'unread_by_admin')) {
            $conversationPayload['unread_by_admin'] = DB::raw('unread_by_admin + 1');
        }

        if ($this->columnExists('conversations', 'updated_at')) {
            $conversationPayload['updated_at'] = now();
        }

        if (!empty($conversationPayload)) {
            DB::table('conversations')
                ->where('id', $conversationId)
                ->update($conversationPayload);
        }

        $this->createAdminNotification(
            type: 'message',
            title: 'رسالة دعم جديدة',
            body: 'وصلت رسالة دعم جديدة من ' . ($user->name ?? 'مريض اتزان') . '.',
            url: route('admin.messages.show', $conversationId),
            relatedId: $conversationId,
            relatedType: 'conversation'
        );

        return redirect()
            ->route('patient.support')
            ->with('success', 'تم إرسال رسالتك للدعم بنجاح.');
    }

}
