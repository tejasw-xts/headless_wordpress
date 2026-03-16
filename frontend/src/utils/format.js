export function formatCurrency(value) {
  if (!value) {
    return 'Price on request';
  }

  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
  }).format(Number(value));
}
