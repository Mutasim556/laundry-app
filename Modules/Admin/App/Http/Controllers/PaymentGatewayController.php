<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PayPalPaymentService;
use App\Services\StripePaymentService;
use Database\Seeders\PaymentGatewaySeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Admin\App\Models\PaymentGateway;
use Modules\Admin\App\Models\PaymentGatewayAuditLog;

class PaymentGatewayController extends Controller
{
    protected function ensureGatewaysExist(): void
    {
        $count = PaymentGateway::allowed()->count();
        if ($count < count(PaymentGateway::ALLOWED_GATEWAYS)) {
            app(PaymentGatewaySeeder::class)->run();
        }
    }

    protected function findAllowedGateway(string $gatewayCode): PaymentGateway
    {
        if (! in_array($gatewayCode, PaymentGateway::ALLOWED_GATEWAYS, true)) {
            abort(404, 'Unsupported payment gateway.');
        }

        $this->ensureGatewaysExist();

        return PaymentGateway::where('gateway_code', $gatewayCode)->firstOrFail();
    }

    protected function recordAudit(
        Request $request,
        string $gatewayCode,
        string $action,
        string $summary,
        array $metadata = []
    ): void {
        $admin = Auth::guard('admin')->user();

        PaymentGatewayAuditLog::create([
            'gateway_code' => $gatewayCode,
            'admin_id' => $admin?->id,
            'admin_name' => $admin?->name ?? 'Admin',
            'action' => $action,
            'summary' => $summary,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
        ]);
    }

    public function index()
    {
        $this->ensureGatewaysExist();

        $gateways = PaymentGateway::allowed()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->keyBy('gateway_code');

        $auditLogs = PaymentGatewayAuditLog::orderByDesc('id')
            ->limit(25)
            ->get();

        return view('admin::payment-gateways.index', compact('gateways', 'auditLogs'));
    }

    public function updateStripe(Request $request): RedirectResponse
    {
        $gateway = $this->findAllowedGateway('stripe');

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'environment' => ['required', Rule::in(['sandbox', 'live'])],
            'publishable_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'capture_mode' => ['nullable', Rule::in(['delayed_setup_intent', 'immediate'])],
            'enable_saved_cards' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $existingCredentials = $gateway->getCredentials();
        $secretInput = trim((string) ($validated['secret_key'] ?? ''));
        $webhookInput = trim((string) ($validated['webhook_secret'] ?? ''));

        if ($secretInput !== '' && $secretInput !== '********') {
            $existingCredentials['secret_key'] = $secretInput;
        }
        if ($webhookInput !== '' && $webhookInput !== '********') {
            $existingCredentials['webhook_secret'] = $webhookInput;
        }

        $publishableKey = trim((string) ($validated['publishable_key'] ?? ''));
        if ($publishableKey !== '' && ! str_starts_with($publishableKey, 'pk_')) {
            return back()->withErrors([
                'publishable_key' => 'Stripe Publishable Key must start with pk_test_ or pk_live_.',
            ])->withInput();
        }
        if (! empty($existingCredentials['secret_key']) && ! str_starts_with($existingCredentials['secret_key'], 'sk_') && ! str_starts_with($existingCredentials['secret_key'], 'rk_')) {
            return back()->withErrors([
                'secret_key' => 'Stripe Secret Key must start with sk_test_, sk_live_, or rk_.',
            ])->withInput();
        }

        $gateway->display_name = $validated['display_name'];
        $gateway->description = $validated['description'] ?? $gateway->description;
        $gateway->environment = $validated['environment'];
        $gateway->sort_order = (int) $validated['sort_order'];
        $gateway->public_configuration = [
            'publishable_key' => $publishableKey,
            'currency' => strtoupper($validated['currency']),
            'capture_mode' => $validated['capture_mode'] ?? 'delayed_setup_intent',
            'enable_saved_cards' => $request->boolean('enable_saved_cards'),
        ];
        $gateway->setCredentials($existingCredentials);

        $wantActive = $request->boolean('is_active');
        if ($wantActive && ! $gateway->isConfigured()) {
            return back()->withErrors([
                'is_active' => 'Stripe cannot be activated until both Publishable Key and Secret Key are configured.',
            ])->withInput();
        }

        $gateway->is_active = $wantActive;
        $gateway->syncConfigurationStatus();
        $gateway->save();

        $this->recordAudit($request, 'stripe', 'configured', 'Updated Stripe configuration', [
            'environment' => $gateway->environment,
            'currency' => $gateway->getPublicValue('currency'),
            'is_active' => $gateway->is_active,
            'sort_order' => $gateway->sort_order,
        ]);

        return redirect()
            ->route('admin.payment-gateways.index')
            ->with('success', 'Stripe gateway settings updated successfully.');
    }

