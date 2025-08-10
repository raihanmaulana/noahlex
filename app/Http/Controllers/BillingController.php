<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Webhook;
use Illuminate\Validation\Rule;
use Stripe\Checkout\Session;

class BillingController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        // 1. Validasi input dari frontend
        $request->validate([
            'price_key' => [
                'required',
                'string',
                // Pastikan 'price_key' yang dikirim valid dan ada di file config kita
                Rule::in(array_keys(config('stripe.prices'))),
            ],
        ]);

        // 2. Ambil Price ID dari config berdasarkan key yang divalidasi
        $priceId = config('stripe.prices.' . $request->input('price_key'));

        $user = auth()->user();

        // 3. Cek atau buat Stripe Customer ID untuk user
        // Ini memastikan 1 user = 1 customer di Stripe
        $stripeCustomerId = $user->stripe_customer_id;
        if (!$stripeCustomerId) {
            $customer = \Stripe\Customer::create(['email' => $user->email]);
            $stripeCustomerId = $customer->id;
            $user->update(['stripe_customer_id' => $stripeCustomerId]);
        }

        // 4. Buat Sesi Checkout dengan harga dinamis
        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'subscription',
            'customer' => $stripeCustomerId, // Gunakan customer ID yang sudah ada
            'line_items' => [[
                'price' => $priceId, // <-- Harga sekarang dinamis!
                'quantity' => 1,
            ]],
            'success_url' => url('/billing/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/billing/cancel'),
            // 'customer_email' tidak perlu lagi jika sudah menggunakan 'customer'
        ]);

        return response()->json(['id' => $session->id]);
    }


    public function success()
    {
        return "Payment Success! Subscription is now active.";
    }

    public function cancel()
    {
        return "Payment Canceled.";
    }

    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->server('HTTP_STRIPE_SIGNATURE');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET'); // dari Stripe Dashboard

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->server('HTTP_STRIPE_SIGNATURE'),
                config('stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        $session = $event->data->object;

        switch ($event->type) {
            case 'checkout.session.completed':
                // Logika saat user pertama kali subscribe
                $user = \App\Models\User::where('email', $session->customer_email)->first();
                if ($user) {
                    $user->update([
                        'subscription_status' => 'active',
                        'stripe_customer_id' => $session->customer,
                        'stripe_subscription_id' => $session->subscription,
                    ]);
                }
                break;

            case 'invoice.payment_succeeded':
                // Logika saat subscription berhasil diperpanjang
                $user = \App\Models\User::where('stripe_subscription_id', $session->subscription)->first();
                if ($user) {
                    $user->update(['subscription_status' => 'active']);
                }
                break;

            case 'customer.subscription.deleted':
                // Logika saat subscription dibatalkan atau berakhir
                $user = \App\Models\User::where('stripe_subscription_id', $session->id)->first();
                if ($user) {
                    $user->update(['subscription_status' => 'canceled']);
                }
                break;

                return response()->json(['status' => 'success']);
        }
    }
}
