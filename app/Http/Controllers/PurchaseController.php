<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Stripe SDK
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Checkout\Session as CheckoutSession;

class PurchaseController extends Controller
{
    /**
     * 購入画面（要 auth, verified）
     */
    public function show(Item $item)
    {
        // 自分の出品は買えない／売切れチェック
        if (auth()->check() && (int) $item->user_id === (int) auth()->id()) {
            return redirect()->route('items.show', $item)
                ->with('error', '自分の商品は購入できません。');
        }
        if ((int) ($item->status ?? 1) === 0) {
            return redirect()->route('items.show', $item)
                ->with('error', 'この商品は売り切れです。');
        }

        $user = auth()->user()->load('profile');

        // 表示用
        $quantity       = 1;
        $shippingFee    = 0;
        $subtotal       = (int) $item->price * $quantity;
        $total          = $subtotal + $shippingFee;
        $paymentOptions = [
            'credit_card'       => 'カード支払い',
            'convenience_store' => 'コンビニ払い',
            'bank_transfer'     => '銀行振込',
        ];

        return view('purchase.show', [
            'item'           => $item,
            'profile'        => $user->profile,
            'quantity'       => $quantity,
            'shippingFee'    => $shippingFee,
            'subtotal'       => $subtotal,
            'total'          => $total,
            'paymentOptions' => $paymentOptions,
        ]);
    }

    /**
     * （レガシー）アプリ内で即確定する処理
     * 使わない場合はルートから外してください。
     */
    public function store(Request $request, Item $item)
    {
        $validated = $request->validate([
            'quantity'       => ['required', 'integer', 'min:1', 'max:10'],
            'payment_method' => ['required', 'in:convenience_store,credit_card,bank_transfer'],
        ]);

        $profile = auth()->user()->load('profile')->profile;
        if (!$profile || empty($profile->postal_code) || empty($profile->address)) {
            return back()
                ->withErrors(['address' => '配送先が未設定です。「変更する」から住所を登録してください。'])
                ->withInput();
        }

        try {
            DB::transaction(function () use ($item, $validated, $profile) {
                $fresh = Item::lockForUpdate()->findOrFail($item->id);

                if ((int) $fresh->user_id === (int) auth()->id()) {
                    abort(403, '自分の商品は購入できません。');
                }
                if ((int) ($fresh->status ?? 1) === 0) {
                    abort(409, '売り切れです。');
                }

                $qty       = (int) $validated['quantity'];
                $unitPrice = (int) $fresh->price;
                $shipping  = 0;
                $total     = $unitPrice * $qty + $shipping;

                $data = [
                    'user_id'        => auth()->id(),
                    'item_id'        => $fresh->id,
                    'price'          => $unitPrice,
                    'payment_method' => $validated['payment_method'],
                ];

                // 数量・送料・合計（存在するカラムのみ）
                if (Schema::hasColumn('purchases', 'quantity'))     $data['quantity'] = $qty;
                if (Schema::hasColumn('purchases', 'shipping_fee')) $data['shipping_fee'] = $shipping;
                if (Schema::hasColumn('purchases', 'total'))        $data['total'] = $total;

                // 住所系（delivery_* / 通常名 両対応）
                $postal = $this->normalizePostal((string) ($profile->postal_code ?? ''));
                $addr   = $profile->address        ?? null;
                $bldg   = $profile->building_name  ?? null;

                if (Schema::hasColumn('purchases', 'postal_code'))          $data['postal_code'] = $postal;
                if (Schema::hasColumn('purchases', 'delivery_postal_code')) $data['delivery_postal_code'] = $postal;

                if (Schema::hasColumn('purchases', 'address'))              $data['address'] = $addr;
                if (Schema::hasColumn('purchases', 'delivery_address'))     $data['delivery_address'] = $addr;

                if (Schema::hasColumn('purchases', 'building_name'))        $data['building_name'] = $bldg;
                if (Schema::hasColumn('purchases', 'delivery_building'))    $data['delivery_building'] = $bldg;

                Purchase::create($data);

                // 売切れに更新
                $fresh->status = 0;
                $fresh->save();
            });
        } catch (\Throwable $e) {
            $msg = $e->getCode() === 409 ? 'この商品は売り切れです。' : ($e->getMessage() ?: '購入に失敗しました。');
            return back()->with('error', $msg);
        }

        return redirect()->route('items.show', $item)->with('success', '購入が完了しました！');
    }

