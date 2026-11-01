@extends('admin::layouts.app')

@section('content')
<div class="row page-titles">
    <div class="col-md-5 col-12 align-self-center">
        <h3 class="text-themecolor mb-0">Dashboard</h3>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a href="javascript:void(0)">Home</a>
            </li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </div>
    <div class="col-md-7 col-12 align-self-center d-none d-md-block">
        <div class="d-flex mt-2 justify-content-end">
            <div class="d-flex me-3 ms-2">
                <div class="chart-text me-2">
                    <h6 class="mb-0"><small>THIS MONTH</small></h6>
                    <h4 class="mt-0 text-info">$58,356</h4>
                </div>
                <div class="spark-chart">
                    <div id="monthchart"></div>
                </div>
            </div>
            <div class="d-flex ms-2">
                <div class="chart-text me-2">
                    <h6 class="mb-0"><small>LAST MONTH</small></h6>
                    <h4 class="mt-0 text-primary">$48,356</h4>
                </div>
                <div class="spark-chart">
                    <div id="lastmonthchart"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="container-fluid">
    <div class="row">
        <!-- Welcome Card -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-md-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="card-title text-dark fw-bold mb-1">
                                Welcome back, {{ Auth::guard('admin')->user()->name ?? 'Administrator' }}! 👋
                            </h3>
                            <p class="card-subtitle text-muted mb-0">
                                Here is what's happening with your laundry operations today.
                            </p>
                        </div>
                        <div class="mt-3 mt-md-0">
                            <form method="POST" action="{{ route('admin.logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger text-white rounded-pill px-4">
                                    <i data-feather="log-out" class="feather-sm me-1"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div class="col-md-6 col-lg-3">
            <div class="card card-hover">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-light-primary text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i data-feather="shopping-bag" class="feather-md"></i>
                        </div>
                        <div class="ms-3">
                            <h2 class="mb-0 fw-bold">128</h2>
                            <span class="text-muted">Total Orders</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card card-hover">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-light-warning text-warning rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i data-feather="clock" class="feather-md"></i>
                        </div>
                        <div class="ms-3">
                            <h2 class="mb-0 fw-bold">24</h2>
                            <span class="text-muted">In Progress</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card card-hover">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-light-success text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i data-feather="check-circle" class="feather-md"></i>
                        </div>
                        <div class="ms-3">
                            <h2 class="mb-0 fw-bold">96</h2>
                            <span class="text-muted">Completed</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card card-hover">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-light-info text-info rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i data-feather="users" class="feather-md"></i>
                        </div>
                        <div class="ms-3">
                            <h2 class="mb-0 fw-bold">42</h2>
                            <span class="text-muted">Customers</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
