<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HomepageContentVersion;
use App\Services\CustomerLoginOtpSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerLoginOtpSettingController extends Controller
{
    public function __construct(
        protected CustomerLoginOtpSettingService $otpSettings
    ) {}

    public function index(): View
    {
        $record = $this->otpSettings->getOrInitializeRecord();
        $isTestingAuthorized = $this->otpSettings->isEnvironmentAuthorizedForTesting();

        $versions = HomepageContentVersion::query()
            ->with('createdByAdmin:id,name')
            ->where('homepage_content_id', $record->id)
            ->orderByDesc('version_number')
            ->limit(15)
            ->get();

        return view('admin::website-manager.customer-login-otp', [
            'record' => $record,
            'selectedMode' => $this->otpSettings->getSelectedMode(),
            'effectiveMode' => $this->otpSettings->getEffectiveMode(),
            'environmentLabel' => ucfirst((string) app()->environment()),
            'isEnvironmentApproved' => $this->otpSettings->isEnvironmentApproved(),
            'isTestingConfigEnabled' => $this->otpSettings->isTestingConfigEnabled(),
            'isTestingAuthorized' => $isTestingAuthorized,
            'testOtpCode' => $isTestingAuthorized ? $this->otpSettings->getTestOtpCode() : null,
            'versions' => $versions,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in([
                CustomerLoginOtpSettingService::MODE_EMAIL,
                CustomerLoginOtpSettingService::MODE_TESTING,
            ])],
        ]);

        $this->otpSettings->updateMode(
            $validated['mode'],
            (int) Auth::guard('admin')->id()
        );

        $effectiveMode = $this->otpSettings->getEffectiveMode();

        return response()->json([
            'status' => 'ok',
            'message' => $effectiveMode === CustomerLoginOtpSettingService::MODE_TESTING
                ? 'Customer login switched to Testing OTP Mode.'
                : 'Customer login switched to Email OTP Mode.',
            'selected_mode' => $this->otpSettings->getSelectedMode(),
            'effective_mode' => $effectiveMode,
            'effective_mode_label' => $effectiveMode === CustomerLoginOtpSettingService::MODE_TESTING ? 'Testing OTP' : 'Email OTP',
        ]);
    }
}
