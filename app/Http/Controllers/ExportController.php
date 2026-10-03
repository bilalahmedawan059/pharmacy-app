<?php

namespace App\Http\Controllers;

use App\Exports\RecordsExport;
use App\Models\Branch;
use App\Models\Batch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sales;
use App\Models\SaleTransaction;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;

class ExportController extends Controller
{
    public function download(Request $request, $dataset, $format)
    {
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        $headings = [];
        $rows = [];
        $title = 'Export';

        switch ($dataset) {
            case 'products':
                $this->authorize('view-products');
                $products = Product::with('purchase.category', 'purchase.batches')->get();
                $headings = ['Medicine', 'Product Code', 'Category', 'Price', 'Quantity', 'Discount', 'Expiry Date', 'Batch Number', 'Batch Expiry'];
                $rows = $this->productRows($products);
                $title = 'Products';
                break;
            case 'expired':
                $this->authorize('view-expired-products');
                $batches = Batch::with('purchase')->whereDate('expiry_date', '<', Carbon::today())
                    ->where('quantity_available', '>', 0)->orderBy('expiry_date')->get();
                $headings = ['Medicine', 'Batch Number', 'Expiry', 'Quantity Left'];
                $rows = $this->expiryBatchRows($batches);
                $title = 'Expired Batches';
                break;
            case 'near-expiry':
                $this->authorize('view-expired-products');
                $days = (int) $request->validate(['days' => 'required|in:30,60,90'])['days'];
                $batches = Batch::with('purchase')->where('quantity_available', '>', 0)
                    ->whereDate('expiry_date', '>=', Carbon::today())
                    ->whereDate('expiry_date', '<=', Carbon::today()->addDays($days))
                    ->orderBy('expiry_date')->get();
                $headings = ['Medicine', 'Batch Number', 'Expiry', 'Quantity Left'];
                $rows = $this->expiryBatchRows($batches);
                $title = 'Near Expiry Batches';
                break;
            case 'outstock':
                $this->authorize('view-outstock-products');
                $purchases = Purchase::with(['category', 'batches'])->where('quantity', '<=', 0)->get();
                $headings = ['Medicine', 'Category', 'Price', 'Quantity', 'Discount', 'Expiry Date', 'Batch Number', 'Batch Expiry'];
                $rows = $this->purchaseRows($purchases, false);
                $title = 'Out of Stock';
                break;
            case 'purchases':
                $this->authorize('view-purchase');
                $purchases = Purchase::with(['category', 'supplier', 'batches'])->get();
                $headings = ['Medicine', 'Category', 'Purchase Price', 'Quantity', 'Supplier', 'Expiry Date', 'Batch Number', 'Batch Expiry'];
                $rows = $this->purchaseRows($purchases, true);
                $title = 'Purchases';
                break;
            case 'suppliers':
                $this->authorize('view-supplier');
                $rows = Supplier::orderBy('name')->get()->map(function ($supplier) {
                    return [$supplier->product, $supplier->name, $supplier->phone, $supplier->email, $supplier->address, $supplier->company];
                })->all();
                $headings = ['Product', 'Name', 'Phone', 'Email', 'Address', 'Company'];
                $title = 'Suppliers';
                break;
            case 'categories':
                $this->authorize('view-category');
                $rows = Category::orderBy('name')->get()->map(function ($category) {
                    return [$category->name, optional($category->created_at)->format('Y-m-d')];
                })->all();
                $headings = ['Category', 'Created Date'];
                $title = 'Categories';
                break;
            case 'users':
                $this->authorize('view-users');
                $users = User::with('roles')
                    ->when(!auth()->user()->hasRole('super-admin'), function ($query) {
                        $query->where('pharmacy_id', auth()->user()->pharmacy_id);
                    })
                    ->orderBy('name')->get();
                $rows = $users->map(function ($user) {
                    return [$user->name, $user->email, $user->getRoleNames()->implode(', '), optional($user->created_at)->format('Y-m-d')];
                })->all();
                $headings = ['Name', 'Email', 'Roles', 'Created Date'];
                $title = 'Users';
                break;
            case 'roles':
                abort_unless(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-role'), 403);
                $roles = Role::with('permissions')
                    ->when(!auth()->user()->hasRole('super-admin'), function ($query) {
                        $query->where('pharmacy_id', auth()->user()->pharmacy_id);
                    })
                    ->orderBy('name')->get();
                $rows = $roles->map(function ($role) {
                    return [$role->name, $role->permissions->pluck('name')->implode(', ')];
                })->all();
                $headings = ['Role', 'Permissions'];
                $title = 'Roles';
                break;
            case 'permissions':
                abort_unless(auth()->user()->hasRole('super-admin'), 403);
                $rows = Permission::orderBy('name')->get()->map(function ($permission) {
                    return [$permission->name, optional($permission->created_at)->format('Y-m-d')];
                })->all();
                $headings = ['Permission', 'Created Date'];
                $title = 'Permissions';
                break;
            case 'backups':
                $rows = [];
                foreach (config('backup.backup.destination.disks', []) as $diskName) {
                    $disk = Storage::disk($diskName);
                    foreach ($disk->allFiles() as $file) {
                        if (substr($file, -4) !== '.zip' || !$disk->exists($file)) {
                            continue;
                        }

                        $rows[] = [
                            $diskName,
                            str_replace('backups/', '', $file),
                            Carbon::createFromTimestamp($disk->lastModified($file))->format('Y-m-d H:i:s'),
                            round($disk->size($file) / 1048576, 2),
                        ];
                    }
                }
                $headings = ['Disk', 'Backup File', 'Modified At', 'Size (MB)'];
                $title = 'Backups';
                break;
            case 'branches':
                $this->authorize('view-branches');
                $branches = Branch::with('pharmacy')->withCount('users')
                    ->when(!auth()->user()->hasRole('super-admin'), function ($query) {
                        $query->where('pharmacy_id', auth()->user()->pharmacy_id);
                    })
                    ->orderBy('name')->get();
                $rows = $branches->map(function ($branch) {
                    return [optional($branch->pharmacy)->business_name, $branch->name, $branch->city, $branch->address, $branch->contact, $branch->license_number, $branch->status, $branch->users_count];
                })->all();
                $headings = ['Pharmacy', 'Branch', 'City', 'Address', 'Contact', 'License Number', 'Status', 'Users'];
                $title = 'Branches';
                break;
            case 'sales':
                $this->authorize('view-sales');
                $sales = Sales::with('product.purchase', 'allocations.batch')->latest()->get();
                $headings = ['Medicine', 'Quantity', 'Total Price', 'Date', 'Batch Number', 'Batch Expiry'];
                $rows = $this->salesRows($sales);
                $title = 'Sales';
                break;
            case 'dashboard-sales':
                abort_unless(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-dashboard') || auth()->user()->can('view-sales'), 403);
                $filters = $request->validate([
                    'pharmacy_id' => ['nullable', 'integer'],
                    'branch_id' => ['nullable', 'integer'],
                ]);
                $salesQuery = Sales::with([
                    'product.purchase',
                    'allocations.batch',
                    'pharmacy',
                    'transaction.user.pharmacy',
                    'transaction.user.branch',
                ])->latest();
                if (!empty($filters['pharmacy_id'])) {
                    $salesQuery->where('pharmacy_id', $filters['pharmacy_id']);
                }
                if (!empty($filters['branch_id'])) {
                    $salesQuery->whereHas('transaction.user', function ($query) use ($filters) {
                        $query->where('branch_id', $filters['branch_id']);
                    });
                }
                $sales = $salesQuery->get();
                $headings = ['Medicine', 'Pharmacy', 'Branch', 'Quantity', 'Total Price', 'Date', 'Batch Number', 'Batch Expiry'];
                $rows = $this->dashboardSalesRows($sales);
                $title = 'Sales';
                break;
            case 'transactions':
                $this->authorize('view-sales');
                $transactions = SaleTransaction::with('user')->latest()->get();
                $rows = $transactions->map(function ($transaction) {
                    return [
                        $transaction->invoice_number,
                        $transaction->customer_name,
                        optional($transaction->user)->name,
                        $transaction->subtotal,
                        $transaction->discount,
                        $transaction->total,
                        $transaction->amount_received,
                        $transaction->change_amount,
                        $transaction->payment_method,
                        $transaction->payment_status,
                        optional($transaction->created_at)->format('Y-m-d H:i:s'),
                    ];
                })->all();
                $headings = ['Invoice', 'Customer', 'Staff', 'Subtotal', 'Discount', 'Total', 'Amount Received', 'Change', 'Payment Method', 'Status', 'Date'];
                $title = 'Invoices';
                break;
            case 'report-sales':
            case 'report-products':
            case 'report-purchases':
                $this->authorize('view-reports');
                $dates = $request->validate([
                    'from_date' => ['required', 'date'],
                    'to_date' => ['required', 'date', 'after_or_equal:from_date'],
                ]);
                if ($dataset === 'report-sales') {
                    $sales = Sales::with('product.purchase', 'allocations.batch')
                        ->whereBetween(DB::raw('DATE(created_at)'), [$dates['from_date'], $dates['to_date']])
                        ->latest()->get();
                    $headings = ['Medicine', 'Quantity', 'Total Price', 'Date', 'Batch Number', 'Batch Expiry'];
                    $rows = $this->salesRows($sales);
                    $title = 'Sales Report';
                } elseif ($dataset === 'report-products') {
                    $products = Product::with('purchase.category', 'purchase.batches')
                        ->whereBetween(DB::raw('DATE(created_at)'), [$dates['from_date'], $dates['to_date']])->get();
                    $headings = ['Medicine', 'Category', 'Price', 'Quantity', 'Discount', 'Expiry Date', 'Batch Number', 'Batch Expiry'];
                    $rows = $this->reportProductRows($products);
                    $title = 'Products Report';
                } else {
                    $purchases = Purchase::with(['category', 'supplier', 'batches'])
                        ->whereBetween(DB::raw('DATE(created_at)'), [$dates['from_date'], $dates['to_date']])->get();
                    $headings = ['Medicine', 'Category', 'Purchase Price', 'Quantity', 'Supplier', 'Expiry Date', 'Batch Number', 'Batch Expiry'];
                    $rows = $this->purchaseRows($purchases, true);
                    $title = 'Purchases Report';
                }
                break;
            default:
                abort(404);
        }

        $filename = strtolower(str_replace(' ', '-', $title)) . '.' . $format;

        return Excel::download(new RecordsExport($headings, $rows, $title), $filename);
    }

    private function productRows($products)
    {
        return $products->filter(function ($product) {
            return $product->purchase;
        })->map(function ($product) {
            return [
                $product->purchase->name,
                $product->product_code,
                optional($product->purchase->category)->name,
                $product->price,
                $product->purchase->quantity,
                $product->discount,
                $this->formatDate($product->purchase->expiry_date),
                $product->purchase->batches->pluck('batch_number')->implode(', '),
                $product->purchase->batches->map(function ($batch) { return $this->formatDate($batch->expiry_date); })->implode(', '),
            ];
        })->values()->all();
    }

    private function reportProductRows($products)
    {
        return $products->filter(function ($product) {
            return $product->purchase;
        })->map(function ($product) {
            return [
                $product->purchase->name,
                optional($product->purchase->category)->name,
                $product->price,
                $product->purchase->quantity,
                $product->discount,
                $this->formatDate($product->purchase->expiry_date),
                $product->purchase->batches->pluck('batch_number')->implode(', '),
                $product->purchase->batches->map(function ($batch) { return $this->formatDate($batch->expiry_date); })->implode(', '),
            ];
        })->values()->all();
    }

    private function purchaseRows($purchases, $includeSupplier)
    {
        return $purchases->map(function ($purchase) use ($includeSupplier) {
            $row = [
                $purchase->name,
                optional($purchase->category)->name,
                $purchase->price,
                $purchase->quantity,
            ];

            if ($includeSupplier) {
                $row[] = optional($purchase->supplier)->name;
            } else {
                $row[] = $purchase->discount;
            }

            $row[] = $this->formatDate($purchase->expiry_date);
            $row[] = $purchase->batches->pluck('batch_number')->implode(', ');
            $row[] = $purchase->batches->map(function ($batch) { return $this->formatDate($batch->expiry_date); })->implode(', ');

            return $row;
        })->all();
    }

    private function salesRows($sales)
    {
        return $sales->filter(function ($sale) {
            return $sale->product && $sale->product->purchase;
        })->map(function ($sale) {
            return [
                $sale->product->purchase->name,
                $sale->quantity,
                $sale->total_price,
                optional($sale->created_at)->format('Y-m-d H:i:s'),
                $sale->allocations->map(function ($allocation) { return optional($allocation->batch)->batch_number; })->filter()->implode(', '),
                $sale->allocations->map(function ($allocation) { return $this->formatDate(optional($allocation->batch)->expiry_date); })->filter()->implode(', '),
            ];
        })->values()->all();
    }

    private function dashboardSalesRows($sales)
    {
        return $sales->filter(function ($sale) {
            return $sale->product && $sale->product->purchase;
        })->map(function ($sale) {
            $user = optional($sale->transaction)->user;
            $pharmacy = $sale->pharmacy ?: optional($user)->pharmacy;
            $branch = optional($user)->branch;

            return [
                $sale->product->purchase->name,
                optional($pharmacy)->business_name,
                optional($branch)->name,
                $sale->quantity,
                $sale->total_price,
                optional($sale->created_at)->format('Y-m-d H:i:s'),
                $sale->allocations->map(function ($allocation) { return optional($allocation->batch)->batch_number; })->filter()->implode(', '),
                $sale->allocations->map(function ($allocation) { return $this->formatDate(optional($allocation->batch)->expiry_date); })->filter()->implode(', '),
            ];
        })->values()->all();
    }

    private function expiryBatchRows($batches)
    {
        return $batches->map(function (Batch $batch) {
            return [
                optional($batch->purchase)->name,
                $batch->batch_number,
                $this->formatDate($batch->expiry_date),
                $batch->quantity_available,
            ];
        })->all();
    }

    private function formatDate($value)
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }
}