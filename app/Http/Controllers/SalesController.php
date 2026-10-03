<?php

namespace App\Http\Controllers;

use App\Events\MedicineOutStock;
use App\Models\Batch;
use App\Models\Sales;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\SaleBatchAllocation;
use App\Models\SaleTransaction;
use Carbon\Carbon;
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
        $user = request()->user();
        $canSelectBranch = $user->hasAnyRole(['super-admin', 'admin', 'branch-manager']);
        $branches = $this->availableBranches($user, $canSelectBranch);
        $branch = $this->resolveBranch(request(), $branches, $canSelectBranch);
        $branchId = optional($branch)->id;
        $branchScopeActive = $branches->isNotEmpty();
        $title = "sales";
        $products = Product::with('purchase')->get();
        $medicineOptions = $products->filter(function ($product) {
            return $product->purchase && $product->purchase->quantity > 0;
        })->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->purchase->name,
                'price' => (float) $product->price,
                'stock' => (int) $product->purchase->quantity,
            ];
        })->values();
        $sales = Sales::with('product.purchase')->whereHas('transaction', function ($query) use ($branchId, $branchScopeActive) {
            if ($branchScopeActive) {
                $query->where('branch_id', $branchId);
            }
        })->latest()->get();
        $transactions = SaleTransaction::with('lines.product.purchase', 'user')->when($branchScopeActive, function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })->latest()->paginate(10)->withQueryString();
        $returnTransaction = null;
        if (request()->filled('return_transaction_id')) {
            $returnTransaction = SaleTransaction::with('lines.product.purchase')->when($branchScopeActive, function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->find(request('return_transaction_id'));
            abort_unless($returnTransaction, 404);
        }
        return view('sales.sales', compact(
            'title',
            'products',
            'medicineOptions',
            'sales',
            'transactions',
            'returnTransaction',
            'branches',
            'branchId',
            'canSelectBranch'
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
            'branch_id' => 'nullable|integer',
        ]);
        $user = $request->user();
        $canSelectBranch = $user->hasAnyRole(['super-admin', 'admin', 'branch-manager']);
        $branches = $this->availableBranches($user, $canSelectBranch);
        $branch = $this->resolveBranch($request, $branches, $canSelectBranch);
        if ($branches->isNotEmpty() && !$branch) {
            return back()->withInput()->withErrors(['branch_id' => 'Select a branch before completing the sale.']);
        }

        $items = collect($request->input('items'))
            ->groupBy('product_id')
            ->map(function ($lines) {
                return ['product_id' => (int) $lines->first()['product_id'], 'quantity' => $lines->sum('quantity')];
            })->values();

        try {
            $transaction = DB::transaction(function () use ($items, $request, $branch) {
                $subtotal = 0;
                $prepared = [];

                foreach ($items as $item) {
                    $product = Product::with('purchase')->findOrFail($item['product_id']);
                    $needed = (int) $item['quantity'];
                    $availableBatches = Batch::where('purchase_id', $product->purchase_id)
                        ->where('quantity_available', '>', 0)
                        ->whereDate('expiry_date', '>=', Carbon::today())
                        ->orderBy('expiry_date')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    $availableStock = $availableBatches->sum('quantity_available');
                    if ($availableStock < $needed) {
                        throw new \DomainException($product->purchase->name . ' has only ' . $availableStock . ' left in stock.');
                    }

                    $remaining = $needed;
                    $allocations = [];
                    foreach ($availableBatches as $batch) {
                        if ($remaining <= 0) {
                            break;
                        }

                        $allocationQty = min($remaining, (int) $batch->quantity_available);
                        $allocations[] = [
                            'batch_id' => $batch->id,
                            'quantity' => $allocationQty,
                        ];
                        $remaining -= $allocationQty;
                    }

                    $lineTotal = round($needed * (float) $product->price, 2);
                    $subtotal += $lineTotal;
                    $prepared[] = compact('product', 'item', 'lineTotal', 'allocations');
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
                    'branch_id' => optional($branch)->id,
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
                    $saleLine = Sales::create([
                        'sale_transaction_id' => $invoice->id,
                        'product_id' => $line['product']->id,
                        'quantity' => $line['item']['quantity'],
                        'total_price' => $line['lineTotal'],
                    ]);

                    foreach ($line['allocations'] as $allocation) {
                        $batch = Batch::whereKey($allocation['batch_id'])->lockForUpdate()->firstOrFail();
                        $batch->decrement('quantity_available', $allocation['quantity']);
                        SaleBatchAllocation::create([
                            'sale_id' => $saleLine->id,
                            'batch_id' => $batch->id,
                            'quantity' => $allocation['quantity'],
                            'returned_quantity' => 0,
                        ]);
                    }

                    $remainingPurchase = $line['product']->purchase->fresh();
                    if ($remainingPurchase && $remainingPurchase->quantity <= 1) {
                        event(new MedicineOutStock($remainingPurchase));
                    }
                }

                return $invoice;
            });
        } catch (\DomainException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        }

        return redirect()->route('sales.transaction.print', array_merge(
            ['transaction' => $transaction],
            $branch ? ['branch_id' => $branch->id] : []
        ));
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
        $this->assertTransactionBranch($transaction, request());
        $transaction->load('lines.product.purchase', 'user');
        return view('sales.receipt', compact('transaction'));
    }

    public function return(Request $request, SaleTransaction $transaction)
    {
        $this->authorize('update-sales');
        $this->assertTransactionBranch($transaction, $request);
        $request->validate([
            'returns' => 'required|array|min:1',
            'returns.*' => 'nullable|integer|min:0',
        ]);

        try {
            DB::transaction(function () use ($request, $transaction) {
                $transaction = SaleTransaction::whereKey($transaction->id)
                    ->with('lines.allocations.batch')
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

                    $availableToReturn = $line->quantity - $line->returned_quantity;
                    if ($returnQuantity > $availableToReturn) {
                        throw new \DomainException('Return quantity cannot exceed the quantity sold.');
                    }

                    $remaining = $returnQuantity;
                    foreach ($line->allocations()->with('batch')->orderBy('id')->get() as $allocation) {
                        if ($remaining <= 0) {
                            break;
                        }

                        $allocatable = $allocation->quantity - $allocation->returned_quantity;
                        if ($allocatable <= 0) {
                            continue;
                        }

                        $takeFromAllocation = min($remaining, $allocatable);
                        $allocation->increment('returned_quantity', $takeFromAllocation);
                        $allocation->batch()->first()->increment('quantity_available', $takeFromAllocation);
                        $remaining -= $takeFromAllocation;
                    }

                    if ($remaining > 0) {
                        throw new \DomainException('Return quantity exceeds the batch allocations for this item.');
                    }

                    $unitPrice = $line->quantity > 0 ? (float) $line->total_price / $line->quantity : 0;
                    $line->increment('returned_quantity', $returnQuantity);
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

    private function availableBranches($user, bool $canSelectBranch)
    {
        $query = Branch::query();
        if (!$user->hasRole('super-admin')) {
            $query->where('pharmacy_id', $user->pharmacy_id ?: 0);
        }

        if (!$canSelectBranch) {
            $query->whereKey($user->branch_id ?: 0);
        }

        return $query->orderBy('name')->get();
    }

    private function resolveBranch(Request $request, $branches, bool $canSelectBranch): ?Branch
    {
        $user = $request->user();
        $branchId = $canSelectBranch
            ? ($request->input('branch_id') ?: $user->branch_id ?: optional($branches->first())->id)
            : $user->branch_id;

        return $branchId ? $branches->firstWhere('id', (int) $branchId) ?: abort(404) : null;
    }

    private function assertTransactionBranch(SaleTransaction $transaction, Request $request): void
    {
        $user = $request->user();
        $canSelectBranch = $user->hasAnyRole(['super-admin', 'admin', 'branch-manager']);
        $branches = $this->availableBranches($user, $canSelectBranch);
        $branch = $this->resolveBranch($request, $branches, $canSelectBranch);

        if ($branches->isNotEmpty() && (int) $transaction->branch_id !== (int) optional($branch)->id) {
            abort(404);
        }
    }
}
