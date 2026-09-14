@extends('layouts.app')

@push('page-css')
	<!-- Select2 CSS -->
    <link rel="stylesheet" href="{{asset('jambasangsang/assets/select2/css/select2.min.css')}}">
@endpush


@section('content')
<div class="page-header">
    <div class="page-block">
        <div class="row align-items-center">
            <div class="col-md-9 col-auto">
                <div class="page-header-title">
                    <h3 class="m-b-10">Add Sales</h3>
                </div>
            </div>
            <div class="col-sm-3 col">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="feather icon-home"></i>
                            Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active">Add Sales</li>
                </ul>
            </div>
        </div>
    </div>
</div>
    <div class="row">
        <div class="col-md-8">

            <!-- Recent Sales -->
            <div class="card">
                <div class="card-header">
                            <h5>Invoices</h5>
                    <div class="card-header-right">
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
                                                class="feather icon-plus"></i> expand</span></a></li>
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
                        <table id="datatable-export" class="table table-hover table-center mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Items</th>
                                    <th>Total Price</th>
                                    <th>Cashier</th>
                                    <th>Date</th>
                                    <th class="action-btn">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($transactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction->invoice_number }}</td>
                                            <td>{{ $transaction->lines->sum('quantity') }}</td>
                                            <td>{{ AppSettings::get('app_currency', '$') }} {{ number_format($transaction->total, 2) }}</td>
                                            <td>{{ optional($transaction->user)->name ?: 'Staff' }}</td>
                                            <td>{{ $transaction->created_at->format('d M, Y H:i') }}</td>
                                            <td>
                                                <div class="actions">
                                                    <a href="{{ route('sales.transaction.print', $transaction) }}" class="btn btn-sm btn-info">Print</a>
                                                </div>
                                            </td>
                                        </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /Invoices -->

        </div>
        @can('create-sales')
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Add Sale</h5>
                    <div class="card-header-right">
                        <a href="#" id="add_new" class="btn btn-primary float-right ">Add New</a>
                    </div>
                </div>
                <div class="card-body">
                  @include('sales.create')
                </div>
            </div>
        </div>
        @endcan
    </div>
    <!-- Delete Modal -->
    <x-modals.delete :route="'sales'" :title="'Product Sale'" />
    <!-- /Delete Modal -->


@endsection


@push('page-js')
    <!-- Select2 js-->
    <script src="{{asset('jambasangsang/assets/select2/js/select2.min.js')}}"></script>


    <script>
         $(document).ready(function() {

            $('#datatable-export').on('click', '.editbtn', function() {
                event.preventDefault();
                var id = $(this).data('id');
                var product = $(this).data('product');
                var quantity = $(this).data('quantity');
                $('#edit_id').val(id);
                $(".edit_product").val(product).trigger('change');
                console.log(product)
                $('.edit_quantity').val(quantity);
                $('.btn-block').text("Update Changes");

            });

            $('#add_new').on('click', function() {
                event.preventDefault();
                $('#edit_id').val('');
                $(".edit_product").val('').trigger('change');
                $('.edit_quantity').val(1);
                $('.btn-block').text("Save Changes");

            });
        });
    </script>


@endpush
