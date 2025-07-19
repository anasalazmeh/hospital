<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentRequest extends Model
{
    protected $fillable = [
        'department_id',
        'status',
        'notes',
        'requested_by',
        'processed_by',
        'status_updated_at',
        'is_issued',
        'issued_at',
        'issued_by'
    ];

    protected $casts = [
        'is_issued' => 'boolean',
        'issued_at' => 'datetime',
        'status_updated_at' => 'datetime'
    ];
public function departmentRequestItems()
{
    return $this->hasMany(DepartmentRequestItem::class);
}
    // علاقة مع العناصر المطلوبة
    public function items(): HasMany
    {
        return $this->hasMany(DepartmentRequestItem::class);
    }

    // علاقة مع القسم
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // علاقة مع مقدم الطلب
    public function requester(): BelongsTo
    {
        return $this->belongsTo(DashboardAccounts::class, 'requested_by');
    }

    // علاقة مع المسؤول الذي قام بالمعالجة (موافقة/رفض)
    public function processor(): BelongsTo
    {
        return $this->belongsTo(DashboardAccounts::class, 'processed_by');
    }

    // علاقة مع المسؤول الذي قام بالإصدار من المستودع
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(DashboardAccounts::class, 'issued_by');
    }

    // دالة لتحديث حالة الطلب
    public function updateStatus(string $status, int $processorId)
    {
        $this->update([
            'status' => $status,
            'processed_by' => $processorId,
            'status_updated_at' => now()
        ]);
    }

    // دالة لتسجيل إصدار المواد من المستودع
    public function markAsIssued(int $issuerId)
    {
        $this->update([
            'is_issued' => true,
            'issued_at' => now(),
            'issued_by' => $issuerId 
        ]);
    }

    // دالة للتحقق مما إذا كان الطلب جاهز للإصدار
    public function isReadyForIssue(): bool
    {
        return $this->status === 'approved' || $this->status === 'partially_fulfilled';
    }
}