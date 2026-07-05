<?php

namespace App\Http\Controllers;

use App\Events\CreateHelpdeskCategory;
use App\Events\UpdateHelpdeskCategory;
use App\Events\DestroyHelpdeskCategory;
use App\Http\Requests\StoreHelpdeskCategoryRequest;
use App\Http\Requests\UpdateHelpdeskCategoryRequest;
use App\Models\HelpdeskCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HelpdeskCategoryController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-helpdesk-categories')){
            $categories = HelpdeskCategory::query()
                ->where('created_by', creatorId())
                ->when(request('name'), fn($q) => $q->where('name', 'like', '%' . request('name') . '%'))
                ->when(request('is_active') !== null, fn($q) => $q->where('is_active', request('is_active')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('helpdesk/categories/index', [
                'categories' => $categories,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StoreHelpdeskCategoryRequest $request)
    {
        if(Auth::user()->can('create-helpdesk-categories')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);

            $category = new HelpdeskCategory();
            $category->name = $validated['name'];
            $category->description = $validated['description'];
            $category->color = $validated['color'] ?? '#3B82F6';
            $category->is_active = $validated['is_active'];
            $category->creator_id = Auth::id();
            $category->created_by = creatorId();
            $category->save();

            CreateHelpdeskCategory::dispatch($request,$category);

            return redirect()->route('helpdesk-categories.index')->with('success', __('The helpdesk category has been created successfully.'));
        }
        else{
            return redirect()->route('helpdesk-categories.index')->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateHelpdeskCategoryRequest $request, HelpdeskCategory $helpdeskCategory)
    {
        if(Auth::user()->can('edit-helpdesk-categories')){
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);

            $helpdeskCategory->name = $validated['name'];
            $helpdeskCategory->description = $validated['description'];
            $helpdeskCategory->color = $validated['color'] ?? '#3B82F6';
            $helpdeskCategory->is_active = $validated['is_active'];
            $helpdeskCategory->save();

            UpdateHelpdeskCategory::dispatch($request,$helpdeskCategory);

            return back()->with('success', __('The helpdesk category has been updated successfully'));
        }
        else{
            return redirect()->route('helpdesk-categories.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(HelpdeskCategory $helpdeskCategory)
    {
        if(Auth::user()->can('delete-helpdesk-categories')){
            DestroyHelpdeskCategory::dispatch($helpdeskCategory);

            $helpdeskCategory->delete();

           return back()->with('success', __('The helpdesk category has been deleted.'));
        }
        else{
            return redirect()->route('helpdesk-categories.index')->with('error', __('Permission denied'));
        }
    }
}