    /**
     * Stripe Checkout 開始（カード／コンビニ／銀行振込）
     * Webhook 側（StripeWebhookController）が決済確定後に items.status=0 を更新します。
     */
    public function checkout(Request $request, Item $item)
    {
        // 自分出品/売切れ防止
        if ((int) $item->user_id === (int) auth()->id()) {
            return back()->with('error', '自分の商品は購入できません。');
        }
        if ((int) ($item->status ?? 1) === 0) {
            return back()->with('error', 'この商品は売り切れです。');
        }

        // UI の値（long/short 両対応） + 配送先（nullableで受ける）
        $validated = $request->validate([
            'pay_method'             => 'required|in:credit_card,convenience_store,bank_transfer,card,konbini,bank',
            'delivery_postal_code'   => 'nullable|string|max:16',
            'delivery_address'       => 'nullable|string|max:255',
            'delivery_building_name' => 'nullable|string|max:255',
        ]);

        $method = $validated['pay_method'];
        $normalized = match ($method) {
            'credit_card', 'card'          => 'card',
            'convenience_store', 'konbini' => 'konbini',
            'bank_transfer', 'bank'        => 'bank',
            default                        => 'card',
        };

        Stripe::setApiKey(config('services.stripe.secret'));

        $lineItems = [[
            'price_data' => [
                'currency'     => 'jpy',
                'unit_amount'  => (int) $item->price,     // 円（整数）
                'product_data' => ['name' => $item->name],
            ],
            'quantity' => 1,
        ]];

        // 配送先（request → session → プロフィール の順で補完）
        $user = auth()->user()->load('profile');
        $delivery = [
            'delivery_postal_code'   => $this->normalizePostal((string) ($validated['delivery_postal_code'] ?? session('delivery.delivery_postal_code', optional($user->profile)->postal_code ?? ''))),
            'delivery_address'       => (string) ($validated['delivery_address'] ?? session('delivery.delivery_address', optional($user->profile)->address ?? '')),
            'delivery_building_name' => (string) ($validated['delivery_building_name'] ?? session('delivery.delivery_building_name', optional($user->profile)->building_name ?? '')),
        ];

        // Checkout 本体 + PaymentIntent の両方に metadata（重要）
        $baseMeta = array_merge([
            'item_id'    => (string) $item->id,
            'user_id'    => (string) auth()->id(),
            'pay_method' => $normalized, // card/konbini/bank
        ], $delivery);

        $params = [
            'mode'        => 'payment',
            'line_items'  => $lineItems,
            'success_url' => route('purchase.success') . '?item=' . $item->id . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('items.show', $item) . '?result=cancel',

            'metadata'             => $baseMeta,
            'payment_intent_data'  => ['metadata' => $baseMeta],
        ];

        if ($normalized === 'card') {
            $params['payment_method_types'] = ['card'];
        } elseif ($normalized === 'konbini') {
            $params['payment_method_types'] = ['konbini'];
        } else {
            // 銀行振込（Customer balance / jp_bank_transfer）
            $user = auth()->user();

            // Stripe Customer が必要。メール形式が不正ならダミーで補正。
            $isValidEmail = filter_var($user->email, FILTER_VALIDATE_EMAIL) && preg_match('/.+@.+\..+/', $user->email);
            if (!$user->stripe_customer_id || !$isValidEmail) {
                $email    = $isValidEmail ? $user->email : 'test+' . $user->id . '@example.com';
                $customer = Customer::create([
                    'email' => $email,
                    'name'  => $user->name ?: 'User ' . $user->id,
                ]);
                $user->forceFill(['stripe_customer_id' => $customer->id])->save();
            }

            $params['customer'] = $user->stripe_customer_id;
            $params['payment_method_types'] = ['customer_balance'];
            $params['payment_method_options'] = [
                'customer_balance' => [
                    'funding_type'  => 'bank_transfer',
                    'bank_transfer' => ['type' => 'jp_bank_transfer'],
                ],
            ];
        }

        $session = CheckoutSession::create($params);
        return redirect()->away($session->url);
    }

    /**
     * 成功戻り（表示用）
     * 確定は Webhook で行うため、ここではメッセージのみ。
     */
    public function success(Request $request)
    {
        // 一時配送先をクリア（任意）
        session()->forget(['delivery', 'delivery_item_id']);

        return redirect()
            ->route('items.index')
            ->with('status', 'お支払い手続きが開始されました。入金確認後に購入が確定します。');
    }

    /** 郵便番号を 123-4567 に整形 */
    private function normalizePostal(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);
        return strlen($digits) === 7
            ? substr($digits, 0, 3) . '-' . substr($digits, 3)
            : $value;
    }
}
