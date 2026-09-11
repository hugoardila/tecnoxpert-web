(() => {
  'use strict';

  const menuButton = document.querySelector('.menu-toggle');
  const navigation = document.querySelector('.main-nav');
  if (menuButton && navigation) {
    menuButton.addEventListener('click', () => {
      const expanded = menuButton.getAttribute('aria-expanded') === 'true';
      menuButton.setAttribute('aria-expanded', String(!expanded));
      navigation.classList.toggle('is-open', !expanded);
    });
    navigation.addEventListener('click', (event) => {
      if (event.target.closest('a')) {
        menuButton.setAttribute('aria-expanded', 'false');
        navigation.classList.remove('is-open');
      }
    });
  }

  document.querySelectorAll('[data-accordion-button]').forEach((button) => {
    button.addEventListener('click', () => {
      const id = button.getAttribute('aria-controls');
      const panel = id ? document.getElementById(id) : null;
      if (!panel) return;
      const expanded = button.getAttribute('aria-expanded') === 'true';
      button.setAttribute('aria-expanded', String(!expanded));
      panel.hidden = expanded;
    });
  });

  const contactForm = document.querySelector('[data-contact-form]');
  if (contactForm) {
    const feedback = contactForm.querySelector('[data-form-feedback]');
    const submitButton = contactForm.querySelector('button[type="submit"]');
    contactForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!contactForm.reportValidity()) return;
      if (submitButton) submitButton.disabled = true;
      if (feedback) {
        feedback.className = 'form-feedback';
        feedback.textContent = 'Enviando solicitud...';
      }
      try {
        const response = await fetch(contactForm.action, {
          method: 'POST',
          body: new FormData(contactForm),
          headers: { Accept: 'application/json' },
        });
        const payload = await response.json();
        if (!response.ok || !payload.success) {
          throw new Error(payload.message || 'No fue posible enviar la solicitud.');
        }
        contactForm.reset();
        if (feedback) {
          feedback.className = 'form-feedback is-success';
          feedback.textContent = payload.message;
        }
      } catch (error) {
        if (feedback) {
          feedback.className = 'form-feedback is-error';
          feedback.textContent = error.message || 'No fue posible enviar la solicitud. Intenta nuevamente.';
        }
      } finally {
        if (submitButton) submitButton.disabled = false;
      }
    });
  }

  const paymentForm = document.querySelector('[data-payment-acceptance]');
  if (paymentForm) {
    const requiredChecks = [...paymentForm.querySelectorAll('input[type="checkbox"][required]')];
    const paymentButton = paymentForm.querySelector('button[type="submit"]');
    const updatePaymentButton = () => {
      if (paymentButton) {
        paymentButton.disabled = !requiredChecks.every((checkbox) => checkbox.checked);
      }
    };
    requiredChecks.forEach((checkbox) => checkbox.addEventListener('change', updatePaymentButton));
    updatePaymentButton();
  }

  const paymentSelector = document.querySelector('[data-payment-selector]');
  if (paymentSelector) {
    const country = paymentSelector.querySelector('[data-payment-country]');
    const countryHelp = paymentSelector.querySelector('[data-payment-country-help]');
    const methodLabels = [...paymentSelector.querySelectorAll('[data-payment-method]')];
    const submitButton = paymentSelector.querySelector('button[type="submit"]');
    const conversionPanel = paymentSelector.querySelector('[data-payment-conversion]');
    const localTotal = paymentSelector.querySelector('[data-payment-local-total]');
    const rateNote = paymentSelector.querySelector('[data-payment-rate-note]');
    const reference = paymentSelector.dataset.paymentReference || '';
    let conversionReady = false;
    let conversionRequest = null;
    let requestNumber = 0;

    const updatePaymentMethods = () => {
      const countryCode = country ? country.value : '';
      let availableCount = 0;
      let checkedAvailable = false;

      methodLabels.forEach((label) => {
        const radio = label.querySelector('input[type="radio"]');
        const countryReason = label.querySelector('[data-country-reason]');
        if (!radio) return;

        const baseEnabled = label.dataset.paymentBaseEnabled === '1';
        const countries = (label.dataset.paymentCountries || '').split(',').filter(Boolean);
        const countryAllowed = countryCode === '' || countries.includes(countryCode);
        const available = baseEnabled && countryAllowed;
        radio.disabled = !available;
        label.classList.toggle('is-disabled', !available);
        if (countryReason) {
          countryReason.hidden = countryAllowed;
        }

        if (!available && radio.checked) {
          radio.checked = false;
        }
        if (available) {
          availableCount += 1;
          checkedAvailable = checkedAvailable || radio.checked;
        }
      });

      if (!checkedAvailable && availableCount > 0) {
        const firstAvailable = methodLabels
          .map((label) => label.querySelector('input[type="radio"]'))
          .find((radio) => radio && !radio.disabled);
        if (firstAvailable) firstAvailable.checked = true;
        checkedAvailable = Boolean(firstAvailable);
      }

      if (countryHelp) {
        if (countryCode === '') {
          countryHelp.textContent = 'Selecciona el país para calcular el equivalente en tu moneda.';
        } else if (!conversionReady) {
          countryHelp.textContent = 'Calculando el equivalente para tu país...';
        } else {
          countryHelp.textContent = `${availableCount} método${availableCount === 1 ? '' : 's'} de pago disponible${availableCount === 1 ? '' : 's'}.`;
        }
      }
      if (submitButton) {
        submitButton.disabled = countryCode === '' || !checkedAvailable || !conversionReady;
      }
    };

    const clearProviderAmounts = () => {
      paymentSelector.querySelectorAll('[data-payment-provider-total]').forEach((element) => {
        element.textContent = '';
      });
    };

    const loadConversion = async () => {
      const countryCode = country ? country.value : '';
      conversionReady = false;
      requestNumber += 1;
      const currentRequest = requestNumber;
      if (conversionRequest) conversionRequest.abort();
      clearProviderAmounts();

      if (countryCode === '') {
        if (conversionPanel) conversionPanel.hidden = true;
        updatePaymentMethods();
        return;
      }

      conversionRequest = new AbortController();
      if (conversionPanel) conversionPanel.hidden = false;
      if (localTotal) localTotal.textContent = 'Calculando...';
      if (rateNote) rateNote.textContent = 'Consultando la tasa de referencia vigente.';
      updatePaymentMethods();

      try {
        const query = new URLSearchParams({ ref: reference, country: countryCode });
        const response = await fetch(`/api/tipo-cambio.php?${query.toString()}`, {
          headers: { Accept: 'application/json' },
          credentials: 'same-origin',
          signal: conversionRequest.signal,
        });
        const data = await response.json();
        if (currentRequest !== requestNumber) return;
        if (!response.ok || data.success !== true) {
          throw new Error(data.message || 'No fue posible calcular la conversión.');
        }

        if (localTotal) localTotal.textContent = data.local.formatted;
        if (rateNote) {
          const cacheNotice = data.local.stale ? 'Tasa temporal en caché. ' : '';
          rateNote.textContent = `${cacheNotice}Tasa de referencia del ${data.local.rate_date}. ${data.notice}`;
        }
        Object.entries(data.providers || {}).forEach(([provider, amount]) => {
          const element = paymentSelector.querySelector(
            `[data-payment-provider-total="${CSS.escape(provider)}"]`
          );
          if (element) element.textContent = `Importe aproximado a procesar: ${amount.formatted}`;
        });
        conversionReady = true;
        updatePaymentMethods();
      } catch (error) {
        if (error.name === 'AbortError' || currentRequest !== requestNumber) return;
        if (localTotal) localTotal.textContent = 'Conversión no disponible';
        if (rateNote) rateNote.textContent = error.message;
        updatePaymentMethods();
        if (countryHelp) {
          countryHelp.textContent = 'No fue posible calcular una tasa vigente. Intenta nuevamente.';
        }
      }
    };

    if (country) {
      country.addEventListener('change', loadConversion);
    }
    methodLabels.forEach((label) => {
      const radio = label.querySelector('input[type="radio"]');
      if (radio) radio.addEventListener('change', updatePaymentMethods);
    });
    updatePaymentMethods();
  }
})();
