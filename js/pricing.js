(function(){
  const API = { plans: '/admin/api/plans.php' };
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

  async function loadPlans(){
    try{
      const r = await fetch(API.plans, { credentials: 'same-origin' });
      if (!r.ok) throw new Error('HTTP '+r.status);
      const j = await r.json();
      if (!j || !j.ok || !j.plans) throw new Error('bad payload');
      return j; // { currency:{code,symbol,rate}, plans:[...] }
    }catch(e){ return null; }
  }

  function updateCardForSku(sku, plan, cur){
    // Update every card area that contains this SKU button
    $all('a.addToCart[data-sku="'+sku+'"]').forEach(a => {
      const area = a.closest('.bottleArea') || document;
      const total = Number(plan.total_price||0);
      const old = Number(plan.old_total_price||0);
      const bottles = Number(plan.bottles||SKUS[sku]||1);

      // Update Add to Cart href + data-price (selected currency total)
      const newHref = 'Checkout/cod.html?sku='+encodeURIComponent(sku)+'&price='+encodeURIComponent(String(total));
      a.setAttribute('href', newHref);
      a.setAttribute('data-price', String(total));

      // Values already in selected currency
      const perBottle = (total / (bottles||1));

      // New total
      $all('.newPrice', area).forEach(el => {
        el.textContent = format(total, cur.symbol);
        el.setAttribute('data-cur', cur.code);
      });
      // Old total
      $all('.oldPrice', area).forEach(el => {
        if (old>0){
          el.textContent = '\u00A0'+format(old, cur.symbol)+'\u00A0';
          el.setAttribute('data-cur', cur.code);
        }
      });
      // Per-bottle headline price
      $all('.price-ttl', area).forEach(el => {
        el.textContent = format(perBottle, cur.symbol).replace(/^\s*/, '');
        el.setAttribute('data-cur', cur.code);
      });
      // Savings text (YOU SAVE ...)
      const save = (old>0 && old>total) ? (old - total) : 0;
      $all('.lto.text-primary strong, .lto strong', area).forEach(el => {
        const txt = el.textContent || '';
        if (txt.toUpperCase().includes('YOU SAVE')){
          el.textContent = 'YOU SAVE ' + format(save, cur.symbol).replace(/\.00$/, '') + '!';
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', async function(){
    const data = await loadPlans();
    if (!data) return;
    const cur = data.currency || { code:'USD', symbol:'$', rate:1 };
    const plansBySku = {};
    (data.plans||[]).forEach(p => { plansBySku[p.sku] = p; });
    Object.keys(SKUS).forEach(sku => {
      if (plansBySku[sku]) updateCardForSku(sku, plansBySku[sku], cur);
    });
  });
})();
