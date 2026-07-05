<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Events\CreateWarehouse;
use App\Events\DestroyWarehouse;
use App\Events\UpdateWarehouse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-warehouses')){
            $warehouses = Warehouse::query()
                ->where(function($q) {
                    if(Auth::user()->can('manage-any-warehouses')) {
                        $q->where('created_by', creatorId());
                    } elseif(Auth::user()->can('manage-own-warehouses')) {
                        $q->where('creator_id', Auth::id());
                    } else {
                        $q->whereRaw('1 = 0');
                    }
                })
                ->when(request('name'), fn($q) => $q->where('name', 'like', '%' . request('name') . '%'))
                ->when(request('city'), fn($q) => $q->where('city', 'like', '%' . request('city') . '%'))
                ->when(request('is_active') !== null, fn($q) => $q->where('is_active', request('is_active')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('warehouses/index', [
                'warehouses' => $warehouses,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreWarehouseRequest $request)
    {
        if(Auth::user()->can('create-warehouses')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);

            $warehouse = new Warehouse();
            $warehouse->name = $validated['name'];
            $warehouse->address = $validated['address'];
            $warehouse->city = $validated['city'];
            $warehouse->zip_code = $validated['zip_code'];
            $warehouse->phone = $validated['phone'];
            $warehouse->email = $validated['email'];
            $warehouse->is_active = $validated['is_active'];
            $warehouse->creator_id = Auth::id();
            $warehouse->created_by = creatorId();
            $warehouse->save();

            // Dispatch event for packages to handle their fields
            CreateWarehouse::dispatch($request, $warehouse);

            return redirect()->route('warehouses.index')->with('success', __('The warehouse has been created successfully.'));
        }
        else{
            return redirect()->route('warehouses.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        if(Auth::user()->can('edit-warehouses')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);

            $warehouse->name = $validated['name'];
            $warehouse->address = $validated['address'];
            $warehouse->city = $validated['city'];
            $warehouse->zip_code = $validated['zip_code'];
            $warehouse->phone = $validated['phone'];
            $warehouse->email = $validated['email'];
            $warehouse->is_active = $validated['is_active'];
            $warehouse->save();

            // Dispatch event for packages to handle their fields
            UpdateWarehouse::dispatch($request, $warehouse);

            return back()->with('success', __('The warehouse details are updated successfully.'));
        }
        else{
            return redirect()->route('warehouses.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Warehouse $warehouse)
    {
        if(Auth::user()->can('delete-warehouses')){
            DestroyWarehouse::dispatch($warehouse);

            $warehouse->delete();

            return back()->with('success', __('The warehouse has been deleted.'));
        }
        else{
            return redirect()->route('warehouses.index')->with('error', __('Permission denied'));
        }
    }
}
