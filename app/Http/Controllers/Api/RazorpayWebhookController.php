<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

/**
 * Handles Razorpay webhook events — server-to-server confirmation of
 * payment success, independent of the customer's device.
 *
 * Why this exists alongside the client-side verify/callback flow already
 * in OrderController and CheckoutController: that flow depends on the
 * customer's app or browser staying connected long enough to send the
 * verification request back after paying. If they close the app, lose
 * signal, or the process gets killed right after Razorpay captures the
 * payment, that request never arrives — Razorpay has the money, but the
 * order stays 'pending' forever. Razorpay sends this webhook directly
 * from their servers regardless of what happens on the customer's device,
 * and retries it if our endpoint doesn't respond, so it's what actually
 * guarantees the order gets marked paid.
 *
 * This does not replace the existing verify/callback endpoints — both can
 * fire for the same payment (normally the client-side one first, this one
 * shortly after, or this one alone if the client-side call never made it).
 * Every update here is guarded by an idempotency check so processing the
 * same event twice — including Razorpay's own retries — is always safe.
 */
class RazorpayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $signature = $request->header('X-Razorpay-Signature');
        $rawBody   = $request->getContent(); // must be the raw body — a re-encoded/parsed copy will not match the signature

        if (! $signature || ! config('services.razorpay.webhook_secret')) {
            Log::warning('Razorpay webhook rejected: missing signature or webhook_secret not configured.');
            return response()->json(['message' => 'Bad request'], 400);
        }

        try {
            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            $api->utility->verifyWebhookSignature($rawBody, $signature, config('services.razorpay.webhook_secret'));
        } catch (\Exception $e) {
            Log::warning('Razorpay webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $data  = json_decode($rawBody, true);
        $event = $data['event'] ?? null;

        // Only these two indicate a successful, capturable payment. Other
        // events (refund.*, payment.failed, dispute.*, etc.) are left to
        // the existing admin refund/dispute flows rather than handled here.
        if (! in_array($event, ['payment.captured', 'order.paid'], true)) {
            return response()->json(['message' => 'Event ignored, no action needed']);
        }

        $paymentEntity = $data['payload']['payment']['entity'] ?? null;
        $orderEntity   = $data['payload']['order']['entity'] ?? null;

        $order = $this->resolveOrder($paymentEntity, $orderEntity);

        if (! $order) {
            // Deliberately 200, not 404/422 — this tells Razorpay the
            // webhook was received and should NOT be retried. An order we
            // can't match now won't become matchable on retry; if this
            // happens often, check that notes.order_id is being sent
            // correctly on new orders (see OrderController@store and
            // PaymentService::initiatePayment).
            Log::warning("Razorpay webhook: could not match any order for event {$event}.", ['payload' => $data]);
            return response()->json(['message' => 'Order not found, ignoring']);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Already processed']);
        }

        $order->update([
            'payment_status' => 'paid',
            'payment_id'     => $paymentEntity['id'] ?? $order->payment_id,
            'status'         => $order->status === 'pending' ? 'confirmed' : $order->status,
            'confirmed_at'   => $order->confirmed_at ?? now(),
        ]);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status'   => 'confirmed',
            'comment'  => 'Payment confirmed via Razorpay webhook (' . $event . ').',
        ]);

        return response()->json(['message' => 'Processed']);
    }

    /**
     * Matches the webhook payload back to our internal order. Tries the
     * notes.order_id we embed at order-creation time first (our primary
     * key — the most direct, reliable match), falling back to the
     * order's receipt (our order_number) in case a future event type
     * doesn't carry notes the same way.
     */
    private function resolveOrder(?array $paymentEntity, ?array $orderEntity): ?Order
    {
        $internalId = $paymentEntity['notes']['order_id'] ?? $orderEntity['notes']['order_id'] ?? null;
        if ($internalId) {
            $order = Order::find($internalId);
            if ($order) return $order;
        }

        $receipt = $orderEntity['receipt'] ?? null;
        if ($receipt) {
            return Order::where('order_number', $receipt)->first();
        }

        return null;
    }
}
