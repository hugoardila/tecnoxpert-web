(() => {
  const quoteForm = document.querySelector('[data-admin-quote-form]');
  if (quoteForm) {
    const subtotal = quoteForm.querySelector('[data-quote-subtotal]');
    const tax = quoteForm.querySelector('[data-quote-tax]');
    const currency = quoteForm.querySelector('[data-quote-currency]');
    const total = quoteForm.querySelector('[data-quote-total]');

    const updateTotal = () => {
      const subtotalValue = Number.parseFloat(subtotal?.value || '0') || 0;
      const taxValue = Number.parseFloat(tax?.value || '0') || 0;
      const currencyValue = currency?.value || 'COP';
      const formatted = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: currencyValue,
        maximumFractionDigits: currencyValue === 'COP' ? 0 : 2,
      }).format(Math.max(0, subtotalValue + taxValue));
      if (total) total.textContent = `${formatted} ${currencyValue}`;
    };

    [subtotal, tax, currency].forEach((control) => {
      control?.addEventListener('input', updateTotal);
      control?.addEventListener('change', updateTotal);
    });
    updateTotal();
  }

  document.querySelectorAll('[data-copy-value]').forEach((button) => {
    button.addEventListener('click', async () => {
      const value = button.dataset.copyValue || '';
      if (!value) return;
      try {
        await navigator.clipboard.writeText(value);
        const original = button.textContent;
        button.textContent = 'Copiado';
        window.setTimeout(() => {
          button.textContent = original;
        }, 1600);
      } catch {
        window.prompt('Copia este enlace:', value);
      }
    });
  });
})();
