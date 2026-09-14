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
        <label for="amount_received">Cash received</label>
        <input type="number" min="0" step="0.01" class="form-control" name="amount_received" id="amount_received" required>
    </div>
    <div class="d-flex justify-content-between font-weight-bold mb-3"><span>Total</span><span id="cart-total">{{ AppSettings::get('app_currency', '$') }} 0.00</span></div>
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
    function renderCart() {
        let total = 0, rows = '';
        Object.keys(cart).forEach(function (id) {
            const item = cart[id], lineTotal = item.quantity * item.price;
            total += lineTotal;
            rows += '<tr><td>' + $('<div>').text(item.name).html() + '<input type="hidden" name="items[' + id + '][product_id]" value="' + id + '"></td>' +
                '<td><div class="quantity-control"><button type="button" class="quantity-minus" data-id="' + id + '" aria-label="Decrease quantity">-</button><input class="cart-quantity text-center" data-id="' + id + '" type="number" min="1" max="' + item.stock + '" name="items[' + id + '][quantity]" value="' + item.quantity + '"><button type="button" class="quantity-plus" data-id="' + id + '" aria-label="Increase quantity">+</button></div></td>' +
                '<td>' + money(item.price) + '</td><td>' + money(lineTotal) + '</td><td><button type="button" class="btn btn-sm btn-danger remove-item" data-id="' + id + '">&times;</button></td></tr>';
        });
        $('#cart-table tbody').html(rows || '<tr><td colspan="5" class="text-muted">No medicines added.</td></tr>');
        $('#cart-total').text(money(total));
        $('#checkout-button').prop('disabled', Object.keys(cart).length === 0);
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
    renderCart();
});
</script>
@endpush
