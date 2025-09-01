<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use Stripe\Webhook;
use App\Models\User;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        
        $request->validate([
            'price_key' => [
                'required',
                'string',
                
                Rule::in(array_keys(config('stripe.prices'))),
            ],
        ]);

        
        $priceId = config('stripe.prices.' . $request->input('price_key'));

        $user = auth()->user();

        
        
        $stripeCustomerId = $user->stripe_customer_id;
        if (!$stripeCustomerId) {
            $customer = \Stripe\Customer::create(['email' => $user->email]);
            $stripeCustomerId = $customer->id;
            $user->update(['stripe_customer_id' => $stripeCustomerId]);
        }

        
        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'subscription',
            'customer' => $stripeCustomerId, 
            'line_items' => [[
                'price' => $priceId, 
                'quantity' => 1,
            ]],
            'success_url' => url('/billing/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/billing/cancel'),
            
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
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET'); 

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
                
                $user = User::where('email', $session->customer_email)->first();
                if ($user) {
                    $user->update([
                        'subscription_status' => 'active',
                        'stripe_customer_id' => $session->customer,
                        'stripe_subscription_id' => $session->subscription,
                    ]);
                }
                break;

            case 'invoice.payment_succeeded':
                
                $user = User::where('stripe_subscription_id', $session->subscription)->first();
                if ($user) {
                    $user->update(['subscription_status' => 'active']);
                }
                break;

            case 'customer.subscription.deleted':
                
                $user = User::where('stripe_subscription_id', $session->id)->first();
                if ($user) {
                    $user->update(['subscription_status' => 'canceled']);
                }
                break;

                return response()->json(['status' => 'success']);
        }
    }
}
