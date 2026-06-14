<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public order tracking resource - excludes sensitive personal information.
 * Used for order tracking page where no authentication is required.
 */
class OrderTrackingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusModel = \App\Models\OrderStatus::findByKey($this->status);

        return [
            'order_number'   => $this->order_number,
            'status'         => $this->status,
            'status_label'   => $statusModel?->label ?? $this->status,
            'status_color'   => $statusModel?->color ?? '#6b7280',
            'payment_status' => $this->payment_status,
            'total'          => $this->total,
            'items_count'    => $this->whenLoaded('items', fn () => $this->items->sum('quantity'), 0),
            'created_at'     => $this->created_at?->format('Y-m-d H:i:s'),
            'paid_at'        => $this->paid_at?->format('Y-m-d H:i:s'),
            'shipped_at'     => $this->shipped_at?->format('Y-m-d H:i:s'),
            'delivered_at'   => $this->delivered_at?->format('Y-m-d H:i:s'),

            // Progress timeline for UI
            'timeline'       => $this->buildTimeline(),
        ];
    }

    /**
     * Build a timeline of order status for display.
     */
    private function buildTimeline(): array
    {
        // Load status models for color information
        $processingStatus = \App\Models\OrderStatus::findByKey('processing');
        $shippedStatus = \App\Models\OrderStatus::findByKey('shipped');
        $deliveredStatus = \App\Models\OrderStatus::findByKey('delivered');

        $timeline = [
            [
                'key'         => 'created',
                'label'       => 'ثبت سفارش',
                'date'        => $this->created_at?->format('Y-m-d H:i'),
                'completed'   => true,
                'current'     => in_array($this->status, ['pending', 'payment_pending', 'payment_failed']),
                'color'       => '#6b7280', // gray-500
            ],
            [
                'key'         => 'paid',
                'label'       => 'پرداخت',
                'date'        => $this->paid_at?->format('Y-m-d H:i'),
                'completed'   => $this->paid_at !== null,
                'current'     => $this->payment_status === 'paid' && !$this->shipped_at,
                'color'       => '#10b981', // emerald-500 (success color)
            ],
            [
                'key'         => 'processing',
                'label'       => 'پردازش',
                'date'        => null,
                'completed'   => $this->shipped_at !== null || ($this->status !== 'pending' && $this->status !== 'payment_pending'),
                'current'     => $this->paid_at && !$this->shipped_at && $this->status === 'processing',
                'color'       => $processingStatus?->color ?? '#3b82f6', // blue-500 or from status
            ],
            [
                'key'         => 'shipped',
                'label'       => 'ارسال شده',
                'date'        => $this->shipped_at?->format('Y-m-d H:i'),
                'completed'   => $this->shipped_at !== null,
                'current'     => $this->shipped_at && !$this->delivered_at,
                'color'       => $shippedStatus?->color ?? '#f97316', // orange-500 or from status
            ],
            [
                'key'         => 'delivered',
                'label'       => 'تحویل داده شده',
                'date'        => $this->delivered_at?->format('Y-m-d H:i'),
                'completed'   => $this->delivered_at !== null,
                'current'     => $this->delivered_at !== null,
                'color'       => $deliveredStatus?->color ?? '#22c55e', // green-500 or from status
            ],
        ];

        return $timeline;
    }
}
