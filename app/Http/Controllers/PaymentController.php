<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Webhook;
use Stripe\Subscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Show premium upgrade page
     */
    public function showUpgrade()
    {
        return view('premium.upgrade');
    }

    /**
     * Create Stripe Checkout session for premium subscription
     */
    public function createCheckoutSession(Request $request)
    {
        $user = Auth::user();
        
        if ($user->isPremium()) {
            return redirect()->route('dashboard')->with('error', 'Masz już aktywny plan Premium.');
        }

        try {
            // Create or get Stripe customer
            $customer = $this->getOrCreateStripeCustomer($user);

            // Create subscription checkout session
            $session = Session::create([
                'customer' => $customer->id,
                'payment_method_types' => ['card', ],//'blik', 'p24'
                'mode' => 'subscription',
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'pln',
                            'product_data' => [
                                'name' => 'CryptoNote Premium',
                                'description' => 'Miesięczny dostęp do AI sentiment analysis i funkcji Premium',
                            ],
                            'unit_amount' => 1900, // 19.00 PLN in grosze
                            'recurring' => [
                                'interval' => 'month',
                            ],
                        ],
                        'quantity' => 1,
                    ],
                ],
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'metadata' => [
                    'user_id' => $user->id,
                    'plan' => 'premium_monthly',
                ],
                'subscription_data' => [
                    'metadata' => [
                        'user_id' => $user->id,
                        'plan' => 'premium_monthly',
                    ],
                ],
            ]);

            return redirect($session->url);

        } catch (\Exception $e) {
            Log::error('Stripe checkout session creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('premium.upgrade')
                ->with('error', 'Błąd podczas tworzenia sesji płatności. Spróbuj ponownie.');
        }
    }

    /**
     * Handle successful payment
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        
        if (!$sessionId) {
            return redirect()->route('dashboard')
                ->with('error', 'Brak identyfikatora sesji płatności.');
        }

        try {
            $session = Session::retrieve($sessionId);
            
            if ($session->payment_status === 'paid') {
                $user = Auth::user();
                
                // Premium will be activated via webhook, but show success page
                return view('payment.success', [
                    'session' => $session,
                    'user' => $user
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Payment success page error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }

        return redirect()->route('dashboard')
            ->with('error', 'Błąd podczas weryfikacji płatności.');
    }

    /**
     * Handle cancelled payment
     */
    public function cancel()
    {
        return view('payment.cancel');
    }

    /**
     * Handle payment failure
     */
    public function failed()
    {
        return view('payment.failed');
    }

    /**
     * Show billing portal
     */
    public function billingPortal()
    {
        $user = Auth::user();
        
        if (!$user->isPremium() || !$user->stripe_customer_id) {
            return redirect()->route('dashboard')
                ->with('error', 'Nie masz aktywnego planu Premium.');
        }

        try {
            $portalSession = \Stripe\BillingPortal\Session::create([
                'customer' => $user->stripe_customer_id,
                'return_url' => route('dashboard'),
            ]);

            return redirect($portalSession->url);

        } catch (\Exception $e) {
            Log::error('Billing portal creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Błąd podczas otwierania panelu rozliczeń.');
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription()
    {
        $user = Auth::user();
        
        if (!$user->isPremium() || !$user->stripe_subscription_id) {
            return redirect()->route('dashboard')
                ->with('error', 'Nie masz aktywnej subskrypcji do anulowania.');
        }

        try {
            // Cancel at period end to allow user to use remaining time
            $subscription = Subscription::retrieve($user->stripe_subscription_id);
            $subscription->cancel(['at_period_end' => true]);

            return redirect()->route('dashboard')
                ->with('success', 'Subskrypcja zostanie anulowana na koniec okresu rozliczeniowego.');

        } catch (\Exception $e) {
            Log::error('Subscription cancellation failed', [
                'user_id' => $user->id,
                'subscription_id' => $user->stripe_subscription_id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Błąd podczas anulowania subskrypcji.');
        }
    }

    /**
     * Handle Stripe webhooks
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Exception $e) {
            Log::error('Webhook signature verification failed', ['error' => $e->getMessage()]);
            return response('Webhook signature verification failed.', 400);
        }

        Log::info('Stripe webhook received', ['type' => $event->type]);

        // Handle different webhook events
        switch ($event->type) {
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;
            
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;

            default:
                Log::info('Unhandled Stripe webhook event type: ' . $event->type);
        }

        return response('Webhook handled', 200);
    }

    /**
     * Handle subscription created/updated
     */
    private function handleSubscriptionUpdated($subscription)
    {
        $userId = $subscription->metadata->user_id ?? null;
        
        if (!$userId) {
            Log::warning('No user_id in subscription metadata', ['subscription_id' => $subscription->id]);
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            Log::warning('User not found for subscription', ['user_id' => $userId]);
            return;
        }

        // Update user premium status
        $user->update([
            'premium' => true,
            'premium_expires_at' => $subscription->current_period_end ? 
                now()->createFromTimestamp($subscription->current_period_end) : null,
            'stripe_customer_id' => $subscription->customer,
            'stripe_subscription_id' => $subscription->id,
        ]);

        Log::info('User premium status updated', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'status' => $subscription->status
        ]);
    }

    /**
     * Handle subscription deleted/cancelled
     */
    private function handleSubscriptionDeleted($subscription)
    {
        $user = User::where('stripe_subscription_id', $subscription->id)->first();
        
        if ($user) {
            $user->update([
                'premium' => false,
                'premium_expires_at' => null,
                'stripe_subscription_id' => null,
            ]);

            Log::info('User premium status deactivated', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id
            ]);
        }
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSucceeded($invoice)
    {
        $subscriptionId = $invoice->subscription;
        
        if ($subscriptionId) {
            $user = User::where('stripe_subscription_id', $subscriptionId)->first();
            
            if ($user) {
                // Extend premium if it was about to expire
                $subscription = Subscription::retrieve($subscriptionId);
                $user->update([
                    'premium' => true,
                    'premium_expires_at' => now()->createFromTimestamp($subscription->current_period_end),
                ]);

                Log::info('Premium subscription renewed', [
                    'user_id' => $user->id,
                    'invoice_id' => $invoice->id
                ]);
            }
        }
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed($invoice)
    {
        $subscriptionId = $invoice->subscription;
        
        if ($subscriptionId) {
            $user = User::where('stripe_subscription_id', $subscriptionId)->first();
            
            if ($user) {
                Log::warning('Premium payment failed', [
                    'user_id' => $user->id,
                    'invoice_id' => $invoice->id
                ]);

                // Optionally send notification to user about failed payment
                // You can implement email notification here
            }
        }
    }

    /**
     * Get or create Stripe customer
     */
    private function getOrCreateStripeCustomer(User $user)
    {
        if ($user->stripe_customer_id) {
            try {
                return Customer::retrieve($user->stripe_customer_id);
            } catch (\Exception $e) {
                Log::warning('Stripe customer not found, creating new one', [
                    'user_id' => $user->id,
                    'old_customer_id' => $user->stripe_customer_id
                ]);
            }
        }

        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }
}