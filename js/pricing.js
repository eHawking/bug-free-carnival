(function(){
  const API = {
    settings: '/admin/api/settings.php',
    pricing: '/admin/api/pricing.php'
  };
  const SKUS = { EPK06: 6, EPK03: 3, EPK02: 2 };

  function $(sel, root){ return (root||document).querySelector(sel); }
  function $all(sel, root){ return Array.from((root||document).querySelectorAll(sel)); }

  function format(n, symbol){
    const num = Number(n||0);
    try{
      const s = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
      return symbol + s;
    }catch(e){ return symbol + num.toFixed(2); }
  }

  async function loadCurrency(){
    if (window.__loadCurrency) { await window.__loadCurrency(); }
    const c = window.__currency || { code:'USD', symbol:'$', rate:1 };
    if (!c.rate || c.rate <= 0) c.rate = 1;
    return c;
  }

  async function loadPricing(){
    try{
      const r = await fetch(API.pricing, { credentials: 'same-origin' });
      if (!r.ok) throw new Error('HTTP '+r.status);
      const j = await r.json();
      if (!j || !j.ok || !j.pricing) throw new Error('bad payload');
      return j.pricing; // { EPK06: {total_usd, old_total_usd, bottles}, ... }
    }catch(e){ return null; }
  }

  function updateCardForSku(sku, data, cur){
    // Update every card area that contains this SKU button
    $all('a.addToCart[data-sku="'+sku+'"]').forEach(a => {
      const area = a.closest('.bottleArea') || document;
      const totalUSD = Number(data.total_usd||0);
      const oldUSD = Number(data.old_total_usd||0);
      const bottles = Number(data.bottles||SKUS[sku]||1);

      // Update Add to Cart href + data-price (base USD)
      const newHref = 'Checkout/cod.html?sku='+encodeURIComponent(sku)+'&price='+encodeURIComponent(String(totalUSD));
      a.setAttribute('href', newHref);
      a.setAttribute('data-price', String(totalUSD));

      // Compute converted values
      const total = totalUSD * (cur.rate||1);
      const old = oldUSD * (cur.rate||1);
      const perBottle = (totalUSD / (bottles||1)) * (cur.rate||1);

      // New total
      $all('.newPrice', area).forEach(el => {
        el.textContent = format(total, cur.symbol);
        el.setAttribute('data-usd', String(totalUSD));
        el.setAttribute('data-cur', cur.code);
      });
      // Old total
      $all('.oldPrice', area).forEach(el => {
        if (oldUSD>0){
          el.textContent = '\u00A0'+format(old, cur.symbol)+'\u00A0';
          el.setAttribute('data-usd', String(oldUSD));
          el.setAttribute('data-cur', cur.code);
        }
      });
      // Per-bottle headline price
      $all('.price-ttl', area).forEach(el => {
        el.textContent = format(perBottle, cur.symbol).replace(/^\s*/, '');
        el.setAttribute('data-usd', String(totalUSD / (bottles||1)));
        el.setAttribute('data-cur', cur.code);
      });
      // Savings text (YOU SAVE ...)
      const saveUSD = (oldUSD>0 && oldUSD>totalUSD) ? (oldUSD - totalUSD) : 0;
      const save = saveUSD * (cur.rate||1);
      $all('.lto.text-primary strong, .lto strong', area).forEach(el => {
        const txt = el.textContent || '';
        if (txt.toUpperCase().includes('YOU SAVE')){
          el.textContent = 'YOU SAVE ' + format(save, cur.symbol).replace(/\.00$/, '') + '!';
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', async function(){
    const cur = await loadCurrency();
    const pricing = await loadPricing();
    if (!pricing) return;
    Object.keys(SKUS).forEach(sku => {
      if (pricing[sku]) updateCardForSku(sku, pricing[sku], cur);
    });
  });
})();
