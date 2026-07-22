<?php

namespace App\Http\Controllers;

use App\Events\AcceptSalesProposal;
use App\Events\ConvertSalesProposal;
use App\Events\CreateSalesProposal;
use App\Events\DestroySalesProposal;
use App\Events\RejectSalesProposal;
use App\Events\SentSalesProposal;
use App\Events\UpdateSalesProposal;
use App\Models\SalesProposal;
use App\Models\SalesProposalItem;
use App\Models\SalesProposalItemTax;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesInvoiceItemTax;
use App\Models\User;
use App\Models\Warehouse;
use App\Http\Requests\StoreSalesProposalRequest;
use App\Http\Requests\UpdateSalesProposalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Zerp\ProductService\Models\ProductServiceItem;

class SalesProposalController extends Controller
{
    private function checkProposalAccess(SalesProposal $salesProposal)
    {
        if(Auth::user()->can('manage-any-sales-proposals')) {
            return true;
        } elseif(Auth::user()->can('manage-own-sales-proposals')) {
            if($salesProposal->creator_id != Auth::id() && $salesProposal->customer_id != Auth::id()) {
                return false;
            }
            if($salesProposal->creator_id != Auth::id() && Auth::user()->type == 'client' && $salesProposal->status == 'draft') {
                return false;
            }
            return true;
        }
        return false;
    }
    public function index(Request $request)
    {
        if(Auth::user()->can('manage-sales-proposals')){
            $query = SalesProposal::with(['customer', 'items'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-sales-proposals')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-sales-proposals')) {
                        $q->where('creator_id', Auth::id())->orWhere('customer_id',Auth::id());
                        if(Auth::user()->type == 'client') {
                            $q->where('status','!=', 'draft');
                        }
                    } else {
                        denyAccess();
                    }
                });
            // Apply filters
            if ($request->customer_id) {
                $query->where('customer_id', $request->customer_id);
            }
            if ($request->status) {
                if ($request->status === 'expired') {
                    $query->where('due_date', '<', now())
                          ->whereNotIn('status', ['accepted', 'rejected']);
                } else {
                    $query->where('status', $request->status);
                }
            }
            if ($request->search) {
                $query->where('proposal_number', 'like', '%' . likeEscape($request->search) . '%');
            }
            if ($request->date_range) {
                $dates = explode(' - ', $request->date_range);
                if (count($dates) === 2) {
                    $query->whereBetween('proposal_date', [$dates[0], $dates[1]]);
                }
            }

            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');
            $allowedSortFields = ['proposal_number', 'proposal_date', 'due_date', 'subtotal', 'tax_amount', 'total_amount', 'status', 'created_at'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'created_at';
            }

            $query->orderBy($sortField, $sortDirection);

            $perPage = perPage();
            $proposals = $query->paginate($perPage);
            $customers = User::where('type', 'client')->select('id', 'name', 'email')->where('created_by', creatorId())->get();

