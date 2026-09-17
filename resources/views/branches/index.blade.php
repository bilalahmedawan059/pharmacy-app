@extends('layouts.app')

@section('content')
<div class="sale-topbar">
    <div class="sale-topbar__title"><h2>Pharmacy Overview</h2></div>
    <div class="sale-topbar__crumbs"><a href="{{ route('dashboard') }}">Dashboard</a><span>/</span><span>Branches</span></div>
</div>

<div class="row">
    @forelse ($pharmacies as $pharmacy)
        @php($sales = $salesByPharmacy->get($pharmacy->id))
        <div class="col-xl-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">{{ $pharmacy->business_name }}</h5>
                        <small class="text-muted">{{ $pharmacy->city }} · {{ $pharmacy->status }}</small>
                    </div>
                    <span class="badge badge-info">{{ $pharmacy->branches->count() }} branches</span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6"><small class="text-muted d-block">Sales transactions</small><strong>{{ $sales->transaction_count ?? 0 }}</strong></div>
                        <div class="col-6"><small class="text-muted d-block">Sales total</small><strong>{{ number_format((float) ($sales->sales_total ?? 0), 2) }}</strong></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Branch</th><th>City</th><th>Users</th><th>Sales</th><th>Total</th><th>Action</th></tr></thead>
                            <tbody>
                                @forelse ($pharmacy->branches as $branch)
                                    @php($branchSales = $salesByBranch->get($branch->id))
                                    <tr>
                                        <td>{{ $branch->name }}</td>
                                        <td>{{ $branch->city }}</td>
                                        <td>{{ $branch->users_count }}</td>
                                        <td>{{ $branchSales->transaction_count ?? 0 }}</td>
                                        <td>{{ number_format((float) ($branchSales->sales_total ?? 0), 2) }}</td>
                                        <td>
                                            @can('update-branch')
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('branches.edit', $branch) }}">Edit</a>
                                            @else
                                                <span class="text-muted">View only</span>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted">No branches found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">No pharmacies found.</div></div>
    @endforelse
</div>
@endsection