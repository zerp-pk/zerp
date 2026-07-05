<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoiceReturn;
use App\Models\SalesInvoiceReturnItem;
use App\Models\SalesInvoiceReturnItemTax;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use App\Events\ApproveSalesReturn;
use App\Events\CompleteSalesReturn;
use App\Events\CreateSalesReturn;
use App\Events\DestroySalesReturn;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSalesReturnRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends Controller
{
    private function checkReturnAccess(SalesInvoiceReturn $salesReturn)
    {
        if(Auth::user()->can('manage-any-sales-return-invoices')) {
            return true;
        } elseif(Auth::user()->can('manage-own-sales-return-invoices')) {
            if($salesReturn->creator_id != Auth::id() && $salesReturn->customer_id != Auth::id()) {
                return false;
            }
            if($salesReturn->creator_id != Auth::id() && Auth::user()->type == 'client' && $salesReturn->status == 'draft') {
                return false;
            }
            return true;
        }
        return false;
    }
    public function index(Request $request)
    {
        if(Auth::user()->can('manage-sales-return-invoices')){
            $query = SalesInvoiceReturn::with(['customer', 'originalInvoice', 'items.product', 'warehouse'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-sales-return-invoices')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-sales-return-invoices')) {
                        $q->where('creator_id', Auth::id())->orWhere('customer_id',Auth::id());
                        if(Auth::user()->type == 'client') {
                            $q->where('status','!=', 'draft');
                        }
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                });

        // Apply filters
        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
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

        $customers = User::where('type', 'client')->select('id', 'name', 'email')->where('created_by', creatorId())->get();
        $warehouses = Warehouse::where('is_active', true)->select('id', 'name')->where('created_by', creatorId())->get();

            return Inertia::render('SalesReturns/Index', [
                'returns' => $returns,
                'customers' => $customers,
                'warehouses' => $warehouses,
                'filters' => $request->only(['customer_id', 'warehouse_id', 'status', 'search', 'date_range'])
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if(Auth::user()->can('create-sales-return-invoices')){
            $invoices = SalesInvoice::with(['customer', 'warehouse', 'items.product', 'items.taxes', 'salesReturns.items'])
            ->where('created_by', creatorId())
            ->where('type', 'product')
            ->where('status', '!=', 'draft')
            ->when(Auth::user()->type == 'client', function($q) {
                $q->where('customer_id', Auth::id());
            })
            ->get();

        // Calculate available quantities for each item
        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                $totalReturned = $invoice->salesReturns
                    ->where('status', '!=', 'cancelled')
                    ->flatMap->items
                    ->where('original_invoice_item_id', $item->id)
                    ->sum('return_quantity');

                $item->available_quantity = $item->quantity - $totalReturned;
            }
        }

        $warehouses = \App\Models\Warehouse::where('created_by', creatorId())->where('is_active', true)->get();

            return Inertia::render('SalesReturns/Create', [
                'invoices' => $invoices,
                'warehouses' => $warehouses
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreSalesReturnRequest $request)
    {
        if(Auth::user()->can('create-sales-return-invoices')){

        $totals = $this->calculateReturnTotals($request->items, $request->original_invoice_id);
        $return = new SalesInvoiceReturn();
        $return->return_date = $request->return_date;
        $return->customer_id = $request->customer_id;
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
            CreateSalesReturn::dispatch($request, $return);
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
            return redirect()->route('sales-returns.index')->with('success', __('The sales return has been created successfully.'));
        }
        else{
            return redirect()->route('sales-returns.index')->with('error', __('Permission denied'));
        }
    }

    public function show(SalesInvoiceReturn $salesReturn)
    {
        if(Auth::user()->can('view-sales-return-invoices') && $salesReturn->created_by == creatorId()){
            if(!$this->checkReturnAccess($salesReturn)) {
                return redirect()->route('sales-returns.index')->with('error', __('Permission denied'));
            }

            $salesReturn->load(['customer', 'warehouse','customerDetails','originalInvoice', 'items.product']);

            return Inertia::render('SalesReturns/View', [
                'return' => $salesReturn
            ]);
        }
        else{
            return redirect()->route('sales-returns.index')->with('error', __('Permission denied'));
        }
    }

    public function approve(SalesInvoiceReturn $salesReturn)
    {
        if(Auth::user()->can('approve-sales-returns-invoices')){
            if ($salesReturn->status !== 'draft') {
                return redirect()->back()->with('error', __('Only draft returns can be approved.'));
            }

            try {
                ApproveSalesReturn::dispatch($salesReturn);
            } catch (\Throwable $th) {
                return back()->with('error', $th->getMessage());
            }

            $salesReturn->update(['status' => 'approved']);

            return redirect()->route('sales-returns.index')->with('success', __('The sales return has been approved successfully. Credit note has been created automatically.'));
        }
        else{
            return redirect()->route('sales-returns.index')->with('error', __('Permission denied'));
        }
    }

    public function complete(SalesInvoiceReturn $salesReturn)
    {
        if(Auth::user()->can('complete-sales-returns-invoices')){
            if ($salesReturn->status !== 'approved') {
            return redirect()->back()->with('error', __('Only approved returns can be completed.'));
        }

        CompleteSalesReturn::dispatch($salesReturn);

        $salesReturn->update(['status' => 'completed']);

            return redirect()->route('sales-returns.index')->with('success', __('The sales return has been completed successfully.'));
        }
        else{
            return redirect()->route('sales-returns.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(SalesInvoiceReturn $salesReturn)
    {
        if(Auth::user()->can('delete-sales-return-invoices')){
            if ($salesReturn->status !== 'draft') {
            return redirect()->back()->with('error', __('Only draft returns can be deleted.'));
        }
            DestroySalesReturn::dispatch($salesReturn);

            $salesReturn->delete();

            return redirect()->route('sales-returns.index')->with('success', __('The sales return has been deleted.'));
        }
        else{
            return redirect()->route('sales-returns.index')->with('error', __('Permission denied'));
        }
    }

    private function calculateReturnTotals($items, $originalInvoiceId)
    {
        $originalInvoice = SalesInvoice::with(['items.taxes'])->find($originalInvoiceId);

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
        $originalInvoice = SalesInvoice::with(['items.taxes'])->find($originalInvoiceId);

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


            $item = new SalesInvoiceReturnItem();
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
                    $returnItemTax = new SalesInvoiceReturnItemTax();
                    $returnItemTax->item_id = $item->id;
                    $returnItemTax->tax_name = $tax->tax_name;
                    $returnItemTax->tax_rate = $tax->tax_rate;
                    $returnItemTax->save();
                }
            }
        }
    }
}
