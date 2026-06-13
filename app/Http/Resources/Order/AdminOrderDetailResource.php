<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusModel = \App\Models\OrderStatus::findByKey($this->status);

        return [
            'id'             => $this->id,
            'order_number'   => $this->order_number,
            'customer_name'  => $this->whenLoaded('user', fn () => $this->user?->name),
            'user'           => $this->whenLoaded('user', fn () => $this->user ? [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ] : null),
            'status'         => $this->status,
            'status_label'   => $statusModel?->label ?? $this->status,
            'status_color'   => $statusModel?->color ?? '#6b7280',
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'shipping_code'  => $this->shipping_code,
            'subtotal'       => $this->subtotal,
            'tax'            => $this->tax,
            'shipping'       => $this->shipping,
            'discount'       => $this->discount,
            'total'          => $this->total,
            'notes'          => $this->notes,
            'admin_notes'    => $this->admin_notes,
            'items'          => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id'           => $item->id,
                'product_name' => $item->product_name,
                'vendor_name'  => $item->vendor_name,
                'sku'          => $item->sku,
                'quantity'     => $item->quantity,
                'price'        => $item->price,
                'subtotal'     => $item->subtotal,
            ])),
            'address'        => $this->whenLoaded('orderAddress', fn () => $this->orderAddress ? [
                'first_name'  => $this->orderAddress->first_name,
                'last_name'   => $this->orderAddress->last_name,
                'phone'       => $this->orderAddress->phone,
                'address'     => $this->orderAddress->address,
                'city'        => $this->orderAddress->city,
                'province'    => $this->orderAddress->province,
                'postal_code' => $this->orderAddress->postal_code,
            ] : null),
            'latest_transaction' => $this->whenLoaded('latestTransaction', fn () => $this->latestTransaction ? [
                'id'             => $this->latestTransaction->id,
                'driver'         => $this->latestTransaction->driver,
                'gateway_ref'    => $this->latestTransaction->gateway_ref,
                'status'         => $this->latestTransaction->status,
                'amount'         => $this->latestTransaction->amount,
                'card_last_four' => $this->latestTransaction->card_last_four,
                'receipt_path'   => $this->latestTransaction->receipt_path
                    ? asset('storage/' . $this->latestTransaction->receipt_path)
                    : null,
                'paid_at'        => $this->latestTransaction->paid_at,
            ] : null),
            'histories'      => $this->whenLoaded('histories', fn () => $this->histories->map(fn ($h) => [
                'id'          => $h->id,
                'event'       => $h->event,
                'event_label' => $this->resolveEventLabel($h->event),
                'from_status' => $h->from_status,
                'to_status'   => $h->to_status,
                'note'        => $h->note,
                'actor'       => $h->relationLoaded('user') && $h->user
                    ? ['id' => $h->user->id, 'name' => $h->user->name]
                    : null,
                'created_at'  => $h->created_at,
            ])),
            'created_at'     => $this->created_at,
            'paid_at'        => $this->paid_at,
            'shipped_at'     => $this->shipped_at,
            'delivered_at'   => $this->delivered_at,
            'cancelled_at'   => $this->cancelled_at,
        ];
    }

    private function resolveEventLabel(string $event): string
    {
        return match ($event) {
            'status_changed'         => 'تغییر وضعیت',
            'shipping_code_set'      => 'ثبت کد رهگیری',
            'notification_sent'      => 'ارسال اعلان',
            'payment_confirmed'      => 'تأیید پرداخت',
            'payment_rejected'       => 'رد پرداخت',
            'payment_status_changed' => 'تغییر وضعیت پرداخت',
            'order_created'          => 'ثبت سفارش',
            default                  => $event,
        };
    }
}
