<form method="POST" action="{{ route('sales') }}" id="checkout-form">
    @csrf
    <div class="form-group">
        <label for="product_code">Scan barcode</label>
        <input type="text" class="form-control" id="product_code" autocomplete="off" autofocus>
    </div>
    <div class="form-group">
        <label for="product-dropdown">Medicine</label>
        <select class="select2 form-control" id="product-dropdown">
            <option value="">Select Product</option>
            @foreach ($products as $product)
                @if ($product->purchase && $product->purchase->quantity > 0)
                    <option value="{{ $product->id }}" data-name="{{ $product->purchase->name }}" data-price="{{ $product->price }}" data-stock="{{ $product->purchase->quantity }}">{{ $product->purchase->name }}</option>
                @endif
            @endforeach
        </select>
    </div>
    <div class="form-group position-relative">
        <label for="medicine-search">Search and add medicine</label>
        <input type="search" class="form-control" id="medicine-search" placeholder="Type a medicine name" autocomplete="off">
        <div id="medicine-results" class="list-group position-absolute w-100" style="z-index: 10;"></div>
    </div>
    <button type="button" class="btn btn-secondary btn-block" id="add-product">Add medicine</button>
    <div class="table-responsive mt-3">
        <table class="table table-sm" id="cart-table">
            <thead><tr><th>Medicine</th><th>Qty</th><th>Price</th><th>Total</th><th></th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
    <div class="form-group">
        <label for="customer_name">Customer name (optional)</label>
        <input type="text" class="form-control" name="customer_name" value="{{ old('customer_name') }}">
    </div>

    <div class="form-group">
        <label>Payment method</label>
        <div class="payment-methods" role="radiogroup" aria-label="Payment method">
            <label class="payment-method active"><input type="radio" name="payment_method" value="cash" checked> <span>Cash</span></label>
            <label class="payment-method"><input type="radio" name="payment_method" value="card"> <span>Card</span></label>
            <label class="payment-method"><input type="radio" name="payment_method" value="jazzcash"> <span>JazzCash</span></label>
            <label class="payment-method"><input type="radio" name="payment_method" value="easypaisa"> <span>EasyPaisa</span></label>
        </div>
    </div>

    <div class="form-group">
        <label for="discount_percent">Discount (%)</label>
        <input type="number" min="0" max="100" step="0.01" class="form-control" name="discount_percent" id="discount_percent" value="0">
        <input type="hidden" name="discount" id="discount" value="0">
    </div>

    <div class="form-group">
        <label for="amount_received">Cash received</label>
        <input type="number" min="0" step="0.01" class="form-control" name="amount_received" id="amount_received" required>
    </div>

    <div class="sale-summary-box">
        <div class="sale-summary-row"><span>Subtotal</span><strong id="subtotal-amount">{{ AppSettings::get('app_currency', '$') }} 0.00</strong></div>
        <div class="sale-summary-row"><span>Discount</span><strong id="discount-amount">{{ AppSettings::get('app_currency', '$') }} 0.00</strong></div>
        <div class="sale-summary-row total"><span>Total</span><strong id="cart-total">{{ AppSettings::get('app_currency', '$') }} 0.00</strong></div>
        <div class="sale-summary-row"><span>Change</span><strong id="change-amount">{{ AppSettings::get('app_currency', '$') }} 0.00</strong></div>
    </div>

    <button type="submit" class="btn btn-primary btn-block" id="checkout-button" disabled>Complete and print bill</button>
