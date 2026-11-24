<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\RefundReturnPolicy;
use App\Models\RefundReturnPolicyVersion;
use App\Services\PolicyResolutionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Admin\Models\ItemCategory;
use Modules\Admin\Models\Store;

class RefundReturnPolicyController extends Controller
{
    public function __construct(
        protected PolicyResolutionService $policyService
    ) {}

    public function index(): View
    {
        $this->policyService->ensureDefaultDraftPolicy(Auth::guard('admin')->id());

        $policies = RefundReturnPolicy::query()
            ->with(['store:id,name', 'category:id,name', 'versions.publishedByAdmin:id,name'])
            ->orderBy('id')
            ->get();

        $stores = Store::query()->orderBy('name')->get(['id', 'name']);
        $categories = ItemCategory::query()->orderBy('name')->get(['id', 'name']);

        return view('admin::refund-returns.policies.index', [
            'policies' => $policies,
            'stores' => $stores,
            'categories' => $categories,
            'defaultRules' => RefundReturnPolicyVersion::defaultRules(),
        ]);
    }

    public function history(): View
    {
        $versions = RefundReturnPolicyVersion::query()
            ->with(['policy.store:id,name', 'policy.category:id,name', 'publishedByAdmin:id,name'])
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin::refund-returns.policies.history', [
            'versions' => $versions,
        ]);
    }

    public function storeDraft(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'policy_id' => ['nullable', 'integer', 'exists:refund_return_policies,id'],
            'name' => ['required', 'string', 'max:150'],
            'country_code' => ['nullable', 'string', 'max:8'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'category_id' => ['nullable', 'integer', 'exists:item_categories,id'],
            'public_text' => ['required', 'string', 'max:5000'],
            'legal_disclaimer_note' => ['nullable', 'string', 'max:1000'],
            'effective_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:effective_at'],
            'policy_enabled' => ['nullable', 'boolean'],
            'customer_cancel_enabled' => ['nullable', 'boolean'],
            'cancel_cutoff_hours_before_collection' => ['required', 'integer', 'min:0', 'max:168'],
            'rewash_enabled' => ['nullable', 'boolean'],
            'rewash_deadline_hours_after_delivery' => ['required', 'integer', 'min:1', 'max:720'],
            'rewash_pickup_included' => ['nullable', 'boolean'],
            'claims_enabled' => ['nullable', 'boolean'],
            'damage_claim_deadline_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'missing_claim_deadline_hours' => ['required', 'integer', 'min:1', 'max:720'],
            'evidence_required' => ['nullable', 'boolean'],
            'manual_review_always_required' => ['nullable', 'boolean'],
            'fixed_deduction_minor' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'auto_approve_max_minor' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $adminId = (int) Auth::guard('admin')->id();
        $defaults = RefundReturnPolicyVersion::defaultRules();

        $rules = array_replace_recursive($defaults, [
            'policy_enabled' => $request->boolean('policy_enabled'),
            'customer_cancel_enabled' => $request->boolean('customer_cancel_enabled'),
            'cancel_cutoff_hours_before_collection' => (int) $validated['cancel_cutoff_hours_before_collection'],
            'rewash_enabled' => $request->boolean('rewash_enabled'),
            'rewash_deadline_hours_after_delivery' => (int) $validated['rewash_deadline_hours_after_delivery'],
            'rewash_pickup_included' => $request->boolean('rewash_pickup_included'),
            'claims_enabled' => $request->boolean('claims_enabled'),
            'damage_claim_deadline_hours' => (int) $validated['damage_claim_deadline_hours'],
            'missing_claim_deadline_hours' => (int) $validated['missing_claim_deadline_hours'],
            'evidence_required' => $request->boolean('evidence_required'),
            'manual_review_always_required' => $request->boolean('manual_review_always_required'),
            'fixed_deduction_minor' => (int) ($validated['fixed_deduction_minor'] ?? 0),
            'auto_approve_max_minor' => (int) ($validated['auto_approve_max_minor'] ?? 0),
        ]);

        DB::transaction(function () use ($validated, $rules, $adminId) {
            if (! empty($validated['policy_id'])) {
                $policy = RefundReturnPolicy::findOrFail($validated['policy_id']);
                $policy->update([
                    'name' => $validated['name'],
                    'country_code' => ! empty($validated['country_code']) ? strtoupper(trim($validated['country_code'])) : null,
                    'store_id' => $validated['store_id'] ?? null,
                    'category_id' => $validated['category_id'] ?? null,
                ]);
            } else {
                $policy = RefundReturnPolicy::create([
                    'code' => 'POL-' . strtoupper(substr(md5((string) microtime(true)), 0, 8)),
                    'name' => $validated['name'],
                    'country_code' => ! empty($validated['country_code']) ? strtoupper(trim($validated['country_code'])) : null,
                    'store_id' => $validated['store_id'] ?? null,
                    'category_id' => $validated['category_id'] ?? null,
                    'is_active' => true,
                    'created_by_admin_id' => $adminId,
                ]);
            }

            $nextVersion = ((int) $policy->versions()->max('version_number')) + 1;

            RefundReturnPolicyVersion::create([
                'policy_id' => $policy->id,
                'version_number' => $nextVersion,
                'status' => RefundReturnPolicyVersion::STATUS_DRAFT,
                'rules_json' => $rules,
                'public_text' => $validated['public_text'],
                'legal_disclaimer_note' => $validated['legal_disclaimer_note'] ?? null,
                'effective_at' => $validated['effective_at'] ?? now(),
                'expires_at' => $validated['expires_at'] ?? null,
            ]);
        });

        return back()->with('success', 'Saved new draft policy version for review.');
    }

    public function publish(RefundReturnPolicyVersion $version): RedirectResponse
    {
        if ($version->status === RefundReturnPolicyVersion::STATUS_PUBLISHED) {
            return back()->with('success', "Policy v{$version->version_number} is already published.");
        }

        $adminId = (int) Auth::guard('admin')->id();

        DB::transaction(function () use ($version, $adminId) {
            RefundReturnPolicyVersion::query()
                ->where('policy_id', $version->policy_id)
                ->where('status', RefundReturnPolicyVersion::STATUS_PUBLISHED)
                ->update(['status' => RefundReturnPolicyVersion::STATUS_ARCHIVED]);

            $version->update([
                'status' => RefundReturnPolicyVersion::STATUS_PUBLISHED,
                'published_by_admin_id' => $adminId,
                'published_at' => now(),
                'effective_at' => $version->effective_at ?? now(),
            ]);
        });

        return back()->with('success', "Published policy version v{$version->version_number}.");
    }
}
