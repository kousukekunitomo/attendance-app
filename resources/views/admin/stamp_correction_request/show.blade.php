@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-request-show.css') }}">
@endsection

@section('content')
@php
    $date = $requestItem->work_date
        ? \Carbon\Carbon::parse($requestItem->work_date)
        : null;

    $breaks = $requestItem->breaks ?? collect();

    $fmt = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i') : '';
@endphp

<div class="request-detail-page">
    <div class="request-detail-container">
        <div class="request-detail-title">
            <h1>申請詳細</h1>
        </div>

        <div class="request-detail-card">
            <table class="request-detail-table">
                <tr>
                    <th>名前</th>
                    <td colspan="2">{{ $requestItem->user->name ?? '' }}</td>
                </tr>

                <tr>
                    <th>日付</th>
                    <td colspan="2">
                        <div class="date-row">
                            <div class="date-col">{{ $date ? $date->format('Y年') : '' }}</div>
                            <div class="date-col">{{ $date ? $date->format('n月j日') : '' }}</div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th>出勤・退勤</th>
                    <td colspan="2">
                        <div class="time-row">
                            <div class="time-col">
                                {{ $fmt($requestItem->after_clock_in) }}
                            </div>
                            <div class="time-sep">〜</div>
                            <div class="time-col">
                                {{ $fmt($requestItem->after_clock_out) }}
                            </div>
                        </div>
                    </td>
                </tr>

                @if($breaks->isNotEmpty())
                    @foreach($breaks as $i => $break)
                        <tr>
                            <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
                            <td colspan="2">
                                <div class="time-row">
                                    <div class="time-col">
                                        {{ $fmt($break->after_rest_start) }}
                                    </div>
                                    <div class="time-sep">〜</div>
                                    <div class="time-col">
                                        {{ $fmt($break->after_rest_end) }}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @elseif($requestItem->after_rest_start || $requestItem->after_rest_end)
                    <tr>
                        <th>休憩</th>
                        <td colspan="2">
                            <div class="time-row">
                                <div class="time-col">
                                    {{ $fmt($requestItem->after_rest_start) }}
                                </div>
                                <div class="time-sep">〜</div>
                                <div class="time-col">
                                    {{ $fmt($requestItem->after_rest_end) }}
                                </div>
                            </div>
                        </td>
                    </tr>
                @endif

                <tr>
                    <th>備考</th>
                    <td colspan="2" class="note-text">{{ $requestItem->reason }}</td>
                </tr>
            </table>
        </div>

<div class="request-detail-actions">
    @if($requestItem->status === 'approved')
        <button class="approve-button approve-button--done" disabled>承認済み</button>

    @elseif($requestItem->status === 'rejected')
        {{-- 仕様外なので表示しない or 承認済みと同じ扱いでもOK --}}
        <button class="approve-button approve-button--done" disabled>承認済み</button>

    @else
        <form method="POST" action="{{ route('admin.stamp_correction_request.approve', ['request' => $requestItem->id]) }}">
            @csrf
            <button type="submit" class="approve-button">承認</button>
        </form>
    @endif
</div>
    </div>
</div>
@endsection