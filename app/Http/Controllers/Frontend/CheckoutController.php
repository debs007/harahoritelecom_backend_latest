<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\ShippingZone;
use App\Notifications\OrderPlacedNotification;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index()
    {
        $cartItems = Cart::with(['product.images', 'variant'])
            ->where('user_id', auth()->id())
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $addresses     = auth()->user()->addresses()->get();
        $shippingZones = ShippingZone::where('is_active', true)->get();
        $codAvailable  = auth()->user()->canUseCod();
        $coupon        = session('coupon');
        $subtotal      = $cartItems->sum(fn($i) => $i->getSubtotal());
        $discount      = $coupon['discount'] ?? 0;

        // Calculate exchange discount from cart items
        $exchangeDiscount = 0;
        foreach ($cartItems as $item) {
            if ($item->exchange_data && !empty($item->exchange_data['brand'])) {
                $offer = \App\Models\ExchangeOffer::where('product_id', $item->product_id)
                    ->where('is_active', true)->first();
                if ($offer && !empty($item->exchange_data['condition'])) {
                    $tradeInPrice = !empty($item->exchange_data['trade_in_price_id'])
                        ? \App\Models\TradeInPrice::find($item->exchange_data['trade_in_price_id'])
                        : null;
                    $exchangeDiscount = $offer->calculateValue(
                        $item->exchange_data['condition'],
                        $tradeInPrice ? (float) $tradeInPrice->price : null
                    );
                }
                break;
            }
        }

        return view('frontend.checkout.index', compact(
            'cartItems', 'addresses', 'shippingZones', 'coupon', 'subtotal', 'discount', 'exchangeDiscount', 'codAvailable'
        ));
    }

    public function placeOrder(Request $request)
    {
        $request->validate([
            'address_id'       => 'required|exists:addresses,id',
            'shipping_zone_id' => 'required|exists:shipping_zones,id',
            'payment_method'   => 'required|in:razorpay,cod',
        ]);

        if ($request->payment_method === 'cod' && ! auth()->user()->canUseCod()) {
            return back()->with('error', 'Cash on Delivery is not available for your account.');
        }

        // Verify address belongs to the logged-in user
        $address = auth()->user()->addresses()->findOrFail($request->address_id);

        $cartItems    = Cart::with(['product.images', 'variant'])->where('user_id', auth()->id())->get();
        $shippingZone = ShippingZone::findOrFail($request->shipping_zone_id);
        $coupon       = session('coupon') ? Coupon::find(session('coupon.id')) : null;
        if ($coupon && ! $coupon->isValid()) {
            $coupon = null;
        }

        $subtotal = $cartItems->sum(fn($i) => $i->getSubtotal());

        $discount = 0;
        if ($coupon && $coupon->isProductSpecific()) {
            $productLineAmount = $cartItems->where('product_id', $coupon->product_id)
                ->sum(fn($i) => $i->getSubtotal());
            $discount = $productLineAmount > 0 ? $coupon->calculateDiscount($productLineAmount) : 0;
        } elseif ($coupon) {
            $discount = $coupon->calculateDiscount($subtotal);
        }

        // Check for exchange data (from any cart item)
        $exchangeData     = null;
        $exchangeDiscount = 0;
        foreach ($cartItems as $item) {
            if ($item->exchange_data && !empty($item->exchange_data['brand'])) {
                $exchangeData = $item->exchange_data;
                // Get the offer for the product
                $offer = \App\Models\ExchangeOffer::where('product_id', $item->product_id)
                    ->where('is_active', true)->first();
                if ($offer && !empty($exchangeData['condition'])) {
                    $tradeInPrice = !empty($exchangeData['trade_in_price_id'])
                        ? \App\Models\TradeInPrice::find($exchangeData['trade_in_price_id'])
                        : null;
                    $exchangeDiscount = $offer->calculateValue(
                        $exchangeData['condition'],
                        $tradeInPrice ? (float) $tradeInPrice->price : null
                    );
                }
                break;
            }
        }

        $shipping = $shippingZone->getRate($subtotal - $discount - $exchangeDiscount);
        $total    = max(0, $subtotal - $discount - $exchangeDiscount + $shipping);

        $order = null;

        DB::transaction(function () use (
            $request, $cartItems, $shippingZone, $coupon,
            $subtotal, $discount, $shipping, $total,
            $exchangeData, $exchangeDiscount, &$order
        ) {
            // Create exchange request if applicable
            $exchangeRequestId = null;
            if ($exchangeData && !empty($exchangeData['brand'])) {
                $cartItemWithExchange = $cartItems->first(fn($i) => !empty($i->exchange_data));
                $er = \App\Models\ExchangeRequest::create([
                    'user_id'           => auth()->id(),
                    'product_id'        => $cartItemWithExchange?->product_id ?? $cartItems->first()->product_id,
                    'trade_in_price_id' => $exchangeData['trade_in_price_id'] ?? null,
                    'old_phone_brand'   => $exchangeData['brand'],
                    'old_phone_model'   => $exchangeData['model'],
                    'imei'              => $exchangeData['imei'] ?? '',
                    'condition'         => $exchangeData['condition'],
                    'estimated_value'   => $exchangeDiscount,
                    'status'            => 'pending',
                ]);
                $exchangeRequestId = $er->id;
            }

            $order = Order::create([
                'order_number'       => Order::generateNumber(),
                'user_id'            => auth()->id(),
                'address_id'         => $request->address_id,
                'shipping_zone_id'   => $request->shipping_zone_id,
                'coupon_id'          => $coupon?->id,
                'exchange_request_id'=> $exchangeRequestId,
                'subtotal'           => $subtotal,
                'discount'           => $discount,
                'exchange_discount'  => $exchangeDiscount,
                'shipping_charge'    => $shipping,
                'tax'                => 0,
                'total'              => $total,
                'payment_method'     => $request->payment_method,
                'status'             => 'pending',
                'payment_status'     => 'pending',
            ]);

            foreach ($cartItems as $item) {
                // Build variant details: Color + RAM + Storage + any variant options
                $variantParts = [];

                $selectedColor = $item->selected_color ?? null;
                if ($selectedColor) {
                    $variantParts[] = 'Color: ' . $selectedColor;
                } elseif ($item->product->colors && count($item->product->colors) > 0) {
                    // Default to first color if none selected
                    $variantParts[] = 'Color: ' . $item->product->colors[0] . ' (default)';
                }

                if ($item->variant) {
                    if ($item->variant->ram)     $variantParts[] = 'RAM: '     . $item->variant->ram;
                    if ($item->variant->storage) $variantParts[] = 'Storage: ' . $item->variant->storage;
                    if ($item->variant->color && !$selectedColor) {
                        // override with variant color if no explicit color selected
                        $variantParts[0] = 'Color: ' . $item->variant->color;
                    }
                } else {
                    // Use product-level specs as variant details
                    if ($item->product->ram)     $variantParts[] = 'RAM: '     . $item->product->ram;
                    if ($item->product->storage) $variantParts[] = 'Storage: ' . $item->product->storage;
                }

                $variantDetails = implode(' | ', $variantParts) ?: null;

                OrderItem::create([
                    'order_id'        => $order->id,
                    'product_id'      => $item->product_id,
                    'variant_id'      => $item->variant_id,
                    'product_name'    => $item->product->name,
                    'variant_details' => $variantDetails,
                    'price'           => $item->variant
                        ? $item->variant->price
                        : $item->product->getCurrentPrice(),
                    'quantity'  => $item->quantity,
                    'subtotal'  => $item->getSubtotal(),
                ]);

                // Decrement stock
                if ($item->variant) {
                    $item->variant->decrement('stock', $item->quantity);
                } else {
                    $item->product->decrement('stock', $item->quantity);
                }
            }

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status'   => 'pending',
                'comment'  => 'Order placed by customer.',
            ]);

            if ($coupon) {
                $coupon->increment('used_count');
            }

            Cart::where('user_id', auth()->id())->delete();
            session()->forget('coupon');
        });

        // Send confirmation notification
        auth()->user()->notify(new OrderPlacedNotification($order));

        if ($request->payment_method === 'cod') {
            return redirect()
                ->route('checkout.success', $order)
                ->with('success', 'Order placed successfully!');
        }

        // Razorpay
        try {
            $paymentData = $this->paymentService->initiatePayment($order);
            return view('frontend.checkout.payment', compact('order', 'paymentData'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                'Razorpay initiation failed for order #' . $order->order_number . ': ' . $e->getMessage()
            );
            // The order (and its stock decrement) already happened above,
            // inside the transaction that just committed — so rather than
            // a raw crash here, send the customer to the order page with a
            // clear message that payment still needs to be completed.
            return redirect()->route('checkout.success', $order)->with(
                'error',
                'Your order #' . $order->order_number . ' was created, but online payment could not be started. Please contact support to complete payment — no charge has gone through yet.'
            );
        }
    }

    public function success(Order $order)
    {
        abort_if($order->user_id !== auth()->id(), 403);
        $order->load(['items.product', 'address']);
        return view('frontend.checkout.success', compact('order'));
    }

    public function razorpayCallback(Request $request)
    {
        $request->validate([
            'order_id'            => 'required|exists:orders,id',
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        $order = Order::findOrFail($request->order_id);
        abort_if($order->user_id !== auth()->id(), 403);

        $verified = $this->paymentService->verifyPayment(
            $request->razorpay_order_id,
            $request->razorpay_payment_id,
            $request->razorpay_signature
        );

        if ($verified) {
            $order->update([
                'payment_status' => 'paid',
                'payment_id'     => $request->razorpay_payment_id,
                'status'         => 'confirmed',
                'confirmed_at'   => now(),
            ]);
            OrderStatusLog::create([
                'order_id' => $order->id,
                'status'   => 'confirmed',
                'comment'  => 'Payment received via Razorpay.',
            ]);
            return redirect()
                ->route('checkout.success', $order)
                ->with('success', 'Payment successful! Order confirmed.');
        }

        $order->update(['payment_status' => 'failed']);
        return redirect()
            ->route('orders.show', $order)
            ->with('error', 'Payment verification failed. Please contact support.');
    }
}
