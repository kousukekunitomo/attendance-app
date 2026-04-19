<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\Profile;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    use MustVerifyEmailTrait {
        sendEmailVerificationNotification as protected sendEmailVerificationNotificationFromTrait;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'stripe_customer_id',
        'needs_profile_setup',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'password'            => 'hashed',
            'is_admin'            => 'boolean',
            'needs_profile_setup' => 'boolean',
        ];
    }

    /* ================= Relations ================= */

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /** 勤怠データ */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** 自分が出した勤怠修正申請 */
    public function attendanceCorrectionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    /* ================= Helpers ================= */

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * 管理者は常にメール認証済み扱いにする
     */
    public function hasVerifiedEmail(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return ! is_null($this->email_verified_at);
    }

    /* ============== 認証メール送信（重複ガード付き） ============== */

    public function sendEmailVerificationNotification()
    {
        // 管理者には認証メールを送らない
        if ($this->isAdmin()) {
            \Log::info('DBG sendEmailVerificationNotification:skipped-admin', [
                'user_id' => $this->id,
                'email'   => $this->email,
                'time'    => microtime(true),
            ]);
            return;
        }

        $key = 'verification.mail.sent.user_id';
        $container = app();

        if ($container->bound($key) && (int) $container->make($key) === (int) $this->id) {
            \Log::info('DBG sendEmailVerificationNotification:skipped-duplicate', [
                'user_id' => $this->id,
                'email'   => $this->email,
                'time'    => microtime(true),
            ]);
            return;
        }

        $container->instance($key, (int) $this->id);

        \Log::info('DBG sendEmailVerificationNotification:called', [
            'user_id' => $this->id,
            'email'   => $this->email,
            'time'    => microtime(true),
        ]);

        return $this->sendEmailVerificationNotificationFromTrait();
    }
}