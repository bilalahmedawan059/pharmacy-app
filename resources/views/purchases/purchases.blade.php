@extends('layouts.app')


@section('content')
    <div class="page-header">
        <div class="page-block">
            <div class="row align-items-center">
                <div class="col-md-9 col-auto">
                    <div class="page-header-title">
                        <h3 class="m-b-10">Purchase Stocks</h3>
                    </div>
                </div>
                <div class="col-sm-3 col">
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="feather icon-home"></i>
                                Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active">Purchase Stocks</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header">
                    <h5>Purchase Stocks</h5>
                    <div class="card-header-right">
                        <a href="{{ route('add-purchase') }}" class="btn btn-primary float-right">Add New</a>
                        <div class="btn-group card-option">
                            <button type="button" class="btn dropdown-toggle btn-icon" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <i class="feather icon-more-horizontal"></i>
                            </button>
                            <ul class="list-unstyled card-option dropdown-menu dropdown-menu-right">
                                <li class="dropdown-item full-card"><a href="#!"><span><i class="feather icon-maximize"></i>
                                            maximize</span><span style="display:none"><i class="feather icon-minimize"></i>
                                            Restore</span></a>
                                </li>
                                <li class="dropdown-item minimize-card"><a href="#!"><span><i
                                                class="feather icon-minus"></i> collapse</span><span style="display:none"><i
                                                class="feather icon-plus"></i> expand</span></a>
                                </li>
                                <li class="dropdown-item reload-card"><a href="#!"><i class="feather icon-refresh-cw"></i>
                                        reload</a></li>
                                <li class="dropdown-item close-card"><a href="#!"><i class="feather icon-trash"></i>
                                        remove</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="datatable-export" data-export-type="purchases" class="js-searchable-table js-exportable-table table table-hover table-center mb-0">
                            <thead>
                                <tr>
                                    <th>Medicine Name</th>
                                    <th>Medicine Category</th>
                                    <th>Purchase Price</th>
                                    <th>Stock / Batches</th>
                                    <th>Supplier</th>
                                    <th>Expire Date</th>
                                    <th class="action-btn">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchases as $purchase)
                                    <tr>
                                        <td>
                                            @if (!empty($purchase->image))
                                                <span class="avatar avatar-sm mr-2">
                                                    <img class="avatar-img" width="30"
                                                        src="{{ asset('storage/purchases/' . $purchase->image) }}"
                                                        alt="product image">
                                                </span>
                                            @endif
                                            {{ $purchase->name }}
                                        </td>
                                        <td>{{ $purchase->category->name }}</td>
                                        <td>{{ AppSettings::get('app_currency', '$') }}{{ $purchase->price }}</td>
                                        <td>
                                            {{ $purchase->batches->filter(function ($batch) { return $batch->quantity_available > 0 && $batch->expiry_date->gte(today()); })->sum('quantity_available') }}
                                            <details class="mt-2">
                                                <summary>View batches</summary>
                                                <table class="table table-sm mt-2 mb-0">
                                                    <thead><tr><th>Batch</th><th>Expiry</th><th>Received</th><th>Left</th><th></th></tr></thead>
                                                    <tbody>
                                                    @php($sellFirstBatch = $purchase->batches->first(function ($batch) { return $batch->quantity_available > 0 && $batch->expiry_date->gte(today()); }))
                                                    @foreach ($purchase->batches as $batch)
                                                        @php($batchClass = $batch->expiry_date->lt(today()) ? 'text-danger' : ($batch->expiry_date->lte(today()->addDays(30)) ? 'text-warning' : ''))
                                                        <tr class="{{ $batchClass }}">
                                                            <td><a href="{{ route('purchases.batch', [$purchase, $batch]) }}">{{ $batch->batch_number }}</a></td>
                                                            <td>{{ $batch->expiry_date->format('m/Y') }}</td>
                                                            <td>{{ $batch->quantity_received }}</td>
                                                            <td>{{ $batch->quantity_available }}</td>
                                                            <td>@if ($sellFirstBatch && $sellFirstBatch->id === $batch->id)<strong>Sells first</strong>@endif</td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </details>
                                        </td>
                                        <td>{{ $purchase->supplier->name }}</td>
                                        <td>{{ date_format(date_create($purchase->expiry_date), 'd M, Y') }}</td>
                                        <td>
                                            <div class="actions">
                                                <a class="btn btn-sm btn-info"
                                                    href="{{ route('edit-purchase', $purchase) }}">
                                                    <i class="fe fe-pencil"></i> Edit
                                                </a>
                                                <a data-id="{{ $purchase->id }}" href="javascript:void(0);"
                                                    class="btn btn-sm btn-danger deletebtn" data-toggle="modal">
                                                    <i class="fe fe-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3"><x-pagination :paginator="$purchases" /></div>
                </div>
            </div>
            <!-- /Recent Orders -->
        </div>
    </div>
    <!-- Delete Modal -->
    <x-modals.delete :route="'delete-stock'" :title="'Purchase'" />
    <!-- /Delete Modal -->
@endsection

@push('page-js')
    <!-- Select2 JS -->
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
@endpush
