@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-request-list.css') }}">
@endsection

@section('content')
<div class="request-page">
    <div class="request-list">

        <div class="request-list__title">
            <h1>申請一覧</h1>
        </div>

        @php
            $currentStatus = $status ?? 'pending';
        @endphp

        <div class="request-tabs">
            <a
                href="{{ route('admin.stamp_correction_request.index', ['status' => 'pending']) }}"
                class="request-tab {{ $currentStatus === 'pending' ? 'is-active' : '' }}"
            >
                承認待ち
            </a>
            <a
                href="{{ route('admin.stamp_correction_request.index', ['status' => 'approved']) }}"
                class="request-tab {{ $currentStatus === 'approved' ? 'is-active' : '' }}"
            >
                承認済み
            </a>
        </div>

        <div class="request-table-wrap">
            <table class="request-table">
                <thead>
                    <tr>
                        <th class="col-status">状態</th>
                        <th class="col-name">名前</th>
                        <th class="col-date">対象日時</th>
                        <th class="col-reason">申請理由</th>
                        <th class="col-created">申請日時</th>
                        <th class="col-detail">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $requestItem)
                        @php
                            $statusLabel = $requestItem->status === 'approved' ? '承認済み' : '承認待ち';
                            $statusClass = $requestItem->status === 'approved' ? 'status-approved' : 'status-pending';
                        @endphp

                        <tr>
                            <td class="col-status {{ $statusClass }}">
                                {{ $statusLabel }}
                            </td>
                            <td class="col-name">
                                {{ $requestItem->user->name ?? '' }}
                            </td>
                            <td class="col-date">
                                {{ $requestItem->work_date ? \Carbon\Carbon::parse($requestItem->work_date)->format('Y/m/d') : '' }}
                            </td>
                            <td class="col-reason">
                                {{ $requestItem->reason ?? '' }}
                            </td>
                            <td class="col-created">
                                {{ $requestItem->created_at ? $requestItem->created_at->format('Y/m/d') : '' }}
                            </td>
                            <td class="col-detail">
                                <a href="{{ route('admin.stamp_correction_request.show', $requestItem) }}" class="detail-link">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-row">申請はありません</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection