@extends('layouts.app')

@push('page-css')
	<!-- Select2 CSS -->
    <link rel="stylesheet" href="{{asset('jambasangsang/assets/select2/css/select2.min.css')}}">
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
        @can('create-sales')
        <div class="pos-sale-card">
            <div class="pos-sale-card__header">
                <div class="pos-sale-tab-group">
                    <button type="button" class="pos-sale-tab active">New Sale</button>
                    <button type="button" class="pos-sale-tab">Return</button>
                </div>
                <button type="button" id="add_new" class="pos-sale-add-btn">Add New</button>
            </div>
            <div class="pos-sale-card__body">
                @include('sales.create')
            </div>
        </div>
        @endcan
    </div>

    <aside class="pos-sales-sidebar">
        <div class="pos-sidebar-card">
            <div class="pos-sidebar-card__header">
                <h5>Invoices</h5>
            </div>
            <div class="pos-invoice-list">
                @foreach ($transactions as $transaction)
                    <div class="pos-invoice-item">
                        <div class="pos-invoice-item__meta">
                            <span class="pos-invoice-number">{{ $transaction->invoice_number }}</span>
                            <span class="pos-invoice-date">{{ $transaction->created_at->format('d M, Y') }}</span>
                        </div>
                        <div class="pos-invoice-item__row">
                            <span>{{ $transaction->lines->sum('quantity') }} Items</span>
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

        <div class="pos-quick-card">
            <div class="pos-quick-card__header">
                <h6>Quick Alerts</h6>
                <a href="#!">View Inventory</a>
            </div>
            <div class="pos-quick-list">
                <div class="pos-quick-item low">
                    <div>
                        <strong>Panadol Syrup 60ml</strong>
                        <small>Low stock</small>
                    </div>
                    <span>5</span>
                </div>
                <div class="pos-quick-item low">
                    <div>
                        <strong>Augmentin 625mg</strong>
                        <small>Low stock</small>
                    </div>
                    <span>4</span>
                </div>
                <div class="pos-quick-item low">
                    <div>
                        <strong>Insulin Glargine</strong>
                        <small>Low stock</small>
                    </div>
                    <span>2</span>
                </div>
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
        .pos-sidebar-card,
        .pos-quick-card {
            background: #f9fbfc;
            border: 1px solid #dfe9f2;
            border-radius: 18px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
        }

        .pos-sale-card {
            overflow: hidden;
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

        .pos-sidebar-card,
        .pos-quick-card {
            padding: 0;
            overflow: hidden;
        }

        .pos-sidebar-card__header,
        .pos-quick-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 18px;
            border-bottom: 1px solid #e7edf3;
            background: #f3f7f9;
        }

        .pos-sidebar-card__header h5,
        .pos-quick-card__header h6 {
            margin: 0;
            color: #21313e;
            font-weight: 700;
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

        .pos-quick-card__header a {
            color: #3a8d8e;
            font-size: 12px;
            font-weight: 700;
        }

        .pos-quick-list {
            background: #fff;
            padding: 12px;
        }

        .pos-quick-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border: 1px solid #ebf1f5;
            border-radius: 12px;
            background: #f7fafb;
            margin-bottom: 10px;
        }

        .pos-quick-item:last-child {
            margin-bottom: 0;
        }

        .pos-quick-item div {
            display: flex;
            flex-direction: column;
        }

        .pos-quick-item strong {
            font-size: 13px;
            color: #1d2b36;
        }

        .pos-quick-item small {
            font-size: 11px;
            color: #7f8f9e;
        }

        .pos-quick-item span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #ffe7e5;
            color: #d85248;
            font-weight: 800;
            font-size: 12px;
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
    <script src="{{asset('jambasangsang/assets/select2/js/select2.min.js')}}"></script>
    <script>
         $(document).ready(function() {
            $('.select2').select2({ width: '100%' });

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
