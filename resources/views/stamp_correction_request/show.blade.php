@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request-show.css') }}">
@endsection

@section('content')
@php
    $fmt = fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('H:i') : '';

    $statusLabel = match ($requestItem->status) {
        'approved' => '承認済み',
        'rejected' => '却下',
        default => '承認待ち',
    };

    $statusClass = match ($requestItem->status) {
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        default => 'status-pending',
    };

    $breaks = $requestItem->breaks ?? collect();

    $yearLabel = \Carbon\Carbon::parse($requestItem->work_date)->format('Y年');
    $mdLabel = \Carbon\Carbon::parse($requestItem->work_date)->format('n月j日');
@endphp

<div class="request-show-page">
    <div class="request-show">

        <div class="request-show__title">
            <h1>申請詳細</h1>
        </div>

        <div class="request-show__card">
            <table class="request-show__table">
                <tbody>
                    <tr>
                        <th>状態</th>
                        <td>
                            <span class="request-status {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                    </tr>

                    <tr>
                        <th>名前</th>
                        <td>
                            <div class="request-value">{{ $requestItem->user->name }}</div>
                        </td>
                    </tr>

                    <tr>
                        <th>日付</th>
                        <td>
                            <div class="date-row">
                                <div class="date-col">{{ $yearLabel }}</div>
                                <div class="date-sep"></div>
                                <div class="date-col">{{ $mdLabel }}</div>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>出勤・退勤</th>
                        <td>
                            <div class="time-fixed">
                                <span>{{ $fmt($requestItem->after_clock_in) }}</span>
                                <span class="time-sep">〜</span>
                                <span>{{ $fmt($requestItem->after_clock_out) }}</span>
                            </div>
                        </td>
                    </tr>

                    @forelse($breaks as $i => $break)
                        <tr>
                            <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
                            <td>
                                <div class="time-fixed">
                                    <span>{{ $fmt($break->after_rest_start) }}</span>
                                    <span class="time-sep">〜</span>
                                    <span>{{ $fmt($break->after_rest_end) }}</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        @if($requestItem->after_rest_start || $requestItem->after_rest_end)
                            <tr>
                                <th>休憩</th>
                                <td>
                                    <div class="time-fixed">
                                        <span>{{ $fmt($requestItem->after_rest_start) }}</span>
                                        <span class="time-sep">〜</span>
                                        <span>{{ $fmt($requestItem->after_rest_end) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforelse

                    <tr>
                        <th>申請理由</th>
                        <td>
                            <div class="request-note">{{ $requestItem->reason }}</div>
                        </td>
                    </tr>

                    <tr>
                        <th>申請日時</th>
                        <td>
                            <div class="request-value">
                                {{ $requestItem->created_at->format('Y/m/d H:i') }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="request-show__actions">
            <a href="{{ route('stamp_correction_request.index', ['status' => $requestItem->status === 'approved' ? 'approved' : 'pending']) }}"
               class="back-link">
                一覧へ戻る
            </a>
        </div>

    </div>
</div>
@endsection