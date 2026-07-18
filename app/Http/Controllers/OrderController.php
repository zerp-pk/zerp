<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-orders')){
            $orders = Order::with(['plan', 'user', 'total_coupon_used.coupon_detail'])
                ->where(function($q) {
                    if(Auth::user()->type == 'superadmin') {
                    } else {
                        $q->where('created_by', Auth::id());
                    }
                })
                ->when(request('search'), function($q) {
                    $search = request('search');
                    $q->where(function ($query) use ($search) {
                        $query->where('order_id', 'like', '%' . likeEscape($search) . '%')
                              ->orWhere('name', 'like', '%' . likeEscape($search) . '%')
                              ->orWhere('email', 'like', '%' . likeEscape($search) . '%')
                              ->orWhere('plan_name', 'like', '%' . likeEscape($search) . '%');
                    });
                })
                ->when(request('order_id'), fn($q) => $q->where('order_id', 'like', '%' . likeEscape(request('order_id')) . '%'))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'desc')), fn($q) => $q->orderBy('id', 'desc'))
                ->paginate(request('per_page', 10))
                ->withQueryString();

            return Inertia::render('orders/index', [
                'orders' => $orders,
            ]);
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}