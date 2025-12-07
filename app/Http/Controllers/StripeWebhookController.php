<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Stripe\Webhook;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripeWebhookController extends Controller
{
    /** デプロイ識別用 */
    private const VERSION = 'B-2025-10-10-dlv2';

    public function handle(Request $request)
    {
        Log::info('stripe webhook controller version', ['v' => self::VERSION]);

        $secret  = config('services.stripe.webhook_secret', env('STRIPE_WEBHOOK_SECRET'));
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            Log::warning('stripe webhook signature error', ['msg' => $e->getMessage()]);
            return response('invalid signature', 400);
        }

        $type = $event->type ?? 'unknown';
        Log::info('stripe webhook received', ['type' => $type]);

        try {
            switch ($type) {
                case 'payment_intent.succeeded': {
                    $pi = $event->data->object; // \Stripe\PaymentIntent
                    $this->handleWithPaymentIntent($pi);
                    break;
                }
                case 'checkout.session.async_payment_succeeded':
                case 'checkout.session.completed': {
                    $session = $event->data->object; // \Stripe\Checkout\Session
                    $this->handleWithSession($session);
                    break;
                }
                case 'charge.succeeded': {
                    $charge = $event->data->object; // \Stripe\Charge
                    $this->handleWithCharge($charge);
                    break;
                }
                default:
                    // 未対応タイプはACKのみ
                    break;
            }
        } catch (\Throwable $e) {
            Log::error('stripe webhook handler error', [
                'type' => $type,
                'msg'  => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'v'    => self::VERSION,
            ]);
            // Stripeの再送ループを防ぐため 200 を返す
            return response('ok', 200);
        }

        return response('ok', 200);
    }

    /* ========================
     * 各イベントの取り回し
     * ======================== */

    private function handleWithPaymentIntent($pi): void
    {
        Stripe::setApiKey(config('services.stripe.secret', env('STRIPE_SECRET')));

        $md = $pi->metadata ?? null;

        $itemId   = isset($md->item_id) ? (int)$md->item_id : null;
        $userId   = isset($md->user_id) ? (int)$md->user_id : null;
        $pm       = $md->pay_method ?? null;                       // card/konbini/bank
        $pmFixed  = $this->normalizePaymentMethod($pm);            // credit_card 等に正規化
        $stripeId = $pi->id;                                       // ★ 必須

        // ★ 配送先は delivery_* をそのまま採用（プロフィールは参照しない）
        $dPostal   = (string)($md->delivery_postal_code   ?? '');
        $dAddress  = (string)($md->delivery_address       ?? '');
        $dBuilding = (string)($md->delivery_building_name ?? '');

        Log::info('handleWithPaymentIntent payload', [
            'item_id' => $itemId, 'user_id' => $userId,
            'pm_raw' => $pm, 'pm_fixed' => $pmFixed, 'stripe_payment_id' => $stripeId,
            'delivery' => compact('dPostal', 'dAddress', 'dBuilding'),
        ]);

        if (!$itemId)   throw new \RuntimeException('item_id missing on PaymentIntent (v '.self::VERSION.')');
        if (!$stripeId) throw new \RuntimeException('stripe_payment_id missing on PI (v '.self::VERSION.')');

        $this->markSoldAndRecord($itemId, $userId, $pmFixed, $stripeId, $dPostal, $dAddress, $dBuilding);
    }

    private function handleWithSession($session): void
    {
        Stripe::setApiKey(config('services.stripe.secret', env('STRIPE_SECRET')));

        $md = $session->metadata ?? null;

        $itemId   = isset($md->item_id) ? (int)$md->item_id : null;
        $userId   = isset($md->user_id) ? (int)$md->user_id : null;
        $pm       = $md->pay_method ?? null;
        $pmFixed  = $this->normalizePaymentMethod($pm);
        $stripeId = $session->payment_intent ?? null; // pi_...

        // ★ delivery_* を拾う（sessionに無ければ後でPIから補完）
        $dPostal   = (string)($md->delivery_postal_code   ?? '');
        $dAddress  = (string)($md->delivery_address       ?? '');
        $dBuilding = (string)($md->delivery_building_name ?? '');

        if (!$itemId || !$userId || !$pmFixed || !$stripeId || !$dPostal || !$dAddress) {
            if (empty($session->payment_intent)) {
                throw new \RuntimeException('payment_intent missing on Checkout Session (v '.self::VERSION.')');
            }
            $pi        = PaymentIntent::retrieve($session->payment_intent);
            $piMd      = $pi->metadata ?? null;
            $itemId    = $itemId    ?: (isset($piMd->item_id) ? (int)$piMd->item_id : null);
            $userId    = $userId    ?: (isset($piMd->user_id) ? (int)$piMd->user_id : null);
            $pmFixed   = $pmFixed   ?: $this->normalizePaymentMethod($piMd->pay_method ?? null);
            $stripeId  = $stripeId  ?: $pi->id;

            // ★ delivery_* を PI から補完
            $dPostal   = $dPostal   ?: (string)($piMd->delivery_postal_code   ?? '');
            $dAddress  = $dAddress  ?: (string)($piMd->delivery_address       ?? '');
            $dBuilding = $dBuilding ?: (string)($piMd->delivery_building_name ?? '');
        }

        Log::info('handleWithSession payload', [
            'item_id' => $itemId, 'user_id' => $userId,
            'pm_fixed' => $pmFixed, 'stripe_payment_id' => $stripeId,
            'delivery' => compact('dPostal', 'dAddress', 'dBuilding'),
        ]);

        if (!$itemId)   throw new \RuntimeException('item_id missing on Session/PI (v '.self::VERSION.')');
        if (!$stripeId) throw new \RuntimeException('stripe_payment_id missing (v '.self::VERSION.')');

        $this->markSoldAndRecord($itemId, $userId, $pmFixed, $stripeId, $dPostal, $dAddress, $dBuilding);
    }

    private function handleWithCharge($charge): void
    {
        Stripe::setApiKey(config('services.stripe.secret', env('STRIPE_SECRET')));

        $stripeId = $charge->payment_intent ?: $charge->id; // 可能なら PI を優先
        $md       = $charge->metadata ?? null;

        $itemId   = isset($md->item_id) ? (int)$md->item_id : null;
        $userId   = isset($md->user_id) ? (int)$md->user_id : null;
        $pmFixed  = $this->normalizePaymentMethod($md->pay_method ?? null);

        // ★ delivery_* を拾う。無ければ PI から補完
        $dPostal   = (string)($md->delivery_postal_code   ?? '');
        $dAddress  = (string)($md->delivery_address       ?? '');
        $dBuilding = (string)($md->delivery_building_name ?? '');

        if ((!$itemId || !$dPostal || !$dAddress) && !empty($charge->payment_intent)) {
            $pi        = PaymentIntent::retrieve($charge->payment_intent);
            $piMd      = $pi->metadata ?? null;
            $itemId    = $itemId   ?: (isset($piMd->item_id) ? (int)$piMd->item_id : null);
            $userId    = $userId   ?: (isset($piMd->user_id) ? (int)$piMd->user_id : null);
            $pmFixed   = $pmFixed  ?: $this->normalizePaymentMethod($piMd->pay_method ?? null);
            $dPostal   = $dPostal   ?: (string)($piMd->delivery_postal_code   ?? '');
            $dAddress  = $dAddress  ?: (string)($piMd->delivery_address       ?? '');
            $dBuilding = $dBuilding ?: (string)($piMd->delivery_building_name ?? '');
        }

        Log::info('handleWithCharge payload', [
            'item_id' => $itemId, 'user_id' => $userId,
            'pm_fixed' => $pmFixed, 'stripe_payment_id' => $stripeId,
            'delivery' => compact('dPostal', 'dAddress', 'dBuilding'),
        ]);

        if (!$itemId)   throw new \RuntimeException('item_id missing on Charge/PI (v '.self::VERSION.')');
        if (!$stripeId) throw new \RuntimeException('stripe_payment_id missing (v '.self::VERSION.')');

        $this->markSoldAndRecord($itemId, $userId, $pmFixed, $stripeId, $dPostal, $dAddress, $dBuilding);
    }

    private function normalizePaymentMethod(?string $raw): string
    {
        return match ($raw) {
            'card', 'credit_card', null         => 'credit_card',
            'konbini', 'convenience_store'      => 'convenience_store',
            'bank', 'bank_transfer'              => 'bank_transfer',
            default                               => 'credit_card',
        };
    }

    /* ========================
     * 中核：冪等な在庫反映＋購入作成（delivery_*を使用）
     * ======================== */
    private function markSoldAndRecord(
        int $itemId,
        ?int $buyerUserId,
        string $paymentMethod,
        string $stripePaymentId,
        string $deliveryPostalCode,
        string $deliveryAddress,
        ?string $deliveryBuildingName
    ): void {
        DB::transaction(function () use (
            $itemId, $buyerUserId, $paymentMethod, $stripePaymentId,
            $deliveryPostalCode, $deliveryAddress, $deliveryBuildingName
        ) {
            /** @var Item|null $item */
            $item = Item::lockForUpdate()->find($itemId);
            if (!$item) {
                Log::warning('stripe markSoldAndRecord: item not found', ['item_id' => $itemId]);
                return;
            }

            // 既に同一PIで作成済みなら冪等終了（ついでにSOLDだけ合わせる）
            if (Purchase::where('stripe_payment_id', $stripePaymentId)->exists()) {
                if ((int)($item->status ?? 1) !== 0) {
                    $item->status = 0;
                    $item->save();
                    Log::info('item marked sold (idempotent)', ['item_id' => $itemId]);
                }
                return;
            }

            // 金額計算
            $unitPrice = (int) $item->price;
            $qty       = 1;
            $shipping  = 0;
            $total     = $unitPrice * $qty + $shipping;

            // ベースデータ（★ stripe_payment_id は必須）
            $data = [
                'user_id'           => $buyerUserId,
                'item_id'           => $item->id,
                'price'             => $unitPrice,
                'payment_method'    => $paymentMethod,
                'stripe_payment_id' => $stripePaymentId,
            ];

            // オプション列が存在する場合のみ詰める
            if (Schema::hasColumn('purchases', 'quantity'))      $data['quantity']      = $qty;
            if (Schema::hasColumn('purchases', 'shipping_fee'))  $data['shipping_fee']  = $shipping;
            if (Schema::hasColumn('purchases', 'total'))         $data['total']         = $total;

            // ★ 配送先を delivery_* に保存（カラムがあれば）
            if (Schema::hasColumn('purchases', 'delivery_postal_code'))    {
                $data['delivery_postal_code'] = $this->normalizePostal($deliveryPostalCode);
            }
            if (Schema::hasColumn('purchases', 'delivery_address'))        {
                $data['delivery_address'] = $deliveryAddress;
            }
            if (Schema::hasColumn('purchases', 'delivery_building_name'))  {
                $data['delivery_building_name'] = $deliveryBuildingName;
            }
            // 互換：もし旧/誤カラムがある場合のフォールバック（存在するなら詰める）
            if (Schema::hasColumn('purchases', 'postal_code') && empty($data['delivery_postal_code'])) {
                $data['postal_code'] = $this->normalizePostal($deliveryPostalCode);
            }
            if (Schema::hasColumn('purchases', 'address') && empty($data['delivery_address'])) {
                $data['address'] = $deliveryAddress;
            }
            if (Schema::hasColumn('purchases', 'building_name') && empty($data['delivery_building_name'])) {
                $data['building_name'] = $deliveryBuildingName;
            }
            if (Schema::hasColumn('purchases', 'delivery_building') && !empty($deliveryBuildingName)) {
                $data['delivery_building'] = $deliveryBuildingName;
            }

            // ログ（検証用）
            Log::info('purchase insert payload', [
                'keys' => array_keys($data),
                'stripe_payment_id' => $data['stripe_payment_id'] ?? null,
                'v' => self::VERSION,
            ]);

            // 作成（マスアサイン漏れでも落ちない保険）
            $purchase = EloquentModel::unguarded(function () use ($data) {
                return Purchase::create($data);
            });

            Log::info('purchase created', [
                'id'                => $purchase->id,
                'item_id'           => $item->id,
                'user_id'           => $buyerUserId,
                'stripe_payment_id' => $stripePaymentId,
                'v'                 => self::VERSION,
            ]);

            // 在庫（SOLD）更新
            if ((int)($item->status ?? 1) !== 0) {
                $item->status = 0;
                $item->save();
                Log::info('item marked sold', ['item_id' => $itemId, 'v' => self::VERSION]);
            }
        });
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
