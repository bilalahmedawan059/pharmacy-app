@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="page-block">
        <div class="row align-items-center">
            <div class="col-md-9 col-auto">
                <div class="page-header-title"><h3 class="m-b-10">Batch {{ $batch->batch_number }}</h3></div>
            </div>
            <div class="col-sm-3 col">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('purchases') }}">Purchase Stocks</a></li>
                    <li class="breadcrumb-item active">Batch Details</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <p><strong>Medicine:</strong> {{ $purchase->name }}</p>
        <p><strong>Batch:</strong> {{ $batch->batch_number }}</p>
        <p><strong>Expiry:</strong> {{ $batch->expiry_date->format('m/Y') }}</p>
        <p><strong>Branch:</strong> {{ optional($batch->branch)->name ?: 'Legacy / unassigned' }}</p>
        <p><strong>Received:</strong> {{ $batch->quantity_received }}</p>
        <p><strong>Left:</strong> {{ $batch->quantity_available }}</p>
        <h5 class="mt-4">Sales using this batch</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Invoice</th><th>Date</th><th>Quantity</th><th>Returned</th></tr></thead>
                <tbody>
                @forelse ($batch->saleAllocations as $allocation)
                    <tr>
                        <td>{{ optional(optional($allocation->sale)->transaction)->invoice_number ?: '-' }}</td>
                        <td>{{ optional(optional($allocation->sale)->created_at)->format('d M Y H:i') ?: '-' }}</td>
                        <td>{{ $allocation->quantity }}</td>
                        <td>{{ $allocation->returned_quantity }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">No sales have used this batch.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
