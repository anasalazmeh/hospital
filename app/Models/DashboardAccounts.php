<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardAccounts extends Model
{
    use HasFactory;
    protected $table = 'dashboard_accounts';
    protected $fillable = [
        'full_name', 'email', 'phone_number', 'password', 'role'
    ];

}
