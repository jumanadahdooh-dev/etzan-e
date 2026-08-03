<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Concerns\ConversationHelpers;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\DoctorProfile;
use App\Models\Message;
use App\Services\AppNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * صفحة "الرسائل" الحقيقية لجهة الدكتور — قبل هيك كانت كل المحادثات
 * والأسماء مكتوبة مباشرة بالـ view (سارة أحمد، خالد عمر...).
 * تستخدم نفس جداول conversations/messages المستخدمة أصلاً من جهة المريض
 * (PatientMessageController::sendDoctorMessage)، ومربوطة بنفس القيمة
 * الوحيدة الموجودة فعلياً للربط: عمود subject = 'رسائل الطبيب'.
 */
class DoctorMessageController extends Controller
{
    use ConversationHelpers;

    public function index(): View
    {
        $doctorProfile = DoctorProfile::where('user_id', auth()->id())->first();

        if (!$doctorProfile) {
            return view('doctor.messages', [
                'pageTitle' => 'الرسائل',
                'activePage' => 'messages',
                'conversations' => collect(),
            ]);
        }

        return view('doctor.messages', [
            'pageTitle' => 'الرسائل',
            'activePage' => 'messages',
            'conversations' => $this->conversationsListFor($doctorProfile),
        ]);
    }

    public function show(Conversation $conversation): View
    {
        $doctorProfile = DoctorProfile::where('user_id', auth()->id())->first();

        abort_unless($doctorProfile, 404);

        $patient = $doctorProfile->patientProfiles()
            ->where('doctor_request_status', 'approved')
            ->where('user_id', $conversation->user_id)
            ->with('user:id,name')
            ->first();

        abort_unless($patient, 403);

        $messages = $conversation->messages()->get();

        Message::where('conversation_id', $conversation->id)
            ->where('sender_type', 'patient')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('doctor.messages', [
            'pageTitle' => 'الرسائل',
            'activePage' => 'messages',
            'conversations' => $this->conversationsListFor($doctorProfile),
            'openConversation' => $conversation,
            'openMessages' => $messages,
            'openPatientName' => $patient->user?->name ?? 'مريض',
            'openPatientProfileId' => $patient->id,
        ]);
    }

    public function send(Request $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $doctorProfile = DoctorProfile::where('user_id', auth()->id())->first();

        abort_unless($doctorProfile, 404);

        $isMyPatient = $doctorProfile->patientProfiles()
            ->where('doctor_request_status', 'approved')
            ->where('user_id', $conversation->user_id)
            ->exists();

        abort_unless($isMyPatient, 403);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'sender_type' => 'doctor',
            'body' => trim((string) $validated['message']),
            'is_read' => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        app(AppNotificationService::class)->send(
            recipientUserId: $conversation->user_id,
            recipientRole: 'patient',
            type: 'message_received',
            title: 'رسالة جديدة من طبيبك',
            body: 'وصلتك رسالة جديدة من طبيبك، افتح صفحة الرسائل للرد.',
            url: route('patient.messages'),
            actorUserId: auth()->id()
        );

        return redirect()
            ->route('doctor.messages.show', $conversation)
            ->with('success', 'تم إرسال ردك للمريض.');
    }

    private function conversationsListFor(DoctorProfile $doctorProfile)
    {
        $patients = $doctorProfile->patientProfiles()
            ->where('doctor_request_status', 'approved')
            ->with('user:id,name')
            ->get()
            ->keyBy('user_id');

        if ($patients->isEmpty() || !$this->tableExists('conversations')) {
            return collect();
        }

        $query = Conversation::query()->whereIn('user_id', $patients->keys());

        return $this->scopeDoctorConversations($query)
            ->with('latestMessage')
            ->get()
            ->map(function (Conversation $conversation) use ($patients) {
                $patient = $patients->get($conversation->user_id);

                $conversation->patient_name = $patient?->user?->name ?? 'مريض';
                $conversation->unread_count = $this->tableExists('messages')
                    ? Message::where('conversation_id', $conversation->id)
                        ->where('sender_type', 'patient')
                        ->where('is_read', false)
                        ->count()
                    : 0;

                return $conversation;
            })
            ->sortByDesc(fn (Conversation $c) => $c->last_message_at ?? $c->updated_at)
            ->values();
    }
}
