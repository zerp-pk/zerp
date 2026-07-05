<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-roles')){
            $roles = Role::select('id', 'name', 'label','editable')
                ->where('created_by', creatorId())
                ->when(request('name'), fn($q) => $q->where('name', 'like', '%' . request('name') . '%'))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')))
                ->with(['users' => function($query) {
                    $query->select('id', 'name')->limit(5);
                }])
                ->withCount('permissions')
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('roles/index', [
                'roles' => $roles,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if(Auth::user()->can('create-roles')){
            $allPermissions = Auth::user()->getAllPermissions()->select('id', 'name', 'label', 'add_on', 'module');
            $permissions = $allPermissions->groupBy('add_on')
                ->filter(function ($addOnPermissions, $addOn) {
                    return $addOn === 'general' || Module_is_active($addOn);
                })
                ->map(function ($addOnPermissions) {
                    return $addOnPermissions->groupBy('module');
                });
            return Inertia::render('roles/create', ['permissions' => $permissions]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreRoleRequest $request)
    {
        if(Auth::user()->can('create-roles')){
            $role = new Role();
            $role->name = $request->name;
            $role->label = $request->label;
            $role->created_by = creatorId();
            $role->save();
            $role->syncPermissions($request->permissions ?? []);
            return redirect()->route('roles.index')->with('success', __('The role has been created successfully.'));
        }
        else{
            return redirect()->route('roles.index')->with('error', __('Permission denied'));
        }
    }

    public function edit(Role $role)
    {
        if(Auth::user()->can('edit-roles')){
            $allPermissions = Auth::user()->getAllPermissions()->select('id', 'name', 'label', 'add_on', 'module','editable');
            $permissions = $allPermissions->groupBy('add_on')
                ->filter(function ($addOnPermissions, $addOn) {
                    return $addOn === 'general' || Module_is_active($addOn);
                })
                ->map(function ($addOnPermissions) {
                    return $addOnPermissions->groupBy('module');
                });
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            return Inertia::render('roles/edit', [
                'role' => $role,
                'permissions' => $permissions,
                'rolePermissions' => $rolePermissions
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        if(Auth::user()->can('edit-roles')){
            $role->update([
                'name' => $request->name,
                'label' => $request->label
            ]);
            $role->syncPermissions($request->permissions ?? []);
            return redirect()->route('roles.index')->with('success', __('The role details are updated successfully.'));
        }
        else{
            return redirect()->route('roles.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Role $role)
    {
        if(Auth::user()->can('delete-roles')){
            if($role->editable == 0){
                return redirect()->route('roles.index')->with('error', __('This role is not editable'));
            }
            $role->delete();
            return redirect()->route('roles.index')->with('success', __('The role has been deleted.'));
        }
        else{
            return redirect()->route('roles.index')->with('error', __('Permission denied'));
        }
    }
}
