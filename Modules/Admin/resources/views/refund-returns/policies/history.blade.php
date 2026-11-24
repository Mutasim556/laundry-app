@extends('admin::layouts.app')

@section('content')
<div class="container-fluid py-3" style="max-width: 100%; overflow-x: clip;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0">Policy Version History</h4>
            <div class="small text-muted">Audit trail of draft, published, and archived policy versions.</div>
        </div>
        <a href="{{ route('admin.refund-returns.policies.index') }}" class="btn btn-sm btn-light border">Policy Settings</a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 14px;">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Policy</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Effective</th>
                        <th>Published By</th>
                        <th>Published At</th>
                        <th>Public Summary</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($versions as $v)
                        <tr>
                            <td class="fw-semibold">{{ $v->policy?->name }}</td>
                            <td>v{{ $v->version_number }}</td>
                            <td><span class="badge {{ $v->status === 'published' ? 'bg-success-subtle text-success' : 'bg-light text-dark border' }}">{{ ucfirst($v->status) }}</span></td>
                            <td>{{ $v->effective_at?->format('d M Y, H:i') }}</td>
                            <td>{{ $v->publishedByAdmin?->name ?? '-' }}</td>
                            <td>{{ $v->published_at?->format('d M Y, H:i') ?? '-' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($v->public_text, 90) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($versions->hasPages())
            <div class="card-footer bg-white border-0 py-2">{{ $versions->links() }}</div>
        @endif
    </div>
</div>
@endsection
