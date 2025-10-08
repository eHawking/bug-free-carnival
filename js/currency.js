(function(){
  const W = window;
  const API = '/admin/api/settings.php';
  let pending = null;

  function parseFirstNumber(text){
    if (!text) return null;
    const m = String(text).replace(/[,\s]/g,'').match(/([0-9]+(?:\.[0-9]+)?)/);
    return m ? parseFloat(m[1]) : null;
  }
  function fmt(n, symbol){
    const num = Number(n||0);
    // Prefer Intl without specifying currency code; we prefix symbol
    try {
      const s = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
      return symbol + s;
    } catch(e){
      return symbol + num.toFixed(2);
    }
  }

  async function load(){
    if (W.__currency) return W.__currency;
    if (pending) return pending;
    pending = fetch(API, { credentials: 'same-origin' })
      .then(r => r.ok ? r.json() : null)
      .then(j => {
        const c = (j && j.ok && j.currency) ? j.currency : { code: 'USD', symbol: '$', rate: 1 };
        if (!c || !c.code) c.code = 'USD';
        if (!c.symbol) c.symbol = '$';
        if (!c.rate || c.rate <= 0) c.rate = 1;
        W.__currency = c;
        return c;
      })
      .catch(() => {
        const c = { code: 'USD', symbol: '$', rate: 1 };
        W.__currency = c; return c;
      });
    return pending;
  }

  function applyPrices(){
    if (W.__pricingDbManaged) return; // Pricing managed by DB; do not touch
    const c = W.__currency; if (!c) return;
    const sels = ['.price-ttl', '.newPrice', '.oldPrice'];
    sels.forEach(sel => {
      document.querySelectorAll(sel).forEach(el => {
        // Skip if already converted for this code
        if (el.getAttribute('data-cur') === c.code) return;
        let base = el.getAttribute('data-usd');
        if (!base){
          const num = parseFirstNumber(el.textContent);
          if (num == null) return;
          base = String(num);
          el.setAttribute('data-usd', base);
        }
        const usd = parseFloat(base);
        const converted = usd * (c.rate || 1);
        el.textContent = fmt(converted, c.symbol);
        el.setAttribute('data-cur', c.code);
      });
    });
  }

  W.__loadCurrency = load;
  W.__applyCurrencyPrices = applyPrices;

  document.addEventListener('DOMContentLoaded', async function(){
    await load();
    if (W.__pricingDbManaged) return;
    applyPrices();
  });
})();
