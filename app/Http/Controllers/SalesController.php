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
        $title = "sales";
        $products = Product::with('purchase')->get();
        $sales = Sales::with('product.purchase')->latest()->get();
        $transactions = SaleTransaction::with('lines.product.purchase', 'user')->latest()->get();

        return view('sales.sales', compact(
            'title',
            'products',
            'sales',
            'transactions'
        ));
    }


    public function index_Auto()
    {
        $title = "sales";
        return $this->index();
    }


    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'amount_received' => 'required|numeric|min:0',
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

            $total = round($subtotal, 2);
            $received = round((float) $request->amount_received, 2);

            if ($received < $total) {
                throw new \DomainException('Amount received cannot be less than the invoice total.');
            }

            $invoice = SaleTransaction::create([
                'invoice_number' => $this->invoiceNumber(),
                'user_id' => optional($request->user())->id,
                'customer_name' => $request->customer_name,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $total,
                'amount_received' => $received,
                'change_amount' => round($received - $total, 2),
                'payment_method' => 'cash',
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
        $transaction->load('lines.product.purchase', 'user');
        return view('sales.receipt', compact('transaction'));
    }

    public function destroy(Request $request)
    {
        $sale = Sales::find($request->id);
        $sale->delete();
        $notification = array(
            'message' => "Sales has been deleted",
            'alert-type' => 'success'
        );
        return back()->with($notification);
    }
}
