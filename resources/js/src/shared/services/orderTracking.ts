import api from '@shared/utils/api'

export interface TrackingTimelineItem {
  key: string
  label: string
  date: string | null
  completed: boolean
  current: boolean
  color: string
}

export interface OrderTrackingResult {
  order_number: string
  status: string
  status_label: string
  status_color: string
  payment_status: string
  total: number
  items_count: number
  created_at: string
  paid_at: string | null
  shipped_at: string | null
  delivered_at: string | null
  timeline: TrackingTimelineItem[]
}

export const orderTrackingService = {
  async track(trackingCode: string): Promise<{ success: boolean; data?: OrderTrackingResult; message?: string }> {
    const response = await api.post('/orders/track', { tracking_code: trackingCode })
    return response.data
  },
}
