<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseInvoiceItemTax;
use App\Models\User;
use App\Models\Warehouse;
use App\Http\Requests\StorePurchaseInvoiceRequest;
use App\Http\Requests\UpdatePurchaseInvoiceRequest;
use Zerp\ProductService\Models\ProductServiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Events\CreatePurchaseInvoice;
use App\Events\UpdatePurchaseInvoice;
use App\Events\DestroyPurchaseInvoice;
use App\Events\PostPurchaseInvoice;
use App\Events\EditPurchaseInvoice;


class PurchaseInvoiceController extends Controller
{
    private function checkInvoiceAccess(PurchaseInvoice $purchaseInvoice)
    {
        if(Auth::user()->can('manage-any-purchase-invoices')) {
            return true;
        } elseif(Auth::user()->can('manage-own-purchase-invoices')) {
            if($purchaseInvoice->creator_id != Auth::id() && $purchaseInvoice->vendor_id != Auth::id()) {
                return false;
            }
            if($purchaseInvoice->creator_id != Auth::id() && Auth::user()->type == 'vendor' && $purchaseInvoice->status == 'draft') {
                return false;
            }
            return true;
        }
        return false;
    }
    public function index(Request $request)
    {
        if(Auth::user()->can('manage-purchase-invoices')){
            $query = PurchaseInvoice::with(['vendor', 'items'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-purchase-invoices')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-purchase-invoices')) {
                        $q->where('creator_id', Auth::id())->orWhere('vendor_id',Auth::id());
                        if(Auth::user()->type == 'vendor') {
                            $q->where('status','!=', 'draft');
                        }
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

            // Apply filters
            if ($request->vendor_id) {
                $query->where('vendor_id', $request->vendor_id);
            }
            if ($request->warehouse_id) {
                $query->where('warehouse_id', $request->warehouse_id);
            }
            if ($request->status) {
                if ($request->status === 'overdue') {
                    $query->where('due_date', '<', now())
                    ->whereIn('status', ['posted', 'partial'])
                    ->where('balance_amount', '>', 0);
                } else {
                    $query->where('status', $request->status);
                }
            }
            if ($request->search) {
                $query->where('invoice_number', 'like', '%' . likeEscape($request->search) . '%');
            }
            if ($request->date_range) {
                $dates = explode(' - ', $request->date_range);
                if (count($dates) === 2) {
                    $query->whereBetween('invoice_date', [$dates[0], $dates[1]]);
                }
            }

        // Apply sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['invoice_number', 'invoice_date', 'due_date', 'subtotal', 'tax_amount', 'total_amount', 'balance_amount', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSortFields) || empty($sortField)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, $sortDirection);

        $perPage = perPage();
        $invoices = $query->paginate($perPage);
        $vendors = User::where('type', 'vendor')->select('id', 'name', 'email')->where('created_by', creatorId())->get();
        $warehouses = Warehouse::where('is_active', true)->select('id', 'name')->where('created_by', creatorId())->get();

            return Inertia::render('Purchase/Index', [
                'invoices' => $invoices,
                'vendors' => $vendors,
                'warehouses' => $warehouses,
                'filters' => $request->only(['vendor_id', 'warehouse_id', 'status', 'search', 'date_range'])
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if(Auth::user()->can('create-purchase-invoices')){
            $vendors = User::where('type', 'vendor')->select('id', 'name', 'email')->where('created_by', creatorId())->get();
            $products = ProductServiceItem::select('id', 'name', 'sku', 'purchase_price', 'tax_ids', 'unit', 'type')
            ->where('is_active', true)->where('created_by', creatorId())
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'purchase_price' => $product->purchase_price,
                    'unit' => $product->unit,
                    'type' => $product->type,
                    'taxes' => $product->taxes->map(function ($tax) {
                        return [
                            'id' => $tax->id,
                            'tax_name' => $tax->tax_name,
                            'rate' => $tax->rate
                        ];
                    })
                ];
            });

            $warehouses = Warehouse::where('is_active', true)->select('id', 'name', 'address')->where('created_by', creatorId())->get();

            return Inertia::render('Purchase/Create', [
                'vendors' => $vendors,
                'products' => $products,
                'warehouses' => $warehouses,
                'modules' => [
                    'recurringinvoicebill' => module_is_active('RecurringInvoiceBill')
                ]
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StorePurchaseInvoiceRequest $request)
    {
        if(Auth::user()->can('create-purchase-invoices')){
            $totals = $this->calculateTotals($request->items);

            $invoice = new PurchaseInvoice();
            $invoice->invoice_date = $request->invoice_date;
            $invoice->due_date = $request->due_date;
            $invoice->vendor_id = $request->vendor_id;
            $invoice->warehouse_id = $request->warehouse_id;
            $invoice->payment_terms = $request->payment_terms;
            $invoice->notes = $request->notes;
            $invoice->subtotal = $totals['subtotal'];
            $invoice->tax_amount = $totals['tax_amount'];
            $invoice->discount_amount = $totals['discount_amount'];
            $invoice->total_amount = $totals['total_amount'];
            $invoice->balance_amount = $totals['total_amount'];
            $invoice->creator_id = Auth::id();
            $invoice->created_by = creatorId();
            $invoice->save();

            // Create invoice items
            $this->createInvoiceItems($invoice->id, $request->items);

            try {
                CreatePurchaseInvoice::dispatch($request, $invoice);
            } catch (\Throwable $th) {
                return back()->with('error', $th->getMessage());
            }

            return redirect()->route('purchase-invoices.index')->with('success', __('The purchase invoice has been created successfully.'));

        }
        else{
            return redirect()->route('purchase-invoices.index')->with('error', __('Permission denied'));
        }
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        if(Auth::user()->can('view-purchase-invoices') && $purchaseInvoice->created_by == creatorId()){
            if(!$this->checkInvoiceAccess($purchaseInvoice)) {
                return redirect()->route('purchase-invoices.index')->with('error', __('Permission denied'));
            }

            $purchaseInvoice->load(['vendor', 'vendorDetails', 'items.product', 'items.taxes', 'warehouse']);

            return Inertia::render('Purchase/View', [
                'invoice' => $purchaseInvoice
            ]);
        }
        else{
            return redirect()->route('purchase-invoices.index')->with('error', __('Permission denied'));
        }
    }

    public function edit(PurchaseInvoice $purchaseInvoice)
    {
        if(Auth::user()->can('edit-purchase-invoices') && $purchaseInvoice->created_by == creatorId()){
            if(!$this->checkInvoiceAccess($purchaseInvoice)) {
                return redirect()->route('purchase-invoices.index')->with('error', __('Permission denied'));
            }

            if ($purchaseInvoice->status != 'draft') {
                return redirect()->route('purchase-invoices.index')->with('error', __('Cannot update posted invoice.'));
            }

            $purchaseInvoice->load(['items.taxes']);

            EditPurchaseInvoice::dispatch($purchaseInvoice);

            $vendors = User::where('type', 'vendor')->select('id', 'name', 'email')->where('created_by', creatorId())->get();
            $products = ProductServiceItem::select('id', 'name', 'sku', 'purchase_price', 'tax_ids', 'unit', 'type')
                ->where('is_active', true)->where('created_by', creatorId())
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'purchase_price' => $product->purchase_price,
                        'unit' => $product->unit,
                        'type' => $product->type,
                        'taxes' => $product->taxes->map(function ($tax) {
                            return [
                                'id' => $tax->id,
                                'tax_name' => $tax->tax_name,
                                'rate' => $tax->rate
                            ];
                        })
                    ];
                });

            $warehouses = Warehouse::where('is_active', true)->select('id', 'name', 'address')->where('created_by', creatorId())->get();

            return Inertia::render('Purchase/Edit', [
                'invoice' => $purchaseInvoice,
                'vendors' => $vendors,
                'products' => $products,
                'warehouses' => $warehouses,
                'modules' => [
                    'recurringinvoicebill' => module_is_active('RecurringInvoiceBill')
                ]
            ]);
        }
        else{
            return redirect()->route('purchase-invoices.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdatePurchaseInvoiceRequest $request, PurchaseInvoice $purchaseInvoice)
    {
        if(Auth::user()->can('edit-purchase-invoices') && $purchaseInvoice->created_by == creatorId()){
            if ($purchaseInvoice->status != 'draft') {
                return redirect()->route('purchase-invoices.index')->with('error', __('Cannot update posted invoice.'));
            }
            $totals = $this->calculateTotals($request->items);

            $purchaseInvoice->invoice_date = $request->invoice_date;
            $purchaseInvoice->due_date = $request->due_date;
            $purchaseInvoice->vendor_id = $request->vendor_id;
            $purchaseInvoice->warehouse_id = $request->warehouse_id;
            $purchaseInvoice->payment_terms = $request->payment_terms;
            $purchaseInvoice->notes = $request->notes;
            $purchaseInvoice->subtotal = $totals['subtotal'];
            $purchaseInvoice->tax_amount = $totals['tax_amount'];
            $purchaseInvoice->discount_amount = $totals['discount_amount'];
            $purchaseInvoice->total_amount = $totals['total_amount'];
            $purchaseInvoice->balance_amount = $totals['total_amount'];
            $purchaseInvoice->save();

            // Delete existing items and recreate
            $purchaseInvoice->items()->delete();
            $this->createInvoiceItems($purchaseInvoice->id, $request->items);

            // Dispatch event for packages to handle their fields
            UpdatePurchaseInvoice::dispatch($request, $purchaseInvoice);

            return redirect()->route('purchase-invoices.index')->with('success', __('The purchase invoice details are updated successfully.'));
        }
        else{
            return redirect()->route('purchase-invoices.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(PurchaseInvoice $purchaseInvoice)
    {
        if(Auth::user()->can('delete-purchase-invoices')){
            if ($purchaseInvoice->status === 'posted') {
                return back()->withErrors(['error' => __('Cannot delete posted invoice.')]);
            }

            // Dispatch event before deletion
            DestroyPurchaseInvoice::dispatch($purchaseInvoice);

            $purchaseInvoice->delete();

            return redirect()->route('purchase-invoices.index')->with('success', __('The purchase invoice has been deleted.'));
        }
        else{
            return redirect()->route('purchase-invoices.index')->with('error', __('Permission denied'));
        }
    }

    private function calculateTotals($items)
    {
        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $discountAmount = ($lineTotal * ($item['discount_percentage'] ?? 0)) / 100;
            $afterDiscount = $lineTotal - $discountAmount;
            $taxAmount = ($afterDiscount * ($item['tax_percentage'] ?? 0)) / 100;

            $subtotal += $lineTotal;
            $totalDiscount += $discountAmount;
            $totalTax += $taxAmount;
        }

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $totalTax,
            'discount_amount' => $totalDiscount,
            'total_amount' => $subtotal + $totalTax - $totalDiscount
        ];
    }

    private function createInvoiceItems($invoiceId, $items)
    {
        foreach ($items as $itemData) {
            $item = new PurchaseInvoiceItem();
            $item->invoice_id = $invoiceId;
            $item->product_id = $itemData['product_id'];
            $item->quantity = $itemData['quantity'];
            $item->unit_price = $itemData['unit_price'];
            $item->discount_percentage = $itemData['discount_percentage'] ?? 0;
            $item->tax_percentage = $itemData['tax_percentage'] ?? 0;
            $item->save();

            // Store individual taxes
            if (isset($itemData['taxes']) && is_array($itemData['taxes'])) {
                foreach ($itemData['taxes'] as $tax) {
                    $purchaseInvoiceItemTax = new PurchaseInvoiceItemTax();
                    $purchaseInvoiceItemTax->item_id = $item->id;
                    $purchaseInvoiceItemTax->tax_name = $tax['tax_name'];
                    $purchaseInvoiceItemTax->tax_rate = $tax['tax_rate'] ?? $tax['rate'] ?? 0;
                    $purchaseInvoiceItemTax->save();
                }
            }
        }
    }

    public function post(PurchaseInvoice $purchaseInvoice)
    {
        if(Auth::user()->can('post-purchase-invoices')){
        if ($purchaseInvoice->status !== 'draft') {
            return back()->withErrors(['error' => __('Only draft invoices can be posted.')]);
        }

        try {
            PostPurchaseInvoice::dispatch($purchaseInvoice);
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }

        $purchaseInvoice->update(['status' => 'posted']);

        return back()->with('success', __('The purchase invoice has been posted successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function print(PurchaseInvoice $purchaseInvoice)
    {
        if(Auth::user()->can('print-purchase-invoices')){
            $purchaseInvoice->load(['vendor', 'vendorDetails', 'items.product', 'items.taxes', 'warehouse']);

            return Inertia::render('Purchase/Print', [
                'invoice' => $purchaseInvoice
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