</form>
@if ($errors->any())<div class="alert alert-danger mt-3">{{ $errors->first() }}</div>@endif
<style>
    .quantity-control {
        display: inline-flex;
        align-items: center;
        width: 116px;
        border: 1px solid #d9e1ea;
        border-radius: 6px;
        overflow: hidden;
        background: #fff;
    }
    .quantity-control button,
    .quantity-control input {
        height: 30px;
        border: 0;
        border-radius: 0;
    }
    .quantity-control button {
        flex: 0 0 32px;
        padding: 0;
        color: #52606d;
        background: #f4f6f8;
        font-size: 18px;
        line-height: 30px;
    }
    .quantity-control button:hover {
        color: #206bc4;
        background: #e8f1fb;
    }
    .quantity-control input {
        min-width: 0;
        padding: 0 4px;
        color: #1f2933;
        font-weight: 600;
        box-shadow: none;
    }
    .payment-methods {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .payment-method {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 90px;
        padding: 9px 12px;
        border: 1px solid #d9e1ea;
        border-radius: 10px;
        background: #f5f8fa;
        color: #3b4d5c;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .payment-method input {
        margin-right: 6px;
    }
    .payment-method.active {
        background: #dff7f4;
        border-color: #8ad9d4;
        color: #0b6d69;
    }
    .sale-summary-box {
        border: 1px solid #e4edf3;
        border-radius: 12px;
        background: #f8fafb;
        padding: 12px 14px;
        margin-top: 16px;
        margin-bottom: 18px;
    }
    .sale-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        color: #415264;
    }
    .sale-summary-row.total {
        font-size: 18px;
        border-top: 1px solid #e5edf2;
        margin-top: 6px;
        padding-top: 10px;
        color: #1d2b36;
    }
</style>
@push('page-js')
<script>
$(function () {
    const cart = {}, currency = @json(AppSettings::get('app_currency', '$'));
    const money = value => currency + ' ' + Number(value).toFixed(2);
    const medicines = $('#product-dropdown option').filter(function () { return $(this).val(); }).map(function () {
        return {
            id: $(this).val(),
            name: $(this).attr('data-name'),
            price: Number($(this).attr('data-price')),
            stock: Number($(this).attr('data-stock'))
        };
    }).get();

    function calculateTotals() {
        let subtotal = 0;
        Object.keys(cart).forEach(function (id) {
            const item = cart[id];
            subtotal += item.quantity * item.price;
        });

        const discountPercent = parseFloat($('#discount_percent').val()) || 0;
        const discountAmount = subtotal * (discountPercent / 100);
        const total = subtotal - discountAmount;
        const received = parseFloat($('#amount_received').val()) || 0;
        const change = received - total;

        $('#discount').val(discountAmount.toFixed(2));
        $('#subtotal-amount').text(money(subtotal));
        $('#discount-amount').text(money(discountAmount));
        $('#cart-total').text(money(total));
        $('#change-amount').text(money(Math.max(change, 0)));
        return { subtotal, discountAmount, total };
    }

    function renderCart() {
        let subtotal = 0, rows = '';
        Object.keys(cart).forEach(function (id) {
            const item = cart[id], lineTotal = item.quantity * item.price;
            subtotal += lineTotal;
            rows += '<tr><td>' + $('<div>').text(item.name).html() + '<input type="hidden" name="items[' + id + '][product_id]" value="' + id + '"></td>' +
                '<td><div class="quantity-control"><button type="button" class="quantity-minus" data-id="' + id + '" aria-label="Decrease quantity">-</button><input class="cart-quantity text-center" data-id="' + id + '" type="number" min="1" max="' + item.stock + '" name="items[' + id + '][quantity]" value="' + item.quantity + '"><button type="button" class="quantity-plus" data-id="' + id + '" aria-label="Increase quantity">+</button></div></td>' +
                '<td>' + money(item.price) + '</td><td>' + money(lineTotal) + '</td><td><button type="button" class="btn btn-sm btn-danger remove-item" data-id="' + id + '">&times;</button></td></tr>';
        });
        $('#cart-table tbody').html(rows || '<tr><td colspan="5" class="text-muted">No medicines added.</td></tr>');
        $('#subtotal-amount').text(money(subtotal));
        $('#checkout-button').prop('disabled', Object.keys(cart).length === 0);

        const discountPercent = parseFloat($('#discount_percent').val()) || 0;
        const discountAmount = subtotal * (discountPercent / 100);
        $('#discount').val(discountAmount.toFixed(2));
        $('#discount-amount').text(money(discountAmount));
        $('#cart-total').text(money(Math.max(subtotal - discountAmount, 0)));

        const received = parseFloat($('#amount_received').val()) || 0;
        $('#change-amount').text(money(Math.max(received - Math.max(subtotal - discountAmount, 0), 0)));
    }

    function addProduct(product) {
        if (!product || Number(product.stock) < 1) { alert('This medicine is out of stock.'); return; }
        if (!cart[product.id]) cart[product.id] = { name: product.name, price: Number(product.price), stock: Number(product.stock), quantity: 0 };
        if (cart[product.id].quantity >= cart[product.id].stock) { alert('The requested quantity is not available.'); return; }
        cart[product.id].quantity++; renderCart();
    }
    function addSearchedMedicine(product) {
        addProduct(product);
        $('#medicine-search').val('').focus();
        $('#medicine-results').empty();
    }
    $('#medicine-search').on('input', function () {
        const query = $(this).val().trim().toLowerCase();
        const matches = query ? medicines.filter(function (medicine) {
            return medicine.name.toLowerCase().includes(query);
        }).slice(0, 8) : [];
        const results = $('#medicine-results').empty();
        matches.forEach(function (medicine) {
            $('<button type="button" class="list-group-item list-group-item-action">')
                .text(medicine.name)
                .on('click', function () { addSearchedMedicine(medicine); })
                .appendTo(results);
        });
        if (query && !matches.length) {
            $('<div class="list-group-item text-muted">No medicines found</div>').appendTo(results);
        }
    });
    $('#medicine-search').on('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            const query = $(this).val().trim().toLowerCase();
            const match = medicines.find(function (medicine) { return medicine.name.toLowerCase() === query; });
            if (match) addSearchedMedicine(match);
        }
    });
    $('#add-product').on('click', function () { const option = $('#product-dropdown option:selected'); if (option.val()) addProduct({ id: option.val(), name: option.data('name'), price: option.data('price'), stock: option.data('stock') }); });
    $('#product_code').on('change', function () { const input = $(this); if (!input.val()) return; $.post('{{ route('getProductByBarcode') }}', { _token: '{{ csrf_token() }}', barcode: input.val() }).done(function (response) { addProduct(response.product); input.val('').focus(); }).fail(function (xhr) { alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Medicine could not be found.'); input.select(); }); });
    $('#cart-table').on('click', '.remove-item', function () { delete cart[$(this).data('id')]; renderCart(); });
    $('#cart-table').on('change', '.cart-quantity', function () { const id = $(this).data('id'); cart[id].quantity = Math.max(1, Math.min(Number($(this).val()), cart[id].stock)); renderCart(); });
    $('#cart-table').on('click', '.quantity-minus, .quantity-plus', function () {
        const id = $(this).data('id');
        const change = $(this).hasClass('quantity-plus') ? 1 : -1;
        cart[id].quantity = Math.max(1, Math.min(cart[id].quantity + change, cart[id].stock));
        renderCart();
    });
    $('#discount_percent, #amount_received').on('input', function () { renderCart(); });
    $('input[name="payment_method"]').on('change', function () {
        $('.payment-method').removeClass('active');
        $(this).closest('.payment-method').addClass('active');
    });
    window.resetSalesForm = function () {
        Object.keys(cart).forEach(function (id) { delete cart[id]; });
        $('#product_code, #medicine-search, #amount_received').val('');
        $('#medicine-results').empty();
        $('#product-dropdown').val('').trigger('change');
        $('#discount_percent').val(0);
        $('input[name="payment_method"][value="cash"]').prop('checked', true).trigger('change');
        renderCart();
        $('#product_code').focus();
    };
    $(document).on('click', '#add_new', function (event) {
        event.preventDefault();
        window.resetSalesForm();
    });
    $(document).on('reset-sales-form', window.resetSalesForm);
    renderCart();
});
</script>
@endpush
