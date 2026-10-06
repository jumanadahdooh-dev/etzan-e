<?php

namespace App\Console\Commands;

use App\Models\DoctorApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * ينقل ملفات الترخيص والسيرة الذاتية للطلبات القديمة من القرص public (المكشوف عبر /storage)
 * للقرص الخاص. المسار النسبي ما بيتغير، فما في داعي نعدل قاعدة البيانات.
 */
class MakeDoctorApplicationFilesPrivate extends Command
{
    protected $signature = 'doctor-applications:make-files-private {--dry-run : List what would be moved without moving anything}';

    protected $description = 'Move doctor licence and CV uploads from the public disk to the private disk';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk(DoctorApplication::PRIVATE_DISK);
        $dryRun = (bool) $this->option('dry-run');
        $moved = $alreadyPrivate = $missing = 0;

        DoctorApplication::query()->select(['id', ...DoctorApplication::PRIVATE_FILE_COLUMNS])
            ->chunkById(100, function ($applications) use ($public, $private, $dryRun, &$moved, &$alreadyPrivate, &$missing) {
                foreach ($applications as $application) {
                    foreach (DoctorApplication::PRIVATE_FILE_COLUMNS as $column) {
                        $path = $application->{$column};

                        if (!$path) {
                            continue;
                        }

                        if (!$public->exists($path)) {
                            $private->exists($path) ? $alreadyPrivate++ : $missing++;
                            continue;
                        }

                        $this->line(($dryRun ? '[dry-run] ' : '') . "#{$application->id} {$column}: {$path}");

                        if (!$dryRun) {
                            // ما منحذف النسخة العامة إلا إذا انكتبت الخاصة بنجاح
                            if (!$private->writeStream($path, $public->readStream($path)) || !$private->exists($path)) {
                                $this->error("  failed to copy {$path}; left on public disk");
                                continue;
                            }

                            $public->delete($path);
                        }

                        $moved++;
                    }
                }
            });

        $this->info(($dryRun ? 'Would move' : 'Moved') . " {$moved} file(s); already private: {$alreadyPrivate}; missing on both disks: {$missing}.");

        return self::SUCCESS;
    }
}
