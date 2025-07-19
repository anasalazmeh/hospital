<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * الحقول التي يمكن تعبئتها بشكل جماعي (Mass Assignment)
     *
     * @var array
     */
    protected $fillable = [
        'sender_account_id',
        'receiver_account_id',
        'patient_id',
        'title',
        'link',
        'priority',
        'message',
        'is_read',
        'read_at',
    ];

    /**
     * الحقول التي يجب إخفاؤها عند التحويل إلى JSON
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * الحقول التي يجب تحويلها إلى أنواع بيانات محددة
     *
     * @var array
     */
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * العلاقة مع جدول حسابات المستخدمين (المرسل)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sender()
    {
        return $this->belongsTo(DashboardAccounts::class, 'sender_account_id');
    }

    /**
     * العلاقة مع جدول حسابات المستخدمين (المستقبل)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function receiver()
    {
        return $this->belongsTo(DashboardAccounts::class, 'receiver_account_id');
    }

    /**
     * العلاقة مع جدول المرضى (إذا كان الإشعار مرتبطًا بمريض)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function patient()
    {
        return $this->belongsTo(Patients::class);
    }

    /**
     * نطاق الاستعلام للبحث عن الإشعارات غير المقروءة
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * نطاق الاستعلام للبحث عن الإشعارات المرسلة من مستخدم معين
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $senderId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSentBy($query, $senderId)
    {
        return $query->where('sender_account_id', $senderId);
    }

    /**
     * نطاق الاستعلام للبحث عن الإشعارات المرسلة إلى مستخدم معين
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $receiverId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReceivedBy($query, $receiverId)
    {
        return $query->where('receiver_account_id', $receiverId);
    }

    /**
     * وضع علامة على الإشعار كمقروء
     *
     * @return bool
     */
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->forceFill([
                'is_read' => true,
                'read_at' => $this->freshTimestamp()
            ])->save();

            return true;
        }

        return false;
    }

    /**
     * وضع علامة على الإشعار كغير مقروء
     *
     * @return bool
     */
    public function markAsUnread()
    {
        if (!is_null($this->read_at)) {
            $this->forceFill([
                'is_read' => false,
                'read_at' => null
            ])->save();

            return true;
        }

        return false;
    }
        // النطاقات (Scopes)

}