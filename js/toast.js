(function(){
  if (window.toast) return;
  const style = document.createElement('style');
  style.textContent = `
  .toast-wrap{ position: fixed; right: 16px; bottom: 16px; z-index: 9999; display:flex; flex-direction:column; gap:10px; }
  .toast{ min-width: 240px; max-width: 360px; color: var(--text); background: #0f1116; border:1px solid var(--border); border-radius:12px; padding:10px 12px; box-shadow:0 10px 30px rgba(0,0,0,.25); font: 14px/1.4 system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial; opacity:0; transform: translateY(8px); transition: all .2s ease; }
  [data-theme="light"] .toast{ background:#ffffff; color:#111; }
  .toast.ok{ border-color:#16a34a; }
  .toast.err{ border-color:#ef4444; }
  .toast.show{ opacity:1; transform: translateY(0); }
  `;
  document.head.appendChild(style);

  let wrap = null;
  function ensureWrap(){
    if (!wrap){
      wrap = document.createElement('div');
      wrap.className = 'toast-wrap';
      document.body.appendChild(wrap);
    }
    return wrap;
  }

  function toast(msg, type){
    try{
      const w = ensureWrap();
      const t = document.createElement('div');
      t.className = 'toast ' + (type||'');
      t.textContent = String(msg||'');
      w.appendChild(t);
      requestAnimationFrame(()=> t.classList.add('show'));
      setTimeout(()=>{
        t.classList.remove('show');
        setTimeout(()=> t.remove(), 200);
      }, 4000);
    }catch(e){ /* ignore */ }
  }

  window.toast = toast;
})();
