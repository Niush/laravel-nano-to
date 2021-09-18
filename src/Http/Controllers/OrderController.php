<?php

namespace Niush\LaravelNanoTo\Http\Controllers;

class OrderController extends Controller
{
    public function success($id)
    {
        return 'ok - Create your own named route "nano-to-success" with one param. E.g. /order/success/{id}';
    }

    public function cancel($id)
    {
        return 'ko - Create your own named route "nano-to-cancel" with one param. E.g. /order/cancel/{id}';
    }

    public function webhook($id)
    {
        // dd($request->all());
        // dd($request->header('Webhook-Secret'));
        Log::info(
            "Nano.to Payment received in Vendor Webhook. You should have setup your own with proper named route. Secret: ". $request->header('Webhook-Secret'),
            $request->all()
        );
        return 'webhook - Create your own named route "nano-to-webhook" with one param. E.g. /order/webhook/{id}';
    }
}
