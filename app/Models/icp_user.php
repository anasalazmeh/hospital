<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class icp_user extends Model
{
    protected $table = 'user_accounts';
    protected $fillable = [
        'user_account_id',
        'intensive_care_patients_id'
    ];
}
