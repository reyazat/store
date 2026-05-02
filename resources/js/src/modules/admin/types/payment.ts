export interface PaymentMethod {
  id: number
  driver: string
  label: string
  description: string | null
  is_active: boolean
  sort_order: number
}

export interface AdminPaymentMethod extends PaymentMethod {
  config: Record<string, string>
  success_order_status: string
  failure_order_status: string
  cancel_order_status: string
}

export interface OrderStatus {
  id: number
  key: string
  label: string
  color: string
  is_cancellation: boolean
  is_completion: boolean
  sort_order: number
  notify_customer: boolean
  notification_message: string | null
}

export interface PaymentTransaction {
  id: number
  driver: string
  gateway_ref: string | null
  amount: number
  status: 'pending' | 'paid' | 'failed' | 'cancelled'
  receipt_path: string | null
  card_last_four: string | null
  paid_at: string | null
  created_at: string
}
