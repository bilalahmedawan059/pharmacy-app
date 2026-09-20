<?php

namespace App\Http\Controllers;

use App\Events\MedicineOutStock;
use App\Models\Sales;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\SaleTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class SalesController extends Controller
{

    public function getProductByBarcode(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
        ]);

        $product = Product::with('purchase')
            ->where('product_code', $request->barcode)
            ->first();

        if (!$product || !$product->purchase) {
            return response()->json(['success' => false, 'message' => 'Medicine not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->purchase->name,
                'price' => (float) $product->price,
                'stock' => (int) $product->purchase->quantity,
            ],
        ]);
    }



    public function index()
    {
        $this->authorize('view-sales');
        $title = "sales";
        $products = Product::with('purchase')->get();
        $sales = Sales::with('product.purchase')->latest()->get();
        $transactions = SaleTransaction::with('lines.product.purchase', 'user')->latest()->get();
        $returnTransaction = null;
        if (request()->filled('return_transaction_id')) {
            $returnTransaction = SaleTransaction::with('lines.product.purchase')
                ->find(request('return_transaction_id'));
        }
        return view('sales.sales', compact(
            'title',
            'products',
            'sales',
            'transactions',
            'returnTransaction'
        ));
    }


    public function index_Auto()
    {
        $title = "sales";
        return $this->index();
    }


    public function store(Request $request)
    {
        $this->authorize('create-sales');
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'amount_received' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'customer_name' => 'nullable|string|max:150',
        ]);

        $items = collect($request->input('items'))
            ->groupBy('product_id')
            ->map(function ($lines) {
                return ['product_id' => (int) $lines->first()['product_id'], 'quantity' => $lines->sum('quantity')];
            })->values();

        try {
            $transaction = DB::transaction(function () use ($items, $request) {
            $subtotal = 0;
            $prepared = [];

            foreach ($items as $item) {
                $product = Product::with('purchase')->findOrFail($item['product_id']);
                $purchase = Purchase::whereKey($product->purchase_id)->lockForUpdate()->firstOrFail();

                if ($purchase->quantity < $item['quantity']) {
                    throw new \DomainException($purchase->name . ' has only ' . $purchase->quantity . ' left in stock.');
                }

                $lineTotal = round($item['quantity'] * (float) $product->price, 2);
                $subtotal += $lineTotal;
                $prepared[] = compact('product', 'purchase', 'item', 'lineTotal');
            }

            $discountPercent = (float) ($request->discount_percent ?? 0);
            $discountAmount = round($subtotal * ($discountPercent / 100), 2);
            $total = round($subtotal - $discountAmount, 2);
            $received = round((float) $request->amount_received, 2);

            if ($received < $total) {
                throw new \DomainException('Amount received cannot be less than the invoice total.');
            }

            $invoice = SaleTransaction::create([
                'invoice_number' => $this->invoiceNumber(),
                'user_id' => optional($request->user())->id,
                'customer_name' => $request->customer_name,
                'subtotal' => round($subtotal, 2),
                'discount' => round($discountAmount, 2),
                'total' => $total,
                'amount_received' => $received,
                'change_amount' => round($received - $total, 2),
                'payment_method' => $request->payment_method ?? 'cash',
                'payment_status' => 'paid',
            ]);

            foreach ($prepared as $line) {
                Sales::create([
                    'sale_transaction_id' => $invoice->id,
                    'product_id' => $line['product']->id,
                    'quantity' => $line['item']['quantity'],
                    'total_price' => $line['lineTotal'],
                ]);

                $line['purchase']->decrement('quantity', $line['item']['quantity']);
                if ($line['purchase']->quantity <= 1) {
                    event(new MedicineOutStock($line['purchase']->fresh()));
                }
            }

                return $invoice;
            });
        } catch (\DomainException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        }

        return redirect()->route('sales.transaction.print', $transaction);
    }

    private function invoiceNumber()
    {
        do {
            $number = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (SaleTransaction::where('invoice_number', $number)->exists());

        return $number;
    }

    public function print(SaleTransaction $transaction)
    {
        $this->authorize('view-sales');
        $transaction->load('lines.product.purchase', 'user');
        return view('sales.receipt', compact('transaction'));
    }

    public function return(Request $request, SaleTransaction $transaction)
    {
        $this->authorize('update-sales');
        $request->validate([
            'returns' => 'required|array|min:1',
            'returns.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::transaction(function () use ($request, $transaction) {
                $transaction = SaleTransaction::whereKey($transaction->id)
                    ->with('lines.product')
                    ->lockForUpdate()
                    ->firstOrFail();
                $lines = $transaction->lines()->lockForUpdate()->get()->keyBy('id');
                $returnedSubtotal = 0;

                foreach ($request->input('returns', []) as $lineId => $returnQuantity) {
                    $returnQuantity = (int) $returnQuantity;
                    if ($returnQuantity === 0) {
                        continue;
                    }

                    $line = $lines->get((int) $lineId);
                    if (!$line) {
                        throw new \DomainException('The selected sale line is invalid.');
                    }
                    $availableQuantity = $line->quantity - $line->returned_quantity;
                    if ($returnQuantity > $availableQuantity) {
                        throw new \DomainException('Return quantity cannot exceed the quantity sold.');
                    }

                    $purchase = Purchase::whereKey($line->product->purchase_id)->lockForUpdate()->firstOrFail();
                    $unitPrice = $line->quantity > 0 ? (float) $line->total_price / $line->quantity : 0;
                    $line->increment('returned_quantity', $returnQuantity);
                    $purchase->increment('quantity', $returnQuantity);
                    $returnedSubtotal += $unitPrice * $returnQuantity;
                }

                if ($returnedSubtotal <= 0) {
                    throw new \DomainException('Select at least one item to return.');
                }

                $originalSubtotal = (float) $transaction->subtotal;
                $discountRate = $originalSubtotal > 0 ? (float) $transaction->discount / $originalSubtotal : 0;
                $remainingSubtotal = $lines->sum(function ($line) {
                    $unitPrice = $line->quantity > 0 ? (float) $line->total_price / $line->quantity : 0;
                    return $unitPrice * ($line->quantity - $line->returned_quantity);
                });
                $discount = round($remainingSubtotal * $discountRate, 2);
                $total = round($remainingSubtotal - $discount, 2);

                $transaction->update([
                    'subtotal' => round($remainingSubtotal, 2),
                    'discount' => $discount,
                    'total' => $total,
                    'change_amount' => round(max((float) $transaction->amount_received - $total, 0), 2),
                    'payment_status' => $total <= 0 ? 'refunded' : 'partially_returned',
                ]);
            });
        } catch (\DomainException $exception) {
            return back()->withErrors(['returns' => $exception->getMessage()]);
        }

        return back()->with(['message' => 'Items returned and stock restored successfully.', 'alert-type' => 'success']);
    }

    public function destroy(Request $request)
    {
        $this->authorize('destroy-sale');
        $sale = Sales::findOrFail($request->id);
        $sale->delete();
        $notification = array(
            'message' => "Sales has been deleted",
            'alert-type' => 'success'
        );
        return back()->with($notification);
    }
}