            return Inertia::render('SalesProposals/Index', [
                'proposals' => $proposals,
                'customers' => $customers,
                'filters' => $request->only(['customer_id', 'status', 'search', 'date_range'])
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if(Auth::user()->can('create-sales-proposals')){
            $customers = User::where('type', 'client')->select('id', 'name', 'email')->where('created_by', creatorId())->get();
            $warehouses = Warehouse::where('is_active', true)->select('id', 'name', 'address')->where('created_by', creatorId())->get();

            return Inertia::render('SalesProposals/Create', [
                'customers' => $customers,
                'warehouses' => $warehouses
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreSalesProposalRequest $request)
    {
        $totals = $this->calculateTotals($request->items);

        $proposal = new SalesProposal();
        $proposal->proposal_date = $request->invoice_date;
        $proposal->due_date = $request->due_date;
        $proposal->customer_id = $request->customer_id;
        $proposal->warehouse_id = $request->warehouse_id;
        $proposal->payment_terms = $request->payment_terms;
        $proposal->notes = $request->notes;
        $proposal->subtotal = $totals['subtotal'];
        $proposal->tax_amount = $totals['tax_amount'];
        $proposal->discount_amount = $totals['discount_amount'];
        $proposal->total_amount = $totals['total_amount'];
        $proposal->creator_id = Auth::id();
        $proposal->created_by = creatorId();
        $proposal->save();

        $this->createProposalItems($proposal->id, $request->items);

        try {
            CreateSalesProposal::dispatch($request, $proposal);
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }

        return redirect()->route('sales-proposals.index')->with('success', __('The sales proposal has been created successfully.'));
    }

    public function show(SalesProposal $salesProposal)
    {
        if(Auth::user()->can('view-sales-proposals') && $salesProposal->created_by == creatorId()){
            if(!$this->checkProposalAccess($salesProposal)) {
                return redirect()->route('sales-proposals.index')->with('error', __('Permission denied'));
            }

            $salesProposal->load(['customer', 'items.product', 'items.taxes', 'warehouse']);

            return Inertia::render('SalesProposals/View', [
                'proposal' => $salesProposal
            ]);
        }
        else{
            return redirect()->route('sales-proposals.index')->with('error', __('Permission denied'));
        }
    }

    public function edit(SalesProposal $salesProposal)
    {
        if(Auth::user()->can('edit-sales-proposals') && $salesProposal->created_by == creatorId()){
            if(!$this->checkProposalAccess($salesProposal)) {
                return redirect()->route('sales-proposals.index')->with('error', __('Permission denied'));
            }

            if ($salesProposal->converted_to_invoice) {
                return redirect()->route('sales-proposals.index')->with('error', __('Cannot update converted proposal.'));
            }

            $salesProposal->load(['items.taxes']);
            $customers = User::where('type', 'client')->select('id', 'name', 'email')->where('created_by', creatorId())->get();
            $warehouses = Warehouse::where('is_active', true)->select('id', 'name', 'address')->where('created_by', creatorId())->get();

            return Inertia::render('SalesProposals/Edit', [
                'proposal' => $salesProposal,
                'customers' => $customers,
                'warehouses' => $warehouses
            ]);
        }
        else{
            return redirect()->route('sales-proposals.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateSalesProposalRequest $request, SalesProposal $salesProposal)
    {
        if ($salesProposal->converted_to_invoice) {
            return redirect()->route('sales-proposals.index')->with('error', __('Cannot update converted proposal.'));
        }

        $totals = $this->calculateTotals($request->items);

        $salesProposal->proposal_date = $request->invoice_date;
        $salesProposal->due_date = $request->due_date;
        $salesProposal->customer_id = $request->customer_id;
        $salesProposal->warehouse_id = $request->warehouse_id;
        $salesProposal->payment_terms = $request->payment_terms;
        $salesProposal->notes = $request->notes;
        $salesProposal->subtotal = $totals['subtotal'];
        $salesProposal->tax_amount = $totals['tax_amount'];
        $salesProposal->discount_amount = $totals['discount_amount'];
        $salesProposal->total_amount = $totals['total_amount'];
        $salesProposal->save();

        $salesProposal->items()->delete();
        $this->createProposalItems($salesProposal->id, $request->items);

        // Dispatch event for packages to handle their fields
        UpdateSalesProposal::dispatch($request, $salesProposal);

        return redirect()->route('sales-proposals.index')->with('success', __('The sales proposal details are updated successfully.'));
    }

    public function destroy(SalesProposal $salesProposal)
    {
        if(Auth::user()->can('delete-sales-proposals')){
            if ($salesProposal->converted_to_invoice) {
                return back()->withErrors(['error' => __('Cannot delete converted proposal.')]);
            }

            // Dispatch event before deletion
            DestroySalesProposal::dispatch($salesProposal);

            $salesProposal->delete();

            return redirect()->route('sales-proposals.index')->with('success', __('The sales proposal has been deleted.'));
        }
        else{
            return redirect()->route('sales-proposals.index')->with('error', __('Permission denied'));
        }
    }

    public function convertToInvoice(SalesProposal $salesProposal)
    {
        if(Auth::user()->can('convert-sales-proposals') && $salesProposal->created_by == creatorId()){
            if ($salesProposal->status !== 'accepted') {
                return back()->with('error', __('Only accepted proposals can be converted to invoice.'));
            }

            if ($salesProposal->converted_to_invoice) {
                return back()->with('error', __('Proposal already converted to invoice.'));
            }
            try {
                $invoice = new SalesInvoice();
                $invoice->customer_id = $salesProposal->customer_id;
                $invoice->warehouse_id = $salesProposal->warehouse_id ?? 1;
                $invoice->invoice_date = now();
                $invoice->due_date = $salesProposal->due_date;
                $invoice->subtotal = $salesProposal->subtotal;
                $invoice->tax_amount = $salesProposal->tax_amount;
                $invoice->discount_amount = $salesProposal->discount_amount;
                $invoice->total_amount = $salesProposal->total_amount;
                $invoice->balance_amount = $salesProposal->total_amount;
                $invoice->paid_amount = 0;
                $invoice->payment_terms = $salesProposal->payment_terms;
                $invoice->notes = $salesProposal->notes;
                $invoice->status = 'draft';
                $invoice->creator_id = Auth::id();
                $invoice->created_by = creatorId();
                $invoice->save();

                foreach ($salesProposal->items as $proposalItem) {
                    $invoiceItem = new SalesInvoiceItem();
                    $invoiceItem->invoice_id = $invoice->id;
                    $invoiceItem->product_id = $proposalItem->product_id;
                    $invoiceItem->quantity = $proposalItem->quantity;
                    $invoiceItem->unit_price = $proposalItem->unit_price;
                    $invoiceItem->discount_percentage = $proposalItem->discount_percentage;
                    $invoiceItem->tax_percentage = $proposalItem->tax_percentage;
                    $invoiceItem->save();

                    foreach ($proposalItem->taxes as $tax) {
                        $invoiceTax = new SalesInvoiceItemTax();
                        $invoiceTax->item_id = $invoiceItem->id;
                        $invoiceTax->tax_name = $tax->tax_name;
                        $invoiceTax->tax_rate = $tax->tax_rate;
                        $invoiceTax->save();
                    }
                }

                $salesProposal->update([
                    'converted_to_invoice' => true,
                    'invoice_id' => $invoice->id
                ]);

                try {
                    ConvertSalesProposal::dispatch($salesProposal, $invoice);
                } catch (\Throwable $th) {
                    return back()->with('error', $th->getMessage());
                }

                return back()->with('success', __('Proposal converted to invoice successfully.'));
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        } else {
            return back()->with('error', __('Permission denied'));
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

    private function createProposalItems($proposalId, $items)
    {
        foreach ($items as $itemData) {
            $item = new SalesProposalItem();
            $item->proposal_id = $proposalId;
            $item->product_id = $itemData['product_id'];
            $item->quantity = $itemData['quantity'];
            $item->unit_price = $itemData['unit_price'];
            $item->discount_percentage = $itemData['discount_percentage'] ?? 0;
            $item->tax_percentage = $itemData['tax_percentage'] ?? 0;
            $item->save();

            if (isset($itemData['taxes']) && is_array($itemData['taxes'])) {
                foreach ($itemData['taxes'] as $tax) {
                    $proposalItemTax = new SalesProposalItemTax();
                    $proposalItemTax->item_id = $item->id;
                    $proposalItemTax->tax_name = $tax['tax_name'];
                    $proposalItemTax->tax_rate = $tax['tax_rate'] ?? $tax['rate'] ?? 0;
                    $proposalItemTax->save();
                }
            }
        }
    }

    public function getWarehouseProducts(Request $request)
    {
        if(Auth::user()->can('create-sales-proposals') || Auth::user()->can('edit-sales-proposals')){
            $warehouseId = $request->warehouse_id;

            if (!$warehouseId) {
                return response()->json([]);
            }
            $products = ProductServiceItem::select('id', 'name', 'sku', 'sale_price', 'tax_ids', 'unit', 'type')
                ->where('is_active', true)
                ->where('created_by', creatorId())
                ->whereHas('warehouseStocks', function($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId)
                      ->where('quantity', '>', 0);
                })
                ->with(['warehouseStocks' => function($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                }])
                ->get()
                ->map(function ($product) {
                    $stock = $product->warehouseStocks->first();
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'sale_price' => $product->sale_price,
                        'unit' => $product->unit,
                        'type' => $product->type,
                        'stock_quantity' => $stock ? $stock->quantity : 0,
                        'taxes' => $product->taxes->map(function ($tax) {
                            return [
                                'id' => $tax->id,
                                'tax_name' => $tax->tax_name,
                                'rate' => $tax->rate
                            ];
                        })
                    ];
                });
            return response()->json($products);
        }
        else{
            return response()->json([], 403);
        }
    }

    public function print(SalesProposal $salesProposal)
    {
        if(Auth::user()->can('print-sales-proposals')){
            $salesProposal->load(['customer', 'items.product', 'items.taxes', 'warehouse']);

            return Inertia::render('SalesProposals/Print', [
                'proposal' => $salesProposal
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function sent(SalesProposal $salesProposal)
    {
        if(Auth::user()->can('sent-sales-proposals') && $salesProposal->created_by == creatorId()){
            if ($salesProposal->status !== 'draft') {
                return back()->with('error', __('Only draft proposals can be sent.'));
            }

            SentSalesProposal::dispatch($salesProposal);

            $salesProposal->update(['status' => 'sent']);

            return back()->with('success', __('Proposal sent successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function accept(SalesProposal $salesProposal)
    {
        if(Auth::user()->can('accept-sales-proposals') && $salesProposal->created_by == creatorId()){
            if ($salesProposal->status !== 'sent') {
                return back()->with('error', __('Only sent proposals can be accepted.'));
            }
            AcceptSalesProposal::dispatch($salesProposal);

            $salesProposal->update(['status' => 'accepted']);

            return back()->with('success', __('Proposal accepted successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function reject(SalesProposal $salesProposal)
    {
        if(Auth::user()->can('reject-sales-proposals') && $salesProposal->created_by == creatorId()){
            if ($salesProposal->status !== 'sent') {
                return back()->with('error', __('Only sent proposals can be rejected.'));
            }

            RejectSalesProposal::dispatch($salesProposal);

            $salesProposal->update(['status' => 'rejected']);

            return back()->with('success', __('Proposal rejected successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
