<?php

namespace App\Http\Controllers;

use App\Models\BankTransferPayment;
use App\Models\Plan;
use App\Models\Order;
use App\Http\Requests\StoreBankTransferPaymentRequest;
use App\Http\Requests\UpdateBankTransferPaymentRequest;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BankTransferPaymentController extends Controller
{
    public function store(StoreBankTransferPaymentRequest $request)
    {
        $validated = $request->validated();

        $bank_transfer_payment = new BankTransferPayment();

        if (!empty($request->payment_receipt)) {
            $filenameWithExt = $request->file('payment_receipt')->getClientOriginalName();
            $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension       = $request->file('payment_receipt')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;

            $uplaod = upload_file($request,'payment_receipt',$fileNameToStore,'bank_transfer');
            if($uplaod['flag'] == 1)
            {
                $bank_transfer_payment->attachment = $uplaod['url'];
            }
            else
            {
                return redirect()->back()->with('error',$uplaod['msg']);
                
            }
        }

        // Calculation
        $plan = Plan::find($request->plan_id);

        $user_module = !empty($request->user_module_input) ? $request->user_module_input : '';
        $duration = !empty($request->time_period) ? $request->time_period : 'Month';

        $user_module_price = 0;
        if (!empty($user_module)) {
            $user_module_array = explode(',', $user_module);
            foreach ($user_module_array as $key => $value) {
                $temp = ($duration == 'Year') ? ModulePriceByName($value)['yearly_price'] : ModulePriceByName($value)['monthly_price'];
                $user_module_price = $user_module_price + $temp;
            }
        }

        $plan_price = ($duration == 'Year') ? $plan->package_price_yearly : $plan->package_price_monthly;
       
        $price = $plan_price + $user_module_price;
        
        if ($request->coupon_code) {
            $validation = applyCouponDiscount($request->coupon_code, $price, auth()->id());
            if ($validation['valid']) {
                $price = $validation['final_amount'];
            }
        }

        if ($price <= 0) {
            $orderID = strtoupper(substr(uniqid(), -12));

            // Create bank transfer entry automatically approved
            $post = $request->all();
            unset($post['_token']);
            unset($post['_method']);
            unset($post['payment_receipt']);
            
            $bank_transfer_payment->order_id = $orderID;
            $bank_transfer_payment->user_id = Auth::id();
            $bank_transfer_payment->request = json_encode($post);
            $bank_transfer_payment->status = 'approved';
            $bank_transfer_payment->type = 'plan';
            $bank_transfer_payment->price = 0;
            $bank_transfer_payment->price_currency = admin_setting('defaultCurrency') ?? 'PKR';
            $bank_transfer_payment->created_by = creatorId();
            $bank_transfer_payment->save();
            $this->linkBankTransferMedia($bank_transfer_payment);

            $counter = [
                'user_counter' => -1,
                'storage_counter' => 0,
            ];
            $assignPlan = assignPlan($plan->id, $duration, $user_module, $counter, Auth::id());
            if ($assignPlan['is_success']) {
                Order::create([
                    'order_id' => $orderID,
                    'name' => Auth::user()->name,
                    'email' => Auth::user()->email,
                    'card_number' => null,
                    'card_exp_month' => null,
                    'card_exp_year' => null,
                    'plan_name' => !empty($plan->name) ? $plan->name : 'Basic Package',
                    'plan_id' => $plan->id,
                    'price' => 0,
                    'currency' => admin_setting('defaultCurrency') ?? 'PKR',
                    'txn_id' => '',
                    'payment_type' => __('Bank Transfer'),
                    'payment_status' => 'succeeded',
                    'receipt' => null,
                    'created_by' => Auth::id(),
                ]);
                if ($request->coupon_code) {
                    $coupon = Coupon::where('code', $request->coupon_code)->first();
                    if ($coupon) {
                        recordCouponUsage($coupon->id, Auth::id(), $orderID);
                    }
                }
                return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
            } else {
                return redirect()->route('plans.index')->with('error', __('Something went wrong, Please try again,'));
            }
        }
        $post = $request->all();
        unset($post['_token']);
        unset($post['_method']);
        unset($post['payment_receipt']);

        $orderID = strtoupper(substr(uniqid(), -12));

        $bank_transfer_payment->order_id = $orderID;
        $bank_transfer_payment->user_id = Auth::id();
        $bank_transfer_payment->request = json_encode($post);
        $bank_transfer_payment->status = 'pending';
        $bank_transfer_payment->type = 'plan';
        $bank_transfer_payment->price = $price;
        $bank_transfer_payment->price_currency = admin_setting('defaultCurrency') ?? 'PKR';
        $bank_transfer_payment->created_by = creatorId();
        $bank_transfer_payment->save();
        $this->linkBankTransferMedia($bank_transfer_payment);
        $msg = __('Plan payment request send successfully.') . ' ' . __('Your request will be approved by admin and then your plan is activated.');

        return redirect()->route('plans.index')->with('success', $msg);
    }

    public function index()
    {
        if (Auth::user()->can('manage-bank-transfer-requests')) {
            $requests = BankTransferPayment::with(['user'])
                ->where(function($q) {
                    // If user is not superadmin, show only their own requests
                    if (Auth::user()->type !== 'superadmin') {
                        $q->where('user_id', Auth::id());
                    }
                })
                ->when(request('order_number'), fn($q) => $q->where('order_id', 'like', '%' . request('order_number') . '%'))
                ->when(request('status'), fn($q) => $q->where('status', request('status')))
                ->when(request('user_name'), fn($q) => $q->whereHas('user', fn($query) => $query->where('name', 'like', '%' . request('user_name') . '%')))
                ->when(request('price_min'), fn($q) => $q->where('price', '>=', request('price_min')))
                ->when(request('price_max'), fn($q) => $q->where('price', '<=', request('price_max')))
                ->when(request('sort'), fn($q) => $q->orderBy(request('sort'), request('direction', 'asc')), fn($q) => $q->latest())
                ->paginate(request('per_page', 10))
                ->withQueryString();

            // Add plan data by parsing request JSON
            foreach ($requests as $request) {
                $requestData = json_decode($request->request, true);
                if (isset($requestData['plan_id'])) {
                    $request->plan = Plan::find($requestData['plan_id']);
                }
            }
            return Inertia::render('bank-transfer/index', [
                'requests' => $requests,
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdateBankTransferPaymentRequest $request, $id)
    {
        $validated = $request->validated();

        if (Auth::user()->can('approve-bank-transfer-requests')) {
            $bank_transfer_payment = BankTransferPayment::find($id);
            if ($bank_transfer_payment && $bank_transfer_payment->status == 'pending') {
                $bank_transfer_payment->status = $request->status;
                $bank_transfer_payment->save();

                if ($request->status == 'approved') {
                    $requests = json_decode($bank_transfer_payment->request);
                    $plan = Plan::find($requests->plan_id);
                    $counter = [
                        'user_counter' => -1,
                        'storage_counter' => 0,
                    ];
                    $user_module = (isset($requests->user_module_input)) ? $requests->user_module_input : '';
                    $duration = (isset($requests->time_period)) ? $requests->time_period : 'Month';
                    $user = User::find($bank_transfer_payment->user_id);
                    $assignPlan = assignPlan($plan->id, $duration, $user_module, $counter, $bank_transfer_payment->user_id);
                    if ($assignPlan['is_success']) {
                        $order = Order::create([
                            'order_id' => $bank_transfer_payment->order_id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'card_number' => null,
                            'card_exp_month' => null,
                            'card_exp_year' => null,
                            'plan_name' => !empty($plan->name) ? $plan->name : 'Basic Package',
                            'plan_id' => $plan->id,
                            'price' => $bank_transfer_payment->price,
                            'currency' => $bank_transfer_payment->price_currency,
                            'txn_id' => '',
                            'payment_type' => __('Bank Transfer'),
                            'payment_status' => 'succeeded',
                            'receipt' => $bank_transfer_payment->attachment,
                            'created_by' => $bank_transfer_payment->user_id,
                        ]);
                        if (isset($requests->coupon_code)) {
                            $coupon = Coupon::where('code', $requests->coupon_code)->first();
                            if ($coupon) {
                                recordCouponUsage($coupon->id, $bank_transfer_payment->user_id, $bank_transfer_payment->order_id);
                            }
                        }
                    } else {
                        return redirect()->back()->with('error', __('Something went wrong, Please try again,'));
                    }

                    return redirect()->back()->with('success', __('The bank transfer request Approve successfully'));
                } else {
                    return redirect()->back()->with('success', __('Bank transfer request Reject successfully'));
                }
            } else {
                return response()->json(['error' => __('Request data not found!')], 401);
            }
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function reject(BankTransferPayment $payment)
    {
        if (Auth::user()->can('reject-bank-transfer-requests')) {

            $payment->update(['status' => 'rejected']);

            return redirect()->back()->with('success', __('The bank transfer request Reject successfully.'));
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    private function linkBankTransferMedia(BankTransferPayment $payment): void
    {
        if (!$payment->attachment) {
            return;
        }
        $media = \App\Services\MediaAttachmentService::resolveOrBackfill(
            $payment->attachment,
            BankTransferPayment::class,
            $payment->id,
            'bank_transfer_receipts',
            Auth::id(),
            creatorId(),
            \App\Services\MediaAttachmentService::ensureDirectory('Bank Transfer Receipts', creatorId(), Auth::id())
        );
        if ($media) {
            $payment->update(['media_id' => $media->id]);
        }
    }

    public function destroy(BankTransferPayment $payment)
    {
        if(Auth::user()->can('delete-bank-transfer-requests') && ( $payment->user_id == Auth::id() || Auth::user()->type == 'superadmin') ){
            if ($payment->status !== 'pending' && Auth::user()->type != 'superadmin') {
                return redirect()->back()->with('error', __('Only pending requests can be deleted.'));
            }
            if ($payment->media_id && $payment->media) {
                \App\Services\MediaAttachmentService::deleteMedia($payment->media);
            } elseif ($payment->attachment) {
                delete_file($payment->attachment);
            }
            $payment->delete();

            return back()->with('success', __('The bank transfer request has been deleted.'));
        }
        else{
            return back()->with('error', __('Permission denied'));
        }
    }
}
