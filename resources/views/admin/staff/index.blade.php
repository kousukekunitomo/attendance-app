@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-staff-list.css') }}">
@endsection

@section('content')
<div class="staff-page">
    <div class="staff-list">

        <div class="staff-list__title">
            <h1>スタッフ一覧</h1>
        </div>

        <div class="staff-table-wrap">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th class="col-name">名前</th>
                        <th class="col-email">メールアドレス</th>
                        <th class="col-detail">月次勤怠</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($staffs as $staff)
                        <tr>
                            <td class="col-name">{{ $staff->name }}</td>
                            <td class="col-email">{{ $staff->email }}</td>
                            <td class="col-detail">
                                <a href="{{ route('admin.attendance.staff', $staff) }}" class="detail-link">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection