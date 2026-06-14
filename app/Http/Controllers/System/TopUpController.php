<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wallet;
use Midtrans\Snap;
use Midtrans\Config;
use Illuminate\Support\Facades\DB;

class TopUpController extends Controller
{
    public function index(Request $request)
    {
        $credits = 0;

        if (Auth::check()) {
            $wallet = Wallet::where('user_id', Auth::id())->first();
            $credits = $wallet ? $wallet->credits : 0;
        }

        return view('app.gallery.topup', compact('credits'));
    }

    public function createTransaction(Request $request)
    {
        $user = Auth::user();

        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $orderId = 'ORDER-' . uniqid();

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $request->price,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        return response()->json([
            'snap_token' => $snapToken,
            'order_id' => $orderId,
        ]);
    }    

    public function handleSuccess(Request $request)
    {
        $user = Auth::user();

        DB::transaction(function () use ($user, $request) {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['credits' => 0]
            );

            $wallet->increment('credits', $request->credits);
        });

        return response()->json(['success' => true]);
    }
}
