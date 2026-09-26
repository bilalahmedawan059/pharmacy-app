@extends('layouts.app')

@push('page-css')
	<!-- Select2 CSS -->
    <link rel="stylesheet" href="{{ asset('assets/backend/select2/css/select2.min.css') }}">
@endpush


@section('content')
<div class="sale-topbar">
    <div class="sale-topbar__title">
        <h2>Add Sales</h2>
    </div>
    <div class="sale-topbar__crumbs">
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <span>/</span>
        <span>Add Sales</span>
    </div>
</div> 
<div class="pos-sales-shell">
    <div class="pos-sales-main">
        @canany(['create-sales', 'update-sales'])
        <div class="pos-sale-card card">
            <div class="pos-sale-card__header card-header">
                <div class="pos-sale-tab-group">
                    <button type="button" class="pos-sale-tab {{ $returnTransaction ? '' : 'active' }}" data-sale-panel="new-sale-panel" onclick="document.querySelectorAll('[data-sale-panel]').forEach(function(button){button.classList.remove('active');}); this.classList.add('active'); document.getElementById('new-sale-panel').style.display='block'; document.getElementById('return-sale-panel').style.display='none';">New Sale</button>
                    @can('update-sales')
                    <button type="button" class="pos-sale-tab {{ $returnTransaction ? 'active' : '' }}" data-sale-panel="return-sale-panel" onclick="document.querySelectorAll('[data-sale-panel]').forEach(function(button){button.classList.remove('active');}); this.classList.add('active'); document.getElementById('new-sale-panel').style.display='none'; document.getElementById('return-sale-panel').style.display='block';">Return</button>
                    @endcan
                </div>
                <div class="pos-sale-header-actions">
                    <button type="button" id="add_new" class="pos-sale-add-btn">Add New</button>
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
                        </ul>
                    </div>
                </div>
            </div>
            <div class="pos-sale-card__body card-body">
                @can('create-sales')
                <div id="new-sale-panel" style="display:{{ $returnTransaction ? 'none' : 'block' }}">
                    @include('sales.create')
                </div>
                @endcan
                @can('update-sales')
                <div id="return-sale-panel" style="display:{{ $returnTransaction ? 'block' : 'none' }}">
                    <form method="GET" action="{{ route('sales') }}" class="mb-3">
                        <div class="form-group">
                            <label for="return-invoice">Invoice</label>
                            <select id="return-invoice" name="return_transaction_id" class="select2 form-control" required>
                                <option value="">Select invoice</option>
                                @foreach ($transactions as $transaction)
                                    <option value="{{ $transaction->id }}" {{ optional($returnTransaction)->id === $transaction->id ? 'selected' : '' }}>{{ $transaction->invoice_number }} ({{ $transaction->created_at->format('d M Y') }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-secondary btn-block">Load invoice items</button>
                    </form>
                    @if ($returnTransaction)
                        <form method="POST" action="{{ route('sales.transaction.return', $returnTransaction) }}" id="return-sale-form">
                            @csrf
                            <div class="table-responsive">
                                <table class="table">
                                    <thead><tr><th>Medicine</th><th>Available</th><th>Return quantity</th></tr></thead>
                                    <tbody>
                                    @foreach ($returnTransaction->lines as $line)
                                        @php($availableQuantity = $line->quantity - $line->returned_quantity)
                                        @if ($availableQuantity > 0)
                                            <tr>
                                                <td>{{ optional($line->product->purchase)->name ?: 'Medicine' }}</td>
                                                <td>{{ $availableQuantity }}</td>
                                                <td><input type="number" class="form-control" name="returns[{{ $line->id }}]" min="0" max="{{ $availableQuantity }}" value="0"></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if ($errors->has('returns'))<div class="alert alert-danger mt-3">{{ $errors->first('returns') }}</div>@endif
                            <button type="submit" class="btn btn-primary btn-block">Process selected return</button>
                        </form>
                    @else
                        <p class="text-muted">Select an invoice to view its returnable items.</p>
                    @endif
                </div>
                @endcan
            </div>
        </div>
        @endcanany
    </div>

    <aside class="pos-sales-sidebar">
        <div class="pos-sidebar-card">
            <div class="pos-sidebar-card__header">
                <h5>Invoices</h5>
                <div id="invoice-export-actions" class="pos-invoice-export-actions"></div>
            </div>
            <div class="p-3 pb-0">
                <input type="search" id="invoice-search" class="form-control" placeholder="Search invoices..." aria-label="Search invoices">
            </div>
            <div class="pos-invoice-list js-exportable-list" data-export-type="transactions" data-export-target="#invoice-export-actions">
                @foreach ($transactions as $transaction)
                    <div class="pos-invoice-item" data-invoice-search="{{ $transaction->invoice_number }} {{ optional($transaction->user)->name }} {{ $transaction->created_at->format('d M Y') }}">
                        <div class="pos-invoice-item__meta">
                            <span class="pos-invoice-number">{{ $transaction->invoice_number }}</span>
                            <span class="pos-invoice-date">{{ $transaction->created_at->format('d M, Y') }}</span>
                        </div>
                        <div class="pos-invoice-item__row">
                            <span>{{ $transaction->lines->sum(function ($line) { return $line->quantity - $line->returned_quantity; }) }} Items</span>
                            <span>{{ AppSettings::get('app_currency', '$') }} {{ number_format($transaction->total, 2) }}</span>
                        </div>
                        <div class="pos-invoice-item__row muted">
                            <span>{{ optional($transaction->user)->name ?: 'Staff' }}</span>
                            <a href="{{ route('sales.transaction.print', $transaction) }}" class="pos-print-link">Print</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </aside>
</div>

<x-modals.delete :route="'sales'" :title="'Product Sale'" />
@endsection


@push('page-css')
    <style>
        .pos-sales-shell {
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(300px, 0.9fr);
            gap: 22px;
            align-items: start;
            margin-top: 14px;
        }

        .sale-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding: 10px 0 14px;
            border-bottom: 1px solid #e9edf4;
        }

        .sale-topbar__title h2 {
            margin: 0;
            font-size: 34px;
            font-weight: 700;
            color: #1d2b36;
        }

        .sale-topbar__crumbs {
            font-size: 13px;
            color: #6d7d8a;
        }

        .sale-topbar__crumbs a {
            color: #3e6078;
            font-weight: 600;
        }

        .sale-topbar__crumbs span + span {
            margin-left: 8px;
        }

        .pos-sale-card,
        .pos-sidebar-card {
            background: #f9fbfc;
            border: 1px solid #dfe9f2;
            border-radius: 18px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
        }

        .pos-sale-card {
            overflow: hidden;
        }

        .pos-sale-card.full-card {
            display: flex;
            flex-direction: column;
        }

        .pos-sale-card.full-card .pos-sale-card__header {
            flex: 0 0 auto;
        }

        .pos-sale-card.full-card .pos-sale-card__body {
            min-height: 0;
            overflow-y: auto;
        }

        html.sales-fullscreen-lock,
        body.sales-fullscreen-lock,
        body.sales-fullscreen-lock main,
        body.sales-fullscreen-lock .pcoded-wrapper,
        body.sales-fullscreen-lock .pcoded-content,
        body.sales-fullscreen-lock .pcoded-inner-content,
        body.sales-fullscreen-lock .main-body,
        body.sales-fullscreen-lock .page-wrapper {
            overflow: hidden !important;
        }

        .pos-sale-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 20px;
            background: #f3f7f9;
            border-bottom: 1px solid #e5edf3;
        }

        .pos-sale-tab-group {
            display: inline-flex;
            background: #e8f5f3;
            border-radius: 14px;
            padding: 4px;
            border: 1px solid #d7efeb;
        }

        .pos-sale-tab {
            border: 0;
            background: transparent;
            color: #4d5c69;
            padding: 8px 18px;
            border-radius: 10px;
            font-weight: 600;
        }

        .pos-sale-tab.active {
            background: #ffffff;
            color: #127f79;
            box-shadow: 0 2px 10px rgba(31, 105, 96, 0.08);
        }

        .pos-sale-add-btn {
            border: 1px solid #8ad9d4;
            background: #d6f3ef;
            color: #0d6f69;
            border-radius: 10px;
            padding: 9px 16px;
            font-weight: 700;
        }

        .pos-sale-card__body {
            padding: 18px 18px 12px;
            background: #ffffff;
        }

        .pos-sale-card__body form {
            padding: 0;
        }

        .pos-sale-card__body .form-group {
            margin-bottom: 14px;
        }

        .pos-sale-card__body label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #5a6d7a;
            margin-bottom: 8px;
        }

        .pos-sale-card__body .form-control,
        .pos-sale-card__body .select2-selection,
        .pos-sale-card__body .select2-container--default .select2-selection--single {
            min-height: 42px;
            border-radius: 10px;
            border: 1px solid #d7e3eb;
            background: #f9fbfc;
            color: #1d2b36;
        }

        .pos-sale-card__body .btn-block,
        .pos-sale-card__body #checkout-button,
        .pos-sale-card__body #add-product {
            border-radius: 10px;
            min-height: 42px;
            font-weight: 700;
            background: linear-gradient(135deg, #3ec2b7, #1d8e9a);
            border: none;
            color: #fff;
        }

        .pos-sale-card__body #add-product {
            margin-top: 4px;
        }

        .pos-sale-card__body .table {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e4edf3;
        }

        .pos-sale-card__body .table thead th {
            background: #f3f7f9;
            color: #516574;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1px solid #dfeaf2;
        }

        .pos-sale-card__body .table td {
            vertical-align: middle;
            padding: 10px 12px;
            color: #23313a;
        }

        .pos-sale-card__body .quantity-control {
            width: 116px;
            border: 1px solid #dce7ef;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .pos-sale-card__body .quantity-control button {
            background: #f2f7f9;
            color: #4d5c69;
            border: 0;
            font-size: 20px;
            line-height: 1;
        }

        .pos-sale-card__body .quantity-control input {
            border: 0;
            box-shadow: none;
            background: transparent;
            text-align: center;
            font-weight: 700;
        }

        .pos-sale-card__body .text-muted {
            color: #6d7d8a !important;
        }

        .pos-sales-sidebar {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .pos-sidebar-card {
            padding: 0;
            overflow: hidden;
        }

        .pos-sidebar-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 18px;
            border-bottom: 1px solid #e7edf3;
            background: #f3f7f9;
        }

        .pos-sidebar-card__header h5 {
            margin: 0;
            color: #21313e;
            font-weight: 700;
        }

        .pos-invoice-export-actions {
            display: flex;
            flex: 0 0 auto;
            gap: 6px;
        }

        .pos-invoice-export-actions .btn {
            min-height: 30px;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 11px;
            line-height: 1.2;
            white-space: nowrap;
        }

        .pos-invoice-list {
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #ffffff;
        }

        .pos-invoice-item {
            background: #f6fafb;
            border: 1px solid #e1ecf3;
            border-radius: 12px;
            padding: 12px 14px;
        }

        .pos-invoice-item__meta,
        .pos-invoice-item__row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .pos-invoice-item__meta {
            margin-bottom: 8px;
            font-size: 12px;
            color: #516574;
        }

        .pos-invoice-number {
            font-weight: 700;
            color: #1d2b36;
        }

        .pos-invoice-item__row {
            font-size: 13px;
            color: #243943;
        }

        .pos-invoice-item__row.muted {
            color: #6a7c8a;
        }

        .pos-print-link {
            color: #1d8e9a;
            font-weight: 700;
        }

        @media (max-width: 1110px) {
            .pos-sales-shell {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sale-topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .sale-topbar__title h2 {
                font-size: 28px;
            }

            .pos-sale-card__header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
@endpush

@push('page-js')
    <script src="{{ asset('assets/backend/select2/js/select2.min.js') }}"></script>
    <script>
         $(document).ready(function() {
            $('.select2').select2({ width: '100%' });

            $(document).on('click', '[data-sale-panel]', function (event) {
                event.preventDefault();
                var panel = $(this).attr('data-sale-panel');
                $('[data-sale-panel]').removeClass('active');
                $(this).addClass('active');
                $('#new-sale-panel, #return-sale-panel').hide();
                $('#' + panel).show();
            });

            $('#invoice-search').on('input', function() {
                var query = this.value.trim().toLocaleLowerCase();
                $('.pos-invoice-item').each(function() {
                    $(this).toggle($(this).attr('data-invoice-search').toLocaleLowerCase().includes(query));
                });
            });

            $('#datatable-export').on('click', '.editbtn', function() {
                event.preventDefault();
                var id = $(this).data('id');
                var product = $(this).data('product');
                var quantity = $(this).data('quantity');
                $('#edit_id').val(id);
                $(".edit_product").val(product).trigger('change');
                $('.edit_quantity').val(quantity);
                $('.btn-block').text("Update Changes");
            });

            $('.pos-sale-card .full-card').on('click', function() {
                var isMaximized = $('.pos-sale-card').hasClass('full-card');
                $('html, body').toggleClass('sales-fullscreen-lock', isMaximized);
                $('main, .pcoded-wrapper, .pcoded-content, .pcoded-inner-content, .main-body, .page-wrapper')
                    .toggleClass('sales-fullscreen-lock', isMaximized);
            });
        });
    </script>
@endpush
