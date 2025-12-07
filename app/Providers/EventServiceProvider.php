<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\LogRegistered;

class EventServiceProvider extends ServiceProvider
{
    /**
     * 明示リスナーのみ（ここに定義したものだけを使う）
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            LogRegistered::class,
        ],
    ];

    /**
     * 親クラスと同じ「型なし static」プロパティで自動検出を無効化
     */
    protected static $shouldDiscoverEvents = false;

    /**
     * 親シグネチャに合わせて public で上書き（保険）
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    public function boot(): void
    {
        // 追加登録・forget は行わない
    }
}
