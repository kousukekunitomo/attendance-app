@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')
@php
  $nextIsFuture = $baseMonth->copy()->addMonth()->startOfMonth()->gt(now()->startOfMonth());
  $weekdayLabels = ['日', '月', '火', '水', '木', '金', '土'];
@endphp

<div class="attendance-page">
  <div class="attendance-list">

    <div class="attendance-list__title">
      <h1>勤怠一覧</h1>
    </div>

    <div class="attendance-list__month">
      <a class="month-nav" href="{{ route('attendance.list', ['month' => $prevMonth]) }}">← 前月</a>

      <div class="month-label">
        <span class="calendar-icon">📅</span>
        <span>{{ $baseMonth->format('Y/m') }}</span>
      </div>

      @if($nextIsFuture)
        <span class="month-nav month-nav--disabled" aria-disabled="true">翌月 →</span>
      @else
        <a class="month-nav" href="{{ route('attendance.list', ['month' => $nextMonth]) }}">翌月 →</a>
      @endif
    </div>

    <div class="attendance-table-wrap">
      <table class="attendance-table">
        <thead>
          <tr>
            <th>日付</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
          </tr>
        </thead>
        <tbody>
          @foreach($days as $row)
            @php
              $date = $row['date'];
              $a = $row['attendance'];

              $in  = $a?->clock_in_at ? $a->clock_in_at->format('H:i') : '';
              $out = $a?->clock_out_at ? $a->clock_out_at->format('H:i') : '';
              $break = $a ? sprintf('%d:%02d', intdiv($a->break_minutes, 60), $a->break_minutes % 60) : '';
              $work  = $a ? sprintf('%d:%02d', intdiv($a->work_minutes, 60), $a->work_minutes % 60) : '';
              $dateParam = $date->toDateString();

              $weekdayClass = match($date->dayOfWeek) {
                  0 => 'weekday-sun',
                  6 => 'weekday-sat',
                  default => '',
              };
            @endphp

            <tr>
              <td class="attendance-date {{ $weekdayClass }}">
                {{ $date->format('m/d') }}({{ $weekdayLabels[$date->dayOfWeek] }})
              </td>
              <td>{{ $in }}</td>
              <td>{{ $out }}</td>
              <td>{{ $break }}</td>
              <td>{{ $work }}</td>
              <td>
                @if($a)
                  <a class="detail-link" href="{{ route('attendance.show', $a) }}">詳細</a>
                @else
                  <a class="detail-link" href="{{ route('attendance.show_by_date', ['date' => $dateParam]) }}">詳細</a>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

  </div>
</div>
@endsection