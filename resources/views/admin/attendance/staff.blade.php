@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-staff-attendance.css') }}">
@endsection

@section('content')
<div class="staff-attendance-page">
    <div class="staff-attendance">

        <div class="staff-attendance__title">
            <h1>{{ $user->name }}さんの勤怠</h1>
        </div>

        <div class="staff-attendance__month-nav">
            <a class="month-nav"
               href="{{ route('admin.attendance.staff', ['user' => $user->id, 'month' => $prevMonth]) }}">
                ← 前月
            </a>

            <div class="month-label">
                <span class="calendar-icon">📅</span>
                <span>{{ $baseMonth->format('Y/m') }}</span>
            </div>

            @if($nextIsFuture)
                <span class="month-nav month-nav--disabled">翌月 →</span>
            @else
                <a class="month-nav"
                   href="{{ route('admin.attendance.staff', ['user' => $user->id, 'month' => $nextMonth]) }}">
                    翌月 →
                </a>
            @endif
        </div>

        <div class="staff-attendance-table-wrap">
            <table class="staff-attendance-table">
                <thead>
                    <tr>
                        <th class="col-date">日付</th>
                        <th class="col-time">出勤</th>
                        <th class="col-time">退勤</th>
                        <th class="col-break">休憩</th>
                        <th class="col-total">合計</th>
                        <th class="col-detail">詳細</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($rows as $row)
                        @php
                            $date = $row['date'];
                            $a = $row['attendance'];

                            $in  = $a?->clock_in_at ? $a->clock_in_at->format('H:i') : '';
                            $out = $a?->clock_out_at ? $a->clock_out_at->format('H:i') : '';

                            $break = $a ? sprintf('%d:%02d', intdiv($a->break_minutes, 60), $a->break_minutes % 60) : '';
                            $work  = $a ? sprintf('%d:%02d', intdiv($a->work_minutes, 60), $a->work_minutes % 60) : '';

                            $weekMap = ['日', '月', '火', '水', '木', '金', '土'];
                            $week = $weekMap[$date->dayOfWeek];

                            $dateClass = match ($date->dayOfWeek) {
                                0 => 'is-sunday',
                                6 => 'is-saturday',
                                default => '',
                            };
                        @endphp

                        <tr>
                            <td class="col-date {{ $dateClass }}">
                                {{ $date->format('m/d') }}（{{ $week }}）
                            </td>

                            <td class="col-time">{{ $in }}</td>
                            <td class="col-time">{{ $out }}</td>
                            <td class="col-break">{{ $break }}</td>
                            <td class="col-total">{{ $work }}</td>

                            <td class="col-detail">
                                <a
                                    href="{{ route('admin.attendance.show_by_date', [
                                        'user' => $user->id,
                                        'date' => $date->toDateString()
                                    ]) }}"
                                    class="detail-link"
                                >
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

<div class="staff-attendance__actions">
    <a class="csv-button"
       href="{{ route('admin.attendance.export_csv', [
           'user_id' => $user->id,
           'month' => $baseMonth->format('Y-m')
       ]) }}">
        CSV出力
    </a>
</div>

    </div>
</div>
@endsection