@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

@section('content')
@php
  $isPending = !empty($pendingRequest);

  $breaks = $attendance->breaks->values();

  $pendingBreaks = $isPending
    ? ($pendingRequest->breaks ?? collect())
    : collect();

  $rowsForEdit = $breakRows ?? max(2, $breaks->count() + 1);
  $rowsForFixed = max(1, $pendingBreaks->count());

  $fmt = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('H:i') : '';

  $yearLabel = $date->format('Y年');
  $mdLabel = $date->format('n月j日');
@endphp

<div class="attendance-page">
  <div class="attendance-detail">

    <div class="attendance-detail__title">
      <h1>勤怠詳細</h1>
    </div>

    <div class="detail-card">

      @if(!$isPending)
        <form id="attendance-form" method="POST" action="{{ route('stamp_correction_request.store') }}">
          @csrf
          <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

          <table class="detail-table">
            <tbody>
              <tr>
                <th>名前</th>
                <td class="detail-td">
                  <div class="detail-value detail-value--name">{{ $attendance->user->name }}</div>
                </td>
              </tr>

              <tr>
                <th>日付</th>
                <td class="detail-td">
                  <div class="date-row">
                    <div class="date-col">{{ $yearLabel }}</div>
                    <div class="date-sep"></div>
                    <div class="date-col">{{ $mdLabel }}</div>
                  </div>
                </td>
              </tr>

              <tr>
                <th>出勤・退勤</th>
                <td class="detail-td">
                  <div class="time-range">
                    <input class="time-input" type="time" name="clock_in"
                           value="{{ old('clock_in', optional($attendance->clock_in_at)->format('H:i')) }}">
                    <span class="time-sep">〜</span>
                    <input class="time-input" type="time" name="clock_out"
                           value="{{ old('clock_out', optional($attendance->clock_out_at)->format('H:i')) }}">
                  </div>
                  @error('clock_in') <p class="form-error">{{ $message }}</p> @enderror
                  @error('clock_out') <p class="form-error">{{ $message }}</p> @enderror
                </td>
              </tr>

              @for($i = 0; $i < $rowsForEdit; $i++)
                @php
                  $b = $breaks->get($i);
                  $label = $i === 0 ? '休憩' : '休憩'.($i + 1);
                  $startVal = $b?->break_start_at ? \Carbon\Carbon::parse($b->break_start_at)->format('H:i') : '';
                  $endVal   = $b?->break_end_at   ? \Carbon\Carbon::parse($b->break_end_at)->format('H:i') : '';
                @endphp

                <tr>
                  <th>{{ $label }}</th>
                  <td class="detail-td">
                    <div class="time-range">
                      <input class="time-input" type="time" name="breaks[{{ $i }}][start]"
                             value="{{ old("breaks.$i.start", $startVal) }}">
                      <span class="time-sep">〜</span>
                      <input class="time-input" type="time" name="breaks[{{ $i }}][end]"
                             value="{{ old("breaks.$i.end", $endVal) }}">
                    </div>
                    @error("breaks.$i.start") <p class="form-error">{{ $message }}</p> @enderror
                    @error("breaks.$i.end") <p class="form-error">{{ $message }}</p> @enderror
                  </td>
                </tr>
              @endfor

              <tr>
                <th>備考</th>
                <td class="detail-td">
                  <textarea class="note-textarea" name="note" rows="2">{{ old('note', $attendance->note ?? '') }}</textarea>
                  @error('note') <p class="form-error">{{ $message }}</p> @enderror
                </td>
              </tr>
            </tbody>
          </table>
        </form>

      @else
        <table class="detail-table">
          <tbody>
            <tr>
              <th>名前</th>
              <td class="detail-td">
                <div class="detail-value detail-value--name">{{ $attendance->user->name }}</div>
              </td>
            </tr>

            <tr>
              <th>日付</th>
              <td class="detail-td">
                <div class="date-row">
                  <div class="date-col">{{ $yearLabel }}</div>
                  <div class="date-sep"></div>
                  <div class="date-col">{{ $mdLabel }}</div>
                </div>
              </td>
            </tr>

            <tr>
              <th>出勤・退勤</th>
              <td class="detail-td">
                <div class="time-fixed">
                  <span>{{ $fmt($pendingRequest->after_clock_in) }}</span>
                  <span class="time-sep">〜</span>
                  <span>{{ $fmt($pendingRequest->after_clock_out) }}</span>
                </div>
              </td>
            </tr>

            @for($i = 0; $i < $rowsForFixed; $i++)
              @php
                $pb = $pendingBreaks->get($i);
                $label = $i === 0 ? '休憩' : '休憩'.($i + 1);
              @endphp
              <tr>
                <th>{{ $label }}</th>
                <td class="detail-td">
                  <div class="time-fixed">
                    <span>{{ $fmt($pb?->after_rest_start) }}</span>
                    <span class="time-sep">〜</span>
                    <span>{{ $fmt($pb?->after_rest_end) }}</span>
                  </div>
                </td>
              </tr>
            @endfor

            <tr>
              <th>備考</th>
              <td class="detail-td">
                <div class="detail-value">
                  {{ $attendance->note ?? '' }}
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      @endif
    </div>

    <div class="detail-actions--outside">
      @if(!$isPending)
        <button class="btn-edit" type="submit" form="attendance-form">修正</button>
      @else
        <p class="pending-msg-red">※ 承認待ちのため修正はできません。</p>
      @endif
    </div>

  </div>
</div>
@endsection