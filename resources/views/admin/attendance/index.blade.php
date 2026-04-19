@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')
<div class="attendance-page">
  <div class="attendance-list">

    <div class="attendance-list__title">
      <h1>{{ $baseDate->format('Y年n月j日') }}の勤怠</h1>
    </div>

    <div class="attendance-list__month">
      <a class="month-nav" href="{{ route('admin.attendance.index', ['date' => $prevDate]) }}">← 前日</a>

      <div class="month-label">
        <span class="calendar-icon">📅</span>
        <span>{{ $baseDate->format('Y/m/d') }}</span>
      </div>

      @if($nextIsFuture)
        <span class="month-nav month-nav--disabled">翌日 →</span>
      @else
        <a class="month-nav" href="{{ route('admin.attendance.index', ['date' => $nextDate]) }}">翌日 →</a>
      @endif
    </div>

    <div class="attendance-table-wrap">
      <table class="attendance-table">
        <thead>
          <tr>
            <th>名前</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
          </tr>
        </thead>

        <tbody>
          @foreach($rows as $row)
            @php
              $u = $row['user'];
              $a = $row['attendance'];

              $in  = $a?->clock_in_at ? $a->clock_in_at->format('H:i') : '';
              $out = $a?->clock_out_at ? $a->clock_out_at->format('H:i') : '';
              $break = $a ? sprintf('%d:%02d', intdiv($a->break_minutes, 60), $a->break_minutes % 60) : '';
              $work  = $a ? sprintf('%d:%02d', intdiv($a->work_minutes, 60), $a->work_minutes % 60) : '';
            @endphp

            <tr>
              <td>{{ $u->name }}</td>
              <td>{{ $in }}</td>
              <td>{{ $out }}</td>
              <td>{{ $break }}</td>
              <td>{{ $work }}</td>
              <td>
                @if($a)
                  <a class="detail-link" href="{{ route('admin.attendance.show', $a) }}">詳細</a>
                @else
                  <a class="detail-link" href="{{ route('admin.attendance.show_by_date', ['user' => $u->id, 'date' => $baseDate->toDateString()]) }}">
                    詳細
                  </a>
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