@extends('layouts.app')

@push('page-header')
    <div class="col-sm-12">
        <h3 class="page-title">{{ $title }}</h3>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('products') }}">Products</a></li>
            <li class="breadcrumb-item active">{{ $title }}</li>
        </ul>
    </div>
@endpush

@section('content')
<div class="page-header">
    <div class="page-block">
        <div class="row align-items-center">
            <div class="col-md-9 col-auto"><div class="page-header-title"><h3 class="m-b-10">{{ $title }}</h3></div></div>
            <div class="col-sm-3 col">
                <ul class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li><li class="breadcrumb-item active">{{ $title }}</li></ul>
            </div>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-body">
        @if (isset($days))
            <form method="GET" action="{{ route('near-expiry') }}" class="form-inline mb-3">
                <label for="expiry-days" class="mr-2">Within</label>
                <select class="form-control mr-2" id="expiry-days" name="days">
                    @foreach ([30, 60, 90] as $option)
                        <option value="{{ $option }}" {{ (int) $days === $option ? 'selected' : '' }}>{{ $option }} days</option>
                    @endforeach
                </select>
                <button class="btn btn-primary" type="submit">Filter</button>
            </form>
        @endif
        <div class="table-responsive">
            <table id="datatable-export" data-export-type="{{ isset($days) ? 'near-expiry' : 'expired' }}" @if (isset($days)) data-export-days="{{ $days }}" @endif class="js-searchable-table js-exportable-table table table-striped table-bordered table-hover">
                <thead><tr><th>Medicine</th><th>Batch</th><th>Expiry</th><th>Quantity left</th></tr></thead>
                <tbody>
                @foreach ($batches as $batch)
                    <tr class="{{ $batch->expiry_date->lt(today()) ? 'text-danger' : ($batch->expiry_date->lte(today()->addDays(30)) ? 'text-warning' : '') }}">
                        <td>{{ optional($batch->purchase)->name ?: 'Medicine' }}</td>
                        <td>{{ $batch->batch_number }}</td>
                        <td>{{ $batch->expiry_date->format('m/Y') }}</td>
                        <td>{{ $batch->quantity_available }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3"><x-pagination :paginator="$batches" /></div>
    </div>
</div>
@endsection
