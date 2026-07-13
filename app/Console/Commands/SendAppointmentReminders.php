<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Send appointment reminders before 60 minutes, 10 minutes, and at appointment time.';

    public function handle(): int
    {
        if (!Schema::hasTable('patient_appointments')) {
            $this->warn('Table patient_appointments does not exist.');
            return self::SUCCESS;
        }

        if (!Schema::hasTable('app_notifications')) {
            $this->warn('Table app_notifications does not exist.');
            return self::SUCCESS;
        }

        $requiredColumns = [
            'reminder_60_sent_at',
            'reminder_10_sent_at',
            'reminder_now_sent_at',
        ];

        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('patient_appointments', $column)) {
                $this->warn("Column {$column} is missing. Run the reminder migration first.");
                return self::SUCCESS;
            }
        }

        $now = now();

        $appointments = DB::table('patient_appointments')
            ->whereIn('status', ['confirmed', 'approved'])
            ->whereDate('appointment_date', '>=', $now->toDateString())
            ->whereDate('appointment_date', '<=', $now->copy()->addDay()->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        $sentCount = 0;

        foreach ($appointments as $appointment) {
            if (empty($appointment->user_id)) {
                continue;
            }

            if (empty($appointment->appointment_date) || empty($appointment->appointment_time)) {
                continue;
            }

            try {
                $appointmentAt = Carbon::parse(
                    $appointment->appointment_date . ' ' . $appointment->appointment_time
                );
            } catch (\Throwable $e) {
                continue;
            }

            $minutesUntil = (int) $now->diffInMinutes($appointmentAt, false);
            $minutesAfter = (int) $appointmentAt->diffInMinutes($now, false);

            $doctorUserId = $this->doctorUserId($appointment->doctor_profile_id ?? null);

            /*
            |--------------------------------------------------------------------------
            | قبل الموعد بساعة
            |--------------------------------------------------------------------------
            */
            if (
                empty($appointment->reminder_60_sent_at)
                && $minutesUntil <= 60
                && $minutesUntil > 10
            ) {
                $this->createNotification(
                    recipientUserId: (int) $appointment->user_id,
                    actorUserId: $doctorUserId,
                    type: 'appointment_reminder_60',
                    title: 'موعدك بعد ساعة',
                    body: 'تبقّى حوالي ساعة على موعدك مع الطبيب. يمكنك مراجعة تفاصيل الموعد من صفحة المواعيد.',
                    url: '/patient/followup'
                );

                DB::table('patient_appointments')
                    ->where('id', $appointment->id)
                    ->update([
                        'reminder_60_sent_at' => now(),
                        'updated_at' => now(),
                    ]);

                $sentCount++;
            }

            /*
            |--------------------------------------------------------------------------
            | قبل الموعد بـ 10 دقائق
            |--------------------------------------------------------------------------
            */
            if (
                empty($appointment->reminder_10_sent_at)
                && $minutesUntil <= 10
                && $minutesUntil > 0
            ) {
                $this->createNotification(
                    recipientUserId: (int) $appointment->user_id,
                    actorUserId: $doctorUserId,
                    type: 'appointment_reminder_10',
                    title: 'موعدك بعد قليل',
                    body: 'تبقّت دقائق قليلة على موعدك مع الطبيب. استعد للدخول إلى الاستشارة في الوقت المحدد.',
                    url: '/patient/followup'
                );

                DB::table('patient_appointments')
                    ->where('id', $appointment->id)
                    ->update([
                        'reminder_10_sent_at' => now(),
                        'updated_at' => now(),
                    ]);

                $sentCount++;
            }

            /*
            |--------------------------------------------------------------------------
            | وقت الموعد الآن
            |--------------------------------------------------------------------------
            | نرسل التنبيه عند وقت الموعد أو خلال أول 5 دقائق بعده،
            | حتى لو الـ scheduler اشتغل متأخر دقيقة أو دقيقتين.
            */
            if (
                empty($appointment->reminder_now_sent_at)
                && $minutesUntil <= 0
                && $minutesAfter <= 5
            ) {
                $this->createNotification(
                    recipientUserId: (int) $appointment->user_id,
                    actorUserId: $doctorUserId,
                    type: 'appointment_starting_now',
                    title: 'موعدك الآن',
                    body: 'حان وقت موعدك مع الطبيب. افتح صفحة الموعد واستعد للاستشارة.',
                    url: '/patient/followup'
                );

                DB::table('patient_appointments')
                    ->where('id', $appointment->id)
                    ->update([
                        'reminder_now_sent_at' => now(),
                        'updated_at' => now(),
                    ]);

                $sentCount++;
            }
        }

        $this->info("Appointment reminders sent: {$sentCount}");

        return self::SUCCESS;
    }

    private function doctorUserId(?int $doctorProfileId): ?int
    {
        if (!$doctorProfileId) {
            return null;
        }

        if (!Schema::hasTable('doctor_profiles')) {
            return null;
        }

        if (!Schema::hasColumn('doctor_profiles', 'user_id')) {
            return null;
        }

        $doctorUserId = DB::table('doctor_profiles')
            ->where('id', $doctorProfileId)
            ->value('user_id');

        return $doctorUserId ? (int) $doctorUserId : null;
    }

    private function createNotification(
        int $recipientUserId,
        ?int $actorUserId,
        string $type,
        string $title,
        string $body,
        string $url
    ): void {
        $payload = [
            'recipient_user_id' => $recipientUserId,
            'actor_user_id' => $actorUserId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ];

        if (Schema::hasColumn('app_notifications', 'created_at')) {
            $payload['created_at'] = now();
        }

        if (Schema::hasColumn('app_notifications', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('app_notifications')->insert($payload);
    }
}
