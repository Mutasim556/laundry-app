@extends('admin::layouts.app')

@section('content')
<style>
    /* Modern minimalist styles & animated interactive buttons */
    .btn-animate {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .btn-animate:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(30, 136, 229, 0.35);
    }
    .btn-animate:active {
        transform: translateY(0);
        box-shadow: none;
    }
    .btn-dark-animate {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .btn-dark-animate:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(33, 37, 41, 0.4);
    }
    .btn-dark-animate:active {
        transform: translateY(0);
    }
    .btn-outline-animate {
        transition: all 0.25s ease;
    }
    .btn-outline-animate:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    /* Minimalist input enhancements */
    .form-minimal {
        background-color: #f8fafc;
        border: 1.5px solid #e2e8f0;
        transition: all 0.2s ease;
        padding: 0.65rem 1rem;
        font-size: 0.95rem;
    }
    .form-minimal:focus {
        background-color: #ffffff;
        border-color: #1e88e5;
        box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.12);
        outline: none;
    }
    .password-hint-item {
        font-size: 0.82rem;
        transition: color 0.2s ease;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <!-- Breadcrumb / Header -->
    <div class="col-12 mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h3 class="fw-bold mb-1 text-dark">Admin Profile</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 bg-transparent p-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Profile Settings</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary rounded-pill px-3 btn-outline-animate">
                <i data-feather="arrow-left" class="feather-sm me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Notifications / Alerts -->
    @if (session('status') === 'profile-updated')
        <div class="col-12 mb-3">
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center" role="alert">
                <i data-feather="check-circle" class="feather-sm me-2 text-success"></i>
                <div class="fw-semibold">Profile information and avatar updated successfully!</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @elseif (session('status') === 'password-updated')
        <div class="col-12 mb-3">
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center" role="alert">
                <i data-feather="shield" class="feather-sm me-2 text-success"></i>
                <div class="fw-semibold">Security credentials updated! Your new password has been saved.</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <!-- Profile Overview Card (Centered, Auto Height) -->
    <div class="col-lg-4 mb-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <div class="card-body p-0 d-flex flex-column align-items-center justify-content-center">
                <div class="position-relative mb-3">
                    <img
                        id="overview-avatar"
                        src="{{ $admin->avatar_url }}"
                        alt="{{ $admin->name }}"
                        class="rounded-circle shadow-sm border border-3 border-primary-subtle object-fit-cover"
                        style="width: 110px; height: 110px;"
                    />
                    <span class="position-absolute bottom-0 end-0 bg-success border border-white border-2 rounded-circle p-2" title="Active"></span>
                </div>
                <h4 class="fw-bold mb-1 text-dark">{{ $admin->name }}</h4>
                <p class="text-muted mb-3">{{ $admin->email }}</p>
                <div class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 fw-semibold fs-2">
                    Super Administrator
                </div>
                <hr class="w-100 my-4 text-muted opacity-25">
                <div class="w-100 text-start">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted fs-2">Account Role:</span>
                        <span class="fw-semibold text-dark fs-2">Administrator</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted fs-2">Guard:</span>
                        <span class="badge bg-dark rounded-pill px-2">admin</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted fs-2">Member Since:</span>
                        <span class="fw-semibold text-dark fs-2">{{ $admin->created_at ? $admin->created_at->format('M Y') : 'Recent' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forms (Avatar + Personal info & Change Password) -->
    <div class="col-lg-8">
        <!-- Profile Info & Photo Upload Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-bottom border-light p-4">
                <h4 class="card-title fw-bold text-dark mb-1">Personal Details and Avatar</h4>
                <p class="card-subtitle text-muted mb-0">Update your account name, email address, and profile photo.</p>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <!-- Photo Upload with Side-by-Side Comparison -->
                    <div class="mb-4 p-4 bg-light bg-opacity-75 rounded-4 border border-light-subtle">
                        <label class="form-label fw-bold text-dark mb-1 d-block">Profile Photo</label>
                        <p class="text-muted small mb-3">Upload a clean square image (JPG, PNG or WEBP, max 2MB). Current and new photos display side-by-side.</p>
                        
                        <div class="d-flex flex-wrap align-items-center gap-4 mb-3">
                            <!-- Current Photo -->
                            <div class="text-center">
                                <div class="text-muted small fw-semibold mb-2">Current Photo</div>
                                <img
                                    id="current-photo-img"
                                    src="{{ $admin->avatar_url }}"
                                    alt="Current Avatar"
                                    class="rounded-circle shadow-sm border border-2 border-secondary-subtle object-fit-cover"
                                    style="width: 85px; height: 85px;"
                                />
                            </div>

                            <!-- Animated Arrow Indicator -->
                            <div id="compare-arrow" class="d-none text-primary d-md-flex flex-column align-items-center justify-content-center">
                                <i data-feather="arrow-right" class="text-primary" style="width: 24px; height: 24px;"></i>
                                <span class="small fw-semibold mt-1">Replacing</span>
                            </div>

                            <!-- New Photo Preview -->
                            <div id="new-photo-container" class="text-center d-none">
                                <div class="text-primary small fw-semibold mb-2">New Preview</div>
                                <img
                                    id="new-photo-preview"
                                    src=""
                                    alt="New Avatar Preview"
                                    class="rounded-circle shadow border border-2 border-primary object-fit-cover"
                                    style="width: 85px; height: 85px;"
                                />
                            </div>
                        </div>

                        <!-- Upload Button -->
                        <div>
                            <input
                                type="file"
                                id="avatar-input"
                                name="avatar"
                                class="d-none"
                                accept="image/png, image/jpeg, image/jpg, image/webp"
                            />
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-outline-animate" onclick="document.getElementById('avatar-input').click();">
                                <i data-feather="upload" class="feather-sm me-1"></i> Choose New Photo
                            </button>
                            <button type="button" id="clear-avatar-btn" class="btn btn-sm btn-link text-danger d-none ms-2 text-decoration-none btn-outline-animate" onclick="resetAvatarPreview();">
                                <i data-feather="x" class="feather-sm me-1"></i> Cancel
                            </button>
                            @error('avatar')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Name Field -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold text-dark">Full Name</label>
                        <input
                            type="text"
                            class="form-control rounded-3 form-minimal @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            value="{{ old('name', $admin->name) }}"
                            required
                        />
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email Field -->
                    <div class="mb-4">
                        <label for="email" class="form-label fw-semibold text-dark">Email Address</label>
                        <input
                            type="email"
                            class="form-control rounded-3 form-minimal @error('email') is-invalid @enderror"
                            id="email"
                            name="email"
                            value="{{ old('email', $admin->email) }}"
                            required
                        />
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold btn-animate">
                            <i data-feather="check" class="feather-sm me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-bottom border-light p-4">
                <h4 class="card-title fw-bold text-dark mb-1">Change Password</h4>
                <p class="card-subtitle text-muted mb-0">Ensure your account is protected by setting a strong security key.</p>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.profile.password.update') }}">
                    @csrf
                    @method('PUT')

                    <!-- Current Password -->
                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold text-dark">Current Password</label>
                        <div class="position-relative">
                            <input
                                type="password"
                                class="form-control rounded-3 form-minimal pe-5 @error('current_password') is-invalid @enderror"
                                id="current_password"
                                name="current_password"
                                placeholder="Enter current password"
                                autocomplete="current-password"
                                required
                            />
                            <button
                                type="button"
                                class="btn position-absolute top-50 end-0 translate-middle-y border-0 bg-transparent text-muted pe-3 toggle-password-btn"
                                onclick="togglePasswordVisibility('current_password', this)"
                                tabindex="-1"
                                aria-label="Toggle password visibility"
                            >
                                <i data-feather="eye" class="feather-sm"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- New Password -->
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold text-dark">New Password</label>
                        <div class="position-relative">
                            <input
                                type="password"
                                class="form-control rounded-3 form-minimal pe-5 @error('password') is-invalid @enderror"
                                id="new_password"
                                name="password"
                                placeholder="Must be at least 8 characters"
                                autocomplete="new-password"
                                required
                                minlength="8"
                            />
                            <button
                                type="button"
                                class="btn position-absolute top-50 end-0 translate-middle-y border-0 bg-transparent text-muted pe-3 toggle-password-btn"
                                onclick="togglePasswordVisibility('new_password', this)"
                                tabindex="-1"
                                aria-label="Toggle password visibility"
                            >
                                <i data-feather="eye" class="feather-sm"></i>
                            </button>
                        </div>
                        <!-- At least 8 characters password indicator badge -->
                        <div class="d-flex align-items-center mt-2 password-hint-item text-muted" id="length-hint">
                            <i data-feather="info" class="feather-sm me-1"></i>
                            <span>Password must be at least <strong>8 characters</strong> long.</span>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Confirm New Password -->
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-semibold text-dark">Confirm Password</label>
                        <div class="position-relative">
                            <input
                                type="password"
                                class="form-control rounded-3 form-minimal pe-5"
                                id="password_confirmation"
                                name="password_confirmation"
                                placeholder="Re-enter your new password"
                                autocomplete="new-password"
                                required
                                minlength="8"
                            />
                            <button
                                type="button"
                                class="btn position-absolute top-50 end-0 translate-middle-y border-0 bg-transparent text-muted pe-3 toggle-password-btn"
                                onclick="togglePasswordVisibility('password_confirmation', this)"
                                tabindex="-1"
                                aria-label="Toggle password visibility"
                            >
                                <i data-feather="eye" class="feather-sm"></i>
                            </button>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 fw-semibold btn-dark-animate">
                            <i data-feather="lock" class="feather-sm me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<script>
    const avatarInput = document.getElementById('avatar-input');
    const newPhotoPreview = document.getElementById('new-photo-preview');
    const newPhotoContainer = document.getElementById('new-photo-container');
    const compareArrow = document.getElementById('compare-arrow');
    const clearAvatarBtn = document.getElementById('clear-avatar-btn');

    if (avatarInput) {
        avatarInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    newPhotoPreview.src = event.target.result;
                    newPhotoContainer.classList.remove('d-none');
                    compareArrow.classList.remove('d-none');
                    clearAvatarBtn.classList.remove('d-none');
                    if (window.feather) {
                        feather.replace();
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    function resetAvatarPreview() {
        avatarInput.value = '';
        newPhotoPreview.src = '';
        newPhotoContainer.classList.add('d-none');
        compareArrow.classList.add('d-none');
        clearAvatarBtn.classList.add('d-none');
    }

    // Live password 8-characters feedback
    const newPasswordInput = document.getElementById('new_password');
    const lengthHint = document.getElementById('length-hint');

    if (newPasswordInput && lengthHint) {
        newPasswordInput.addEventListener('input', function () {
            if (this.value.length >= 8) {
                lengthHint.classList.remove('text-muted', 'text-danger');
                lengthHint.classList.add('text-success');
                lengthHint.innerHTML = '<i data-feather="check" class="feather-sm me-1"></i> Meets minimum 8 characters requirement.';
            } else if (this.value.length > 0) {
                lengthHint.classList.remove('text-muted', 'text-success');
                lengthHint.classList.add('text-danger');
                lengthHint.innerHTML = '<i data-feather="alert-circle" class="feather-sm me-1"></i> Still needs ' + (8 - this.value.length) + ' more character(s).';
            } else {
                lengthHint.classList.remove('text-success', 'text-danger');
                lengthHint.classList.add('text-muted');
                lengthHint.innerHTML = '<i data-feather="info" class="feather-sm me-1"></i> Password must be at least <strong>8 characters</strong> long.';
            }
            if (window.feather) {
                feather.replace();
            }
        });
    }
    // Toggle password visibility
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            btn.innerHTML = '<i data-feather="eye-off" class="feather-sm text-primary"></i>';
        } else {
            input.type = 'password';
            btn.innerHTML = '<i data-feather="eye" class="feather-sm"></i>';
        }

        if (window.feather) {
            feather.replace();
        }
    }
</script>
@endsection
