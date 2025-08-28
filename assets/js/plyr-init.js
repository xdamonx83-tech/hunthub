// Standard-Plyr ohne Portrait-/Fullscreen-Hacks
(function(){
  function init(el){
    if (el.dataset.plyrReady === '1') return;
    el.dataset.plyrReady = '1';

    new Plyr(el, {
      // Standard: nimm natives Fullscreen-Verhalten
      fullscreen: { enabled: true, fallback: true, iosNative: true },
      // kein ratio-Override
      controls: [
        'play','progress','current-time','mute','volume','settings','pip','airplay','fullscreen'
      ]
    });
  }

  function scan(root=document){
    root.querySelectorAll('video:not([data-plyrReady])').forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scan);
  } else {
    scan();
  }

  // Für dynamische Inhalte
  new MutationObserver(m => {
    m.forEach(r => r.addedNodes.forEach(n => {
      if (n.nodeType !== 1) return;
      if (n.tagName === 'VIDEO') init(n); else scan(n);
    }));
  }).observe(document.documentElement, { childList:true, subtree:true });
})();
