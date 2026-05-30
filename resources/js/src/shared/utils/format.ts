export const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('fa-IR').format(price)
}

export const formatDate = (date: string | null | undefined): string => {
  if (!date) return '—'
  return new Intl.DateTimeFormat('fa-IR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(date))
}

export const formatDateTime = (date: string | null | undefined): string => {
  if (!date) return '—'
  return new Intl.DateTimeFormat('fa-IR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(date))
}
