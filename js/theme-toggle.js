(function(){
  const KEY = 'honr_theme';
  const root = document.documentElement;

  function get(){
    try { return localStorage.getItem(KEY) || 'dark'; } catch(e){ return 'dark'; }
  }
  function set(theme){
    try { localStorage.setItem(KEY, theme); } catch(e){}
  }
  function apply(theme){
    if (theme === 'light') {
      root.setAttribute('data-theme','light');
    } else {
      root.removeAttribute('data-theme'); // default dark
    }
    // Update toggle label
    const btn = document.getElementById('themeToggle');
    if (btn) btn.textContent = (theme === 'light') ? 'Dark mode' : 'Light mode';
  }

  function toggle(){
    const cur = get();
    const next = (cur === 'light') ? 'dark' : 'light';
    set(next); apply(next);
  }

  document.addEventListener('DOMContentLoaded', function(){
    apply(get());
    const btn = document.getElementById('themeToggle');
    if (btn) btn.addEventListener('click', toggle);
  });
})();
