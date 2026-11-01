<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Admin dashboard.
     */
    public function index(): View
    {
        $admin = Auth::guard('admin')->user();

        return view('admin::dashboard', [
            'admin' => $admin,
        ]);
    }
}

