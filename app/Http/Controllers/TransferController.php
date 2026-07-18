<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use App\Models\Warehouse;
use Zerp\ProductService\Models\ProductServiceItem;
use Zerp\ProductService\Models\WarehouseStock;
use App\Http\Requests\StoreTransferRequest;
use App\Events\CreateTransfer;
use App\Events\DestroyTransfer;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TransferController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-transfers')){
            $transfers = Transfer::query()
                ->with(['fromWarehouse:id,name', 'toWarehouse:id,name', 'product:id,name,sku'])
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-transfers')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-transfers')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('product_name'), function($q) {
                    $q->whereHas('product', function($query) {
                        $query->where('name', 'like', '%' . likeEscape(request('product_name')) . '%');
                    });
                })
                ->when(request('from_warehouse'), function($q) {
                    $q->where('from_warehouse', request('from_warehouse'));
                })
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            $warehouses = Warehouse::where('created_by', creatorId())->where('is_active', true)->get(['id', 'name']);
            $products = ProductServiceItem::where('created_by', creatorId())->get(['id', 'name', 'sku']);
            $warehouseStocks = WarehouseStock::with('product:id,name,sku')
                ->whereHas('product', function($q) {
                    $q->where('created_by', creatorId());
                })
                ->where('quantity', '>', 0)
                ->get();

            return Inertia::render('Transfers/Index', [
                'transfers' => $transfers,
                'warehouses' => $warehouses,
                'products' => $products,
                'warehouseStocks' => $warehouseStocks,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        // Modal-based create, no separate page needed
        return back();
    }

    public function store(StoreTransferRequest $request)
    {
        if(Auth::user()->can('create-transfers')){
            $validated = $request->validated();

            $transfer = new Transfer();
            $transfer->from_warehouse = $validated['from_warehouse'];
            $transfer->to_warehouse = $validated['to_warehouse'];
            $transfer->product_id = $validated['product_id'];
            $transfer->quantity = $validated['quantity'];
            $transfer->date = $validated['date'];
            $transfer->creator_id = Auth::id();
            $transfer->created_by = creatorId();
            $transfer->save();

            // Update warehouse stocks
            // Decrease from source warehouse
            $fromStock = WarehouseStock::where('product_id', $validated['product_id'])
                ->where('warehouse_id', $validated['from_warehouse'])
                ->first();

            if ($fromStock) {
                $fromStock->quantity = max(0, $fromStock->quantity - $validated['quantity']);
                $fromStock->save();
            }

            // Increase in destination warehouse
            $toStock = WarehouseStock::firstOrCreate(
                [
                    'product_id' => $validated['product_id'],
                    'warehouse_id' => $validated['to_warehouse'],
                ],
                ['quantity' => 0]
            );
            $toStock->quantity += $validated['quantity'];
            $toStock->save();

            // Dispatch event for packages to handle their fields
            CreateTransfer::dispatch($request, $transfer);

            return back()->with('success', __('The transfer has been created successfully.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function show(Transfer $transfer)
    {
        if(Auth::user()->can('view-transfers')){
            $transfer->load(['fromWarehouse', 'toWarehouse', 'product']);

            return Inertia::render('Transfers/Show', [
                'transfer' => $transfer,
            ]);
        }
        return back()->with('error', __('Permission denied'));
    }

    public function destroy(Transfer $transfer)
    {
        if(Auth::user()->can('delete-transfers')){
        // Add back to source warehouse
        $fromStock = WarehouseStock::firstOrCreate(
            [
                'product_id' => $transfer->product_id,
                'warehouse_id' => $transfer->from_warehouse,
            ],
            ['quantity' => 0]
        );
        $fromStock->quantity += $transfer->quantity;
        $fromStock->save();

        // Remove from destination warehouse
        $toStock = WarehouseStock::where('product_id', $transfer->product_id)
            ->where('warehouse_id', $transfer->to_warehouse)
            ->first();

        if ($toStock) {
            $toStock->quantity = max(0, $toStock->quantity - $transfer->quantity);
            $toStock->save();
        }

            DestroyTransfer::dispatch($transfer);

            $transfer->delete();

            return back()->with('success', __('The transfer has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
