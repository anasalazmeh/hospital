<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupController extends Controller
{
    // إنشاء نسخة احتياطية كاملة
    public function createBackup()
    {
        try {
            $date = now()->format('Y-m-d_H-i-s');
            $backupName = "backup_{$date}";
            
            // 1. نسخ قاعدة البيانات
            $dbProcess = new Process([
                'mysqldump',
                '-u', config('database.connections.mysql.username'),
                '-p' . config('database.connections.mysql.password'),
                config('database.connections.mysql.database'),
                '--result-file=' . storage_path("app/backups/{$backupName}.sql")
            ]);
            $dbProcess->run();
            // 2. نسخ الملفات
            $filesProcess = new Process([
                'tar',
                '-czf',
                storage_path("app/backups/{$backupName}_files.tar.gz"),
                '-C',
                base_path(),
                'storage/app/public',
                '.env'
            ]);
            $filesProcess->run();
            // 3. رفع إلى التخزين السحابي (اختياري)
            Storage::disk('s3')->put(
                "backups/{$backupName}.sql",
                file_get_contents(storage_path("app/backups/{$backupName}.sql"))
            );
            
            Storage::disk('s3')->put(
                "backups/{$backupName}_files.tar.gz",
                file_get_contents(storage_path("app/backups/{$backupName}_files.tar.gz"))
            );
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء النسخة الاحتياطية بنجاح',
                'backup_name' => $backupName
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في إنشاء النسخة الاحتياطية',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // جدولة النسخ الاحتياطي التلقائي
    public function scheduleBackup(Request $request)
    {
        $validated = $request->validate([
            'frequency' => 'required|in:daily,weekly,monthly',
            'time' => 'required|date_format:H:i'
        ]);
        
        // هنا يمكنك إضافة الجدولة إلى قاعدة البيانات أو نظام الجدولة
        // مثال بسيط:
        file_put_contents(
            storage_path('app/backup_schedule.json'),
            json_encode($validated)
        );
        
        return response()->json([
            'success' => true,
            'message' => 'تم جدولة النسخ الاحتياطي بنجاح'
        ]);
    }
}
