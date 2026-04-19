@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
@php
    $statusLabel = match($status) {
        'before_work' => '勤務外',
        'working' => '出勤中',
        'on_break' => '休憩中',
        'after_work' => '退勤済',
        default => '',
    };
@endphp

<div class="stamp-page">
    <div class="stamp-page__inner">
        <div class="stamp-card">

            <div class="stamp-status">
                <span class="stamp-status__badge">{{ $statusLabel }}</span>
            </div>

            <div class="stamp-date">{{ $todayLabel }}({{ $weekdayLabel }})</div>
            <div class="stamp-time">{{ $currentTime }}</div>

            @if($errors->any())
                <div class="stamp-error">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('status'))
                <div class="stamp-message">
                    {{ session('status') }}
                </div>
            @endif

            @if($status === 'after_work')
                <p class="stamp-finished">お疲れ様でした。</p>
            @else
                <form method="POST" action="{{ route('attendance.stamp') }}" class="stamp-form">
                    @csrf
                    <div class="stamp-actions">
                        @if($status === 'before_work')
                            <button type="submit" name="action" value="clock_in" class="stamp-button stamp-button--primary">
                                出勤
                            </button>
                        @elseif($status === 'working')
                            <button type="submit" name="action" value="clock_out" class="stamp-button stamp-button--primary">
                                退勤
                            </button>
                            <button type="submit" name="action" value="break_start" class="stamp-button stamp-button--secondary">
                                休憩入
                            </button>
                        @elseif($status === 'on_break')
                            <button type="submit" name="action" value="break_end" class="stamp-button stamp-button--secondary">
                                休憩戻
                            </button>
                        @endif
                    </div>
                </form>
            @endif

        </div>
    </div>
</div>
@endsection