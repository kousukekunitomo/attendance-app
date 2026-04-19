@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

@section('content')
@php
    $date = $attendance->work_date
        ? \Carbon\Carbon::parse($attendance->work_date)
        : null;

    $breaks = $attendance->breaks->values();
    $rowsForEdit = max(2, $breaks->count() + 1);
@endphp

<div class="attendance-page">
    <div class="attendance-detail">

        <div class="attendance-detail__title">
            <h1>勤怠詳細</h1>
        </div>

        <div class="detail-card">
            <form id="attendance-form" method="POST" action="{{ route('admin.attendance.update', $attendance) }}">
                @csrf
                @method('PUT')

                <table class="detail-table">
                    <tbody>
                        <tr>
                            <th>名前</th>
                            <td class="detail-td">
                                <div class="detail-value detail-value--name">
                                    {{ $attendance->user->name }}
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th>日付</th>
                            <td class="detail-td">
                                <div class="date-row">
                                    <div class="date-col">
                                        {{ $date ? $date->format('Y年') : '' }}
                                    </div>
                                    <div class="date-col">
                                        {{ $date ? $date->format('n月j日') : '' }}
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th>出勤・退勤</th>
                            <td class="detail-td">
                                <div class="time-row">
                                    <div class="time-col">
                                        <input
                                            class="time-input"
                                            type="time"
                                            name="clock_in"
                                            value="{{ old('clock_in', optional($attendance->clock_in_at)->format('H:i')) }}"
                                        >
                                    </div>

                                    <div class="time-sep">〜</div>

                                    <div class="time-col">
                                        <input
                                            class="time-input"
                                            type="time"
                                            name="clock_out"
                                            value="{{ old('clock_out', optional($attendance->clock_out_at)->format('H:i')) }}"
                                        >
                                    </div>
                                </div>

                                @error('clock_in')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                                @error('clock_out')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </td>
                        </tr>

                        @for($i = 0; $i < $rowsForEdit; $i++)
                            @php
                                $break = $breaks->get($i);
                                $label = $i === 0 ? '休憩' : '休憩' . ($i + 1);

                                $startVal = $break?->break_start_at
                                    ? \Carbon\Carbon::parse($break->break_start_at)->format('H:i')
                                    : '';

                                $endVal = $break?->break_end_at
                                    ? \Carbon\Carbon::parse($break->break_end_at)->format('H:i')
                                    : '';
                            @endphp

                            <tr>
                                <th>{{ $label }}</th>
                                <td class="detail-td">
                                    <div class="time-row">
                                        <div class="time-col">
                                            <input
                                                class="time-input"
                                                type="time"
                                                name="breaks[{{ $i }}][start]"
                                                value="{{ old("breaks.$i.start", $startVal) }}"
                                            >
                                        </div>

                                        <div class="time-sep">〜</div>

                                        <div class="time-col">
                                            <input
                                                class="time-input"
                                                type="time"
                                                name="breaks[{{ $i }}][end]"
                                                value="{{ old("breaks.$i.end", $endVal) }}"
                                            >
                                        </div>
                                    </div>

                                    @error("breaks.$i.start")
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                    @error("breaks.$i.end")
                                        <p class="form-error">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                        @endfor

                        <tr>
                            <th>備考</th>
                            <td class="detail-td">
                                <textarea
                                    class="note-textarea"
                                    name="note"
                                    rows="2"
                                >{{ old('note', $attendance->note ?? '') }}</textarea>

                                @error('note')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>

        <div class="detail-actions--outside">
            <button class="btn-edit" type="submit" form="attendance-form">修正</button>
        </div>

    </div>
</div>
@endsection