<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class StaffController extends Controller
{
    /**
     * スタッフ一覧（管理者）
     * GET /admin/staff/list
     */
    public function index()
    {
        // 管理者以外のみ取得
        $staffs = User::query()
            ->where('is_admin', 0)
            ->orderBy('id')
            ->get(['id', 'name', 'email']);

        return view('admin.staff.index', compact('staffs'));
    }
}