<?php

namespace App\Observers;

use App\Models\Bed;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BedObserver
{
    /**
     * Handle the Bed "updated" event.
     */
    public function updated(Bed $bed)
    {
        $this->handleRoomStatusUpdate($bed);
    }

    /**
     * Handle the Bed "created" event.
     */
    public function created(Bed $bed)
    {
        $this->handleRoomStatusUpdate($bed);
    }

    /**
     * Handle the Bed "deleted" event (optional).
     */
    public function deleted(Bed $bed)
    {
        $this->handleRoomStatusUpdate($bed);
    }

    /**
     * Update room status based on beds occupancy.
     */
protected function handleRoomStatusUpdate(Bed $bed)
{
    try {
        DB::beginTransaction();

        // إعادة تحميل البيانات مع العلاقات (مهم لتجنب بيانات قديمة)
        $freshBed = $bed->fresh(['room.beds']);

        if (!$freshBed->room) {
            DB::rollBack();
            return;
        }

        $room = $freshBed->room;
        
        // استعلام أكثر كفاءة باستخدام withCount
        $occupiedBeds = $room->beds()
            ->where('status', 'occupied')
            ->where('is_active', true)
            ->count();
            
        $totalActiveBeds = $room->beds()
            ->where('is_active', true)
            ->count();

        // تحديد الحالة الجديدة
        $newStatus = ($totalActiveBeds > 0 && $occupiedBeds === $totalActiveBeds) 
            ? 'occupied' 
            : 'available';

        // تحديث حالة الغرفة فقط إذا تغيرت
        if ($room->status !== $newStatus) {
            $room->update(['status' => $newStatus]);
        }
        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Failed to update room status', [
            'bed_id' => $bed->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString() // إضافة trace للتحليل
        ]);
        
        // يمكنك إضافة إعادة رمي الاستثناء إذا أردت
        // throw $e;
    }
}
}