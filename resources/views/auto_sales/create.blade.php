<form method="POST" action="{{ route('sales') }}">
    @csrf
    <div class="row form-row">
        <div class="col-12">
            <div class="form-group">
                <label>Bar Code</label>
                <input type="number" autofocus class="form-control product_code" name="product_code" id="product_code">
            </div>
        </div>
    </div>
</form>
<script>
    $(document).ready(function() {


        $('#product_code').on('input', function() {
            let barcode = $(this).val();

            if (barcode.length === 10) {
                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        $.ajax({
                            url: '{{ route("getProductByBarcode") }}',  // Update this route to your saving route
                            type: 'POST',
                            data: {
                                barcode: barcode,
                                quantity: 1  // Example data; adjust as needed
                            },
                            success: function(response) {
                                if (response.success) {
                                    localStorage.setItem('notification', response.notification);
                                    // Reload the page
                                    window.location.reload();
                                } else {
                                    alert('Failed to save product data: ' + response.message);
                                }
                            },
                            error: function(xhr) {
                                alert('error');
                                console.error('AJAX Error: ', xhr.responseText);
                            }
                        });
            }
        });
        let notification = localStorage.getItem('notification');
        if (notification) {
            // Show the notification (alert or replace with your notification system)
            alert(notification);

            // Clear the notification from localStorage
            localStorage.removeItem('notification');
        }
    });
</script>


{{--<script>--}}
{{--    $(document).ready(function() {--}}
{{--        let previousBarcode = null;--}}
{{--        let timeout;--}}

{{--        $('#product_code').on('input', function() {--}}
{{--            clearTimeout(timeout);--}}

{{--            var barcode = $(this).val();--}}


{{--            if (barcode.length === 10) {--}}
{{--                timeout = setTimeout(function() {--}}
{{--                    if (barcode === previousBarcode) {--}}
{{--                        // If the barcode is the same as the previous one, increase the quantity by one--}}
{{--                        var currentQuantity = parseInt($('#quantity').val());--}}
{{--                        $('#quantity').val(currentQuantity + 1);--}}
{{--                    } else {--}}
{{--                        // If the barcode is different, make an AJAX call to get the product details--}}
{{--                        $.ajax({--}}
{{--                            url: '{{ route("getProductByBarcode") }}',--}}
{{--                            type: 'GET',--}}
{{--                            data: { barcode: barcode },--}}
{{--                            success: function(response) {--}}
{{--                                if (response.success) {--}}
{{--                                    // Update the product dropdown and quantity--}}
{{--                                    $('#product-dropdown').val(response.product.id);--}}
{{--                                    $('#quantity').val(1); // Set initial quantity to 1--}}
{{--                                    previousBarcode = barcode; // Update the previous barcode--}}
{{--                                } else {--}}
{{--                                    alert(response.message);--}}
{{--                                }--}}
{{--                            },--}}
{{--                            error: function(xhr) {--}}
{{--                                console.error(xhr.responseText);--}}
{{--                            }--}}
{{--                        });--}}
{{--                    }--}}

{{--                    // Clear the product_code field after processing--}}
{{--                    $('#product_code').val('');--}}
{{--                }, 2000); // 2-second delay--}}
{{--            }--}}
{{--        });--}}
{{--    });--}}
{{--</script>--}}