    public function updatePayPal(Request $request): RedirectResponse
    {
        $gateway = $this->findAllowedGateway('paypal');

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'environment' => ['required', Rule::in(['sandbox', 'live'])],
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'webhook_id' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $existingCredentials = $gateway->getCredentials();
        $secretInput = trim((string) ($validated['client_secret'] ?? ''));

        if ($secretInput !== '' && $secretInput !== '********') {
            $existingCredentials['client_secret'] = $secretInput;
        }

        $gateway->display_name = $validated['display_name'];
        $gateway->description = $validated['description'] ?? $gateway->description;
        $gateway->environment = $validated['environment'];
        $gateway->sort_order = (int) $validated['sort_order'];
        $gateway->public_configuration = [
            'client_id' => trim((string) ($validated['client_id'] ?? '')),
            'currency' => strtoupper($validated['currency']),
            'webhook_id' => trim((string) ($validated['webhook_id'] ?? '')),
        ];
        $gateway->setCredentials($existingCredentials);

        $wantActive = $request->boolean('is_active');
        if ($wantActive && ! $gateway->isConfigured()) {
            return back()->withErrors([
                'is_active' => 'PayPal cannot be activated until both Client ID and Client Secret are configured.',
            ])->withInput();
        }

        $gateway->is_active = $wantActive;
        $gateway->syncConfigurationStatus();
        $gateway->save();

        $this->recordAudit($request, 'paypal', 'configured', 'Updated PayPal configuration', [
            'environment' => $gateway->environment,
            'currency' => $gateway->getPublicValue('currency'),
            'is_active' => $gateway->is_active,
            'sort_order' => $gateway->sort_order,
        ]);

        return redirect()
            ->route('admin.payment-gateways.index')
            ->with('success', 'PayPal gateway settings updated successfully.');
    }

    public function updateCod(Request $request): RedirectResponse
    {
        $gateway = $this->findAllowedGateway('cod');

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'instructions' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $gateway->display_name = $validated['display_name'];
        $gateway->description = $validated['description'] ?? $gateway->description;
        $gateway->environment = 'none';
        $gateway->sort_order = (int) $validated['sort_order'];
        $gateway->public_configuration = [
            'instructions' => trim((string) ($validated['instructions'] ?? '')),
        ];
        $gateway->is_active = $request->boolean('is_active');
        $gateway->syncConfigurationStatus();
        $gateway->save();

        $this->recordAudit($request, 'cod', 'configured', 'Updated Cash on Delivery settings', [
            'is_active' => $gateway->is_active,
            'sort_order' => $gateway->sort_order,
        ]);

        return redirect()
            ->route('admin.payment-gateways.index')
            ->with('success', 'Cash on Delivery settings updated successfully.');
    }

    public function toggleStatus(Request $request, string $gatewayCode): RedirectResponse|JsonResponse
    {
        $gateway = $this->findAllowedGateway($gatewayCode);
        $targetState = $request->has('is_active')
            ? $request->boolean('is_active')
            : ! $gateway->is_active;

        if ($targetState && ! $gateway->isConfigured()) {
            $message = "{$gateway->display_name} cannot be activated until required credentials are configured.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $gateway->is_active = $targetState;
        $gateway->syncConfigurationStatus();
        $gateway->save();

        $action = $gateway->is_active ? 'activated' : 'deactivated';
        $this->recordAudit($request, $gateway->gateway_code, $action, "{$gateway->display_name} {$action}", [
            'is_active' => $gateway->is_active,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'gateway_code' => $gateway->gateway_code,
                'is_active' => $gateway->is_active,
                'configuration_status' => $gateway->configuration_status,
                'message' => "{$gateway->display_name} {$action}.",
            ]);
        }

        return redirect()
            ->route('admin.payment-gateways.index')
            ->with('success', "{$gateway->display_name} {$action}.");
    }

    public function testConnection(
        Request $request,
        string $gatewayCode,
        StripePaymentService $stripeService,
        PayPalPaymentService $paypalService
    ): RedirectResponse|JsonResponse {
        $gateway = $this->findAllowedGateway($gatewayCode);

        $result = match ($gateway->gateway_code) {
            'stripe' => $stripeService->testConnection(),
            'paypal' => $paypalService->testConnection(),
            'cod' => ['success' => true, 'message' => 'Cash on Delivery requires no external API connection.'],
        };

        $gateway->last_tested_at = now();
        $gateway->last_test_status = $result['success'] ? 'success' : 'failed';
        $gateway->last_test_message = mb_substr((string) $result['message'], 0, 250);
        $gateway->save();

        $this->recordAudit($request, $gateway->gateway_code, 'tested', "Connection test {$gateway->last_test_status}", [
            'status' => $gateway->last_test_status,
        ]);

        if ($request->expectsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        return redirect()
            ->route('admin.payment-gateways.index')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function removeConfiguration(Request $request, string $gatewayCode): RedirectResponse
    {
        $gateway = $this->findAllowedGateway($gatewayCode);

        $publicConfig = $gateway->public_configuration ?? [];
        if ($gateway->gateway_code === 'stripe') {
            $publicConfig['publishable_key'] = '';
        } elseif ($gateway->gateway_code === 'paypal') {
            $publicConfig['client_id'] = '';
            $publicConfig['webhook_id'] = '';
        }

        $gateway->encrypted_credentials = null;
        $gateway->public_configuration = $publicConfig;
        $gateway->is_active = false;
        $gateway->last_tested_at = null;
        $gateway->last_test_status = null;
        $gateway->last_test_message = null;
        $gateway->syncConfigurationStatus();
        $gateway->save();

        $this->recordAudit($request, $gateway->gateway_code, 'removed', "Removed stored credentials and deactivated {$gateway->display_name}");

        return redirect()
            ->route('admin.payment-gateways.index')
            ->with('success', "{$gateway->display_name} configuration removed and gateway deactivated. Historical bookings remain intact.");
    }
}
