(() => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let leaving = false;
  let exitAnimation;

  // History restores must never retain a faded-out page.
  window.addEventListener('pageshow', () => {
    leaving = false;
    exitAnimation?.cancel();
  });

  document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
    const destination = new URL(link.href, location.href);
    if (destination.origin !== location.origin || !['http:', 'https:'].includes(destination.protocol)) return;
    if (destination.pathname === location.pathname && destination.search === location.search && destination.hash) return;
    if (reducedMotion.matches) return;

    if (destination.href === location.href) {
      event.preventDefault();
      link.animate?.([{ transform: 'scale(1)' }, { transform: 'scale(.96)' }, { transform: 'scale(1)' }], { duration: 240, easing: 'ease-out' });
      return;
    }

    // Native cross-document transitions keep normal navigation semantics.
    if ('CSSViewTransitionRule' in window) return;
    const main = document.querySelector('main');
    if (!main?.animate) return;
    event.preventDefault();
    if (leaving) return;
    leaving = true;
    exitAnimation = main.animate(
      [{ opacity: 1, transform: 'translateY(0)' }, { opacity: 0, transform: 'translateY(-6px)' }],
      { duration: 150, easing: 'cubic-bezier(.4,0,1,1)' }
    );
    // A cancelled animation must not swallow a navigation.
    exitAnimation.finished.catch(() => {}).then(() => location.assign(destination.href));
  });
})();
