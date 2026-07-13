<?php

namespace App\Console\Commands;

use App\Models\PatientTask;
use App\Services\AppNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPatientTaskNotifications extends Command
{
    protected $signature = 'patient-tasks:notify';

    protected $description = 'Send patient task reminder, due, and late notifications.';

    private int $lateAfterMinutes = 15;

    public function handle(): int
    {
        $now = now();

        $tasks = PatientTask::query()
            ->whereNotIn('status', ['completed', 'cancelled', 'canceled'])
            ->whereNotNull('patient_user_id')
            ->whereNotNull('task_date')
            ->whereNotNull('task_time')
            ->whereDate('task_date', '>=', $now->copy()->subDay()->toDateString())
            ->whereDate('task_date', '<=', $now->copy()->addDay()->toDateString())
            ->orderBy('task_date')
            ->orderBy('task_time')
            ->get();

        $reminders = 0;
        $due = 0;
        $late = 0;

        foreach ($tasks as $task) {
            $taskDateTime = $this->taskDateTime($task);

            if (! $taskDateTime) {
                continue;
            }

            if ($this->shouldSendReminder($task, $taskDateTime, $now)) {
                $this->sendTaskNotification(
                    task: $task,
                    type: 'task_reminder',
                    title: 'تذكير بمهمة قريبة',
                    body: 'باقي ' . (int) $task->reminder_minutes . ' دقائق على مهمة: ' . $task->title
                );

                $task->forceFill([
                    'reminder_sent_at' => $now,
                ])->save();

                $reminders++;
            }

            if ($this->shouldSendDue($task, $taskDateTime, $now)) {
                $this->sendTaskNotification(
                    task: $task,
                    type: 'task_due',
                    title: 'حان وقت المهمة',
                    body: 'حان وقت مهمة: ' . $task->title
                );

                $task->forceFill([
                    'due_notification_sent_at' => $now,
                ])->save();

                $due++;
            }

            if ($this->shouldSendLate($task, $taskDateTime, $now)) {
                $this->sendTaskNotification(
                    task: $task,
                    type: 'task_late',
                    title: 'مهمة متأخرة',
                    body: 'تأخرت عن مهمة: ' . $task->title
                );

                /*
                 | مهم جدًا:
                 | لا نغير status إلى late أو missed لأن قاعدة البيانات عندك لا تقبل هذه القيم.
                 | نستخدم late_notification_sent_at كعلامة أن المهمة أصبحت متأخرة وتم إرسال إشعار التأخير.
                 */
                $task->forceFill([
                    'late_notification_sent_at' => $now,
                ])->save();

                $late++;
            }
        }

        $this->info("Patient task notifications sent. reminder={$reminders}, due={$due}, late={$late}");

        return self::SUCCESS;
    }

    private function taskDateTime(PatientTask $task): ?Carbon
    {
        try {
            $date = $task->task_date instanceof Carbon
                ? $task->task_date->toDateString()
                : Carbon::parse($task->task_date)->toDateString();

            return Carbon::parse($date . ' ' . $task->task_time);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function shouldSendReminder(PatientTask $task, Carbon $taskDateTime, Carbon $now): bool
    {
        $reminderMinutes = (int) ($task->reminder_minutes ?? 0);

        if ($reminderMinutes <= 0) {
            return false;
        }

        if ($task->reminder_sent_at) {
            return false;
        }

        if ($task->late_notification_sent_at) {
            return false;
        }

        $reminderTime = $taskDateTime->copy()->subMinutes($reminderMinutes);

        return $now->greaterThanOrEqualTo($reminderTime)
            && $now->lessThan($taskDateTime);
    }

    private function shouldSendDue(PatientTask $task, Carbon $taskDateTime, Carbon $now): bool
    {
        if ($task->due_notification_sent_at) {
            return false;
        }

        if ($task->late_notification_sent_at) {
            return false;
        }

        return $now->greaterThanOrEqualTo($taskDateTime)
            && $now->lessThan($taskDateTime->copy()->addMinutes($this->lateAfterMinutes));
    }

    private function shouldSendLate(PatientTask $task, Carbon $taskDateTime, Carbon $now): bool
    {
        if ($task->late_notification_sent_at) {
            return false;
        }

        return $now->greaterThanOrEqualTo(
            $taskDateTime->copy()->addMinutes($this->lateAfterMinutes)
        );
    }

    private function sendTaskNotification(PatientTask $task, string $type, string $title, string $body): void
    {
        app(AppNotificationService::class)->send(
            recipientUserId: (int) $task->patient_user_id,
            recipientRole: 'patient',
            type: $type,
            title: $title,
            body: $body,
            url: route('patient.journey'),
            actorUserId: $task->created_by_id ? (int) $task->created_by_id : null,
            relatedId: (int) $task->id,
            relatedType: 'patient_task',
            data: [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'task_date' => optional($task->task_date)->toDateString(),
                'task_time' => $task->task_time,
                'source' => $task->source,
                'late_after_minutes' => $this->lateAfterMinutes,
            ]
        );
    }
}