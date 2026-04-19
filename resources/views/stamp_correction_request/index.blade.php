@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request-list.css') }}">
@endsection

@section('content')
<div class="request-page">
    <div class="request-list">

        <div class="request-list__title">
            <h1>申請一覧</h1>
        </div>

        <div class="request-tabs">
            <a href="{{ route('stamp_correction_request.index', ['status' => 'pending']) }}"
               class="request-tab {{ $status === 'pending' ? 'is-active' : '' }}">
                承認待ち
            </a>

            <a href="{{ route('stamp_correction_request.index', ['status' => 'approved']) }}"
               class="request-tab {{ $status === 'approved' ? 'is-active' : '' }}">
                承認済み
            </a>
        </div>

        <div class="request-table-wrap">
            <table class="request-table">
                <thead>
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日時</th>
                        <th>申請理由</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $correctionRequest)
                        @php
                            $statusLabel = match($correctionRequest->status) {
                                'approved' => '承認済み',
                                'rejected' => '却下',
                                default => '承認待ち',
                            };

                            $statusClass = match($correctionRequest->status) {
                                'approved' => 'status-approved',
                                'rejected' => 'status-rejected',
                                default => 'status-pending',
                            };
                        @endphp

                        <tr>
                            <td class="{{ $statusClass }}">
                                {{ $statusLabel }}
                            </td>
                            <td>{{ $correctionRequest->user->name ?? '' }}</td>
                            <td>{{ \Carbon\Carbon::parse($correctionRequest->work_date)->format('Y/m/d') }}</td>
                            <td>{{ $correctionRequest->reason }}</td>
                            <td>{{ $correctionRequest->created_at->format('Y/m/d') }}</td>
                            <td>
                                <a href="{{ route('stamp_correction_request.show', $correctionRequest) }}" class="detail-link">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="request-empty">申請はありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($requests, 'links'))
            <div class="request-pagination">
                {{ $requests->links() }}
            </div>
        @endif

    </div>
</div>
@endsection