<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\PurchaseReturnItemTax;
use App\Models\PurchaseInvoice;
use App\Models\User;
use App\Models\Warehouse;
use App\Events\ApprovePurchaseReturn;
use App\Events\CompletePurchaseReturn;
use App\Events\CreatePurchaseReturn;
use App\Events\DestroyPurchaseReturn;
use Illuminate\Http\Request;
use App\Http\Requests\StorePurchaseReturnRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    private function checkReturnAccess(PurchaseReturn $return)
    {
        if(Auth::user()->can('manage-any-purchase-return-invoices')) {
            return true;
        } elseif(Auth::user()->can('manage-own-purchase-return-invoices')) {
            if($return->creator_id != Auth::id() && $return->vendor_id != Auth::id()) {
                return false;
            }
            if($return->creator_id != Auth::id() && Auth::user()->type == 'vendor' && $return->status == 'draft') {
                return false;
            }
            return true;
        }
        return false;
    }
    public function index(Request $request)
    {
        if(Auth::user()->can('manage-purchase-return-invoices')){
            $query = PurchaseReturn::with(['vendor', 'originalInvoice', 'items.product', 'warehouse'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-purchase-return-invoices')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-purchase-return-invoices')) {
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
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $query->where('return_number', 'like', '%' . $request->search . '%');
        }
        if ($request->date_range) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) === 2) {
                $query->whereBetween('return_date', [$dates[0], $dates[1]]);
            }
        }

        // Apply sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['return_number', 'return_date', 'total_amount', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSortFields) || empty($sortField)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, $sortDirection);

        $perPage = $request->get('per_page', 10);
        $returns = $query->paginate($perPage);

        $vendors = User::where('type', 'vendor')->select('id', 'name', 'email')->where('created_by', creatorId())->get();
        $warehouses = Warehouse::where('is_active', true)->select('id', 'name')->where('created_by', creatorId())->get();

            return Inertia::render('PurchaseReturns/Index', [
                'returns' => $returns,
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
        if(Auth::user()->can('create-purchase-return-invoices')){
            $invoices = PurchaseInvoice::with(['vendor', 'warehouse', 'items.product', 'items.taxes', 'purchaseReturns.items'])
            ->where('created_by', creatorId())
            ->where('status', '!=', 'draft')
            ->when(Auth::user()->type == 'vendor', function($q) {
                $q->where('vendor_id', Auth::id());
            })
            ->get();

        // Calculate available quantities for each item
        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                $totalReturned = $invoice->purchaseReturns
                    ->where('status', '!=', 'cancelled')
                    ->flatMap->items
                    ->where('original_invoice_item_id', $item->id)
                    ->sum('return_quantity');

                $item->available_quantity = $item->quantity - $totalReturned;
            }
        }

        $warehouses = \App\Models\Warehouse::where('created_by', creatorId())->where('is_active', true)->get();

            return Inertia::render('PurchaseReturns/Create', [
                'invoices' => $invoices,
                'warehouses' => $warehouses
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StorePurchaseReturnRequest $request)
    {
        if(Auth::user()->can('create-purchase-return-invoices')){

        $totals = $this->calculateReturnTotals($request->items, $request->original_invoice_id);
        $return = new PurchaseReturn();
        $return->return_date = $request->return_date;
        $return->vendor_id = $request->vendor_id;
        $return->warehouse_id = $request->warehouse_id ?? null;
        $return->original_invoice_id = $request->original_invoice_id;
        $return->reason = $request->reason;
        $return->notes = $request->notes;
        $return->subtotal = $totals['subtotal'];
        $return->tax_amount = $totals['tax_amount'];
        $return->discount_amount = $totals['discount_amount'];
        $return->total_amount = $totals['total_amount'];
        $return->status = 'draft';
        $return->creator_id = Auth::id();
        $return->created_by = creatorId();
        $return->save();

        // Create return items
        $this->createReturnItems($return->id, $request->items, $request->original_invoice_id);

        try {
            CreatePurchaseReturn::dispatch($request, $return);
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
            return redirect()->route('purchase-returns.index')->with('success', __('The purchase return has been created successfully.'));
        }
        else{
            return redirect()->route('purchase-returns.index')->with('error', __('Permission denied'));
        }
    }

    public function show(PurchaseReturn $return)
    {
        if(Auth::user()->can('view-purchase-return-invoices') && $return->created_by == creatorId()){
            if(!$this->checkReturnAccess($return)) {
                return redirect()->route('purchase-returns.index')->with('error', __('Permission denied'));
            }

            $return->load(['vendor', 'vendorDetails', 'warehouse', 'originalInvoice', 'items.product']);

            return Inertia::render('PurchaseReturns/View', [
                'return' => $return
            ]);
        }
        else{
            return redirect()->route('purchase-returns.index')->with('error', __('Permission denied'));
        }
    }

    public function approve(PurchaseReturn $return)
    {
        if(Auth::user()->can('approve-purchase-returns-invoices')){
            if ($return->status !== 'draft') {
                return redirect()->back()->with('error', __('Only draft returns can be approved.'));
            }

            try {
                ApprovePurchaseReturn::dispatch($return);
            } catch (\Throwable $th) {
                return back()->with('error', $th->getMessage());
            }

            $return->update(['status' => 'approved']);

            return redirect()->route('purchase-returns.index')->with('success', __('The purchase return has been approved successfully. Debit note has been created automatically.'));
        }
        else{
            return redirect()->route('purchase-returns.index')->with('error', __('Permission denied'));
        }
    }

    public function complete(PurchaseReturn $return)
    {
        if(Auth::user()->can('complete-purchase-returns-invoices')){
            if ($return->status !== 'approved') {
            return redirect()->back()->with('error', __('Only approved returns can be completed.'));
        }

        CompletePurchaseReturn::dispatch($return);

        $return->update(['status' => 'completed']);

            return redirect()->route('purchase-returns.index')->with('success', __('The purchase return has been completed successfully.'));
        }
        else{
            return redirect()->route('purchase-returns.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(PurchaseReturn $return)
    {
        if(Auth::user()->can('delete-purchase-return-invoices')){
            if ($return->status !== 'draft') {
            return redirect()->back()->with('error', __('Only draft returns can be deleted.'));
        }
            DestroyPurchaseReturn::dispatch($return);

            $return->delete();

            return redirect()->route('purchase-returns.index')->with('success', __('The purchase return has been deleted.'));
        }
        else{
            return redirect()->route('purchase-returns.index')->with('error', __('Permission denied'));
        }
    }

    private function calculateReturnTotals($items, $originalInvoiceId)
    {
        $originalInvoice = PurchaseInvoice::with(['items.taxes'])->find($originalInvoiceId);

        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;

        foreach ($items as $item) {
            $originalItem = $originalInvoice->items->where('id', $item['original_invoice_item_id'])->first();

            $lineTotal = $item['return_quantity'] * $item['unit_price'];
            $discountPercentage = $originalItem ? $originalItem->discount_percentage : 0;
            $discountAmount = ($lineTotal * $discountPercentage) / 100;
            $afterDiscount = $lineTotal - $discountAmount;
            $taxPercentage = $originalItem ? $originalItem->tax_percentage : 0;
            $taxAmount = ($afterDiscount * $taxPercentage) / 100;

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

    private function createReturnItems($returnId, $items, $originalInvoiceId)
    {
        $originalInvoice = PurchaseInvoice::with(['items.taxes'])->find($originalInvoiceId);

        foreach ($items as $itemData) {
            $originalItem = $originalInvoice->items->where('id', $itemData['original_invoice_item_id'])->first();

            // Calculate amounts based on return quantity
            $lineTotal = $itemData['return_quantity'] * $itemData['unit_price'];
            $discountPercentage = $originalItem ? $originalItem->discount_percentage : 0;
            $discountAmount = ($lineTotal * $discountPercentage) / 100;
            $afterDiscount = $lineTotal - $discountAmount;
            $taxPercentage = $originalItem ? $originalItem->tax_percentage : 0;
            $taxAmount = ($afterDiscount * $taxPercentage) / 100;
            $totalAmount = $afterDiscount + $taxAmount;


            $item = new PurchaseReturnItem();
            $item->return_id = $returnId;
            $item->product_id = $itemData['product_id'];
            $item->original_invoice_item_id = $itemData['original_invoice_item_id'];
            $item->original_quantity = $originalItem ? $originalItem->quantity : 0;
            $item->return_quantity = $itemData['return_quantity'];
            $item->unit_price = $itemData['unit_price'];
            $item->discount_percentage = $discountPercentage;
            $item->discount_amount = $discountAmount;
            $item->tax_percentage = $taxPercentage;
            $item->tax_amount = $taxAmount;
            $item->total_amount = $totalAmount;
            $item->reason = $itemData['reason'] ?? null;
            $item->save();

            // Store individual taxes
            if ($originalItem && $originalItem->taxes) {
                foreach ($originalItem->taxes as $tax) {
                    $returnItemTax = new PurchaseReturnItemTax();
                    $returnItemTax->item_id = $item->id;
                    $returnItemTax->tax_name = $tax->tax_name;
                    $returnItemTax->tax_rate = $tax->tax_rate;
                    $returnItemTax->save();
                }
            }
        }
    }
}
