/* Progressive enhancements: all navigation and forms remain server-rendered. */
(() => {
  'use strict';

  function init() {
    const root = document.documentElement;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');

    if (sidebar && sidebarToggle) {
      const mobile = window.matchMedia('(max-width: 991.98px)');
      const backdrop = document.createElement('div');
      backdrop.className = 'sidebar-backdrop';
      backdrop.hidden = true;
      backdrop.setAttribute('aria-hidden', 'true');
      document.body.append(backdrop);
      let open = false;
      let previousFocus = null;

      function focusableElements() {
        return Array.from(sidebar.querySelectorAll(
          'a[href], button, input, select, textarea, [tabindex]'
        )).filter(element => !element.disabled && element.tabIndex >= 0 &&
          !element.closest('[inert]') && element.getClientRects().length > 0 &&
          window.getComputedStyle(element).visibility !== 'hidden');
      }

      function setOpen(next, restoreFocus = false) {
        open = next && mobile.matches;
        root.classList.toggle('sidebar-open', open);
        sidebarToggle.setAttribute('aria-expanded', String(open));
        backdrop.hidden = !open;
        if (!open && restoreFocus && sidebar.contains(document.activeElement)) {
          const target = previousFocus && previousFocus.isConnected ? previousFocus : sidebarToggle;
          target.focus();
        }
        if (mobile.matches) {
          sidebar.inert = !open;
          sidebar.setAttribute('aria-hidden', String(!open));
        } else {
          sidebar.inert = false;
          sidebar.removeAttribute('aria-hidden');
        }
        if (open) {
          const first = focusableElements()[0];
          if (first) {
            first.focus();
          } else {
            if (!sidebar.hasAttribute('tabindex')) sidebar.setAttribute('tabindex', '-1');
            sidebar.focus();
          }
        }
      }

      sidebarToggle.addEventListener('click', () => {
        if (!open) previousFocus = document.activeElement;
        setOpen(!open, true);
      });
      document.addEventListener('click', event => {
        if (open && !sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
          setOpen(false, true);
        }
      });
      sidebar.addEventListener('click', event => {
        if (open && event.target.closest('a[href]')) setOpen(false, true);
      });
      document.addEventListener('keydown', event => {
        if (!open) return;
        if (event.key === 'Escape') {
          event.preventDefault();
          setOpen(false, true);
        } else if (event.key === 'Tab') {
          const items = focusableElements();
          const first = items[0];
          const last = items[items.length - 1];
          if (!first) {
            event.preventDefault();
            sidebar.focus();
          } else if (!sidebar.contains(document.activeElement)) {
            event.preventDefault();
            (event.shiftKey ? last : first).focus();
          } else if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
          } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
          }
        }
      });
      function syncSidebar() {
        setOpen(false, mobile.matches);
      }
      if (mobile.addEventListener) mobile.addEventListener('change', syncSidebar);
      else mobile.addListener(syncSidebar);
      root.classList.add('js');
      syncSidebar();
    }

    document.addEventListener('submit', event => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
      const message = form.getAttribute('data-confirm')?.trim() ||
        'Möchten Sie diese Aktion wirklich ausführen?';
      if (!window.confirm(message)) event.preventDefault();
    });

    const searchInputs = Array.from(document.querySelectorAll('[data-search]'));
    const searchRows = Array.from(document.querySelectorAll('[data-search-row]'));
    const searchableRows = searchRows.filter(row => !row.hidden);
    const normalize = value => value.normalize('NFKD')
      .replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('de-DE').replace(/ß/g, 'ss');
    function filterRows() {
      const terms = searchInputs.flatMap(input => normalize(input.value || '').trim().split(/\s+/))
        .filter(Boolean);
      searchableRows.forEach(row => {
        const text = normalize(row.textContent || '');
        row.hidden = !terms.every(term => text.includes(term));
      });
    }
    searchInputs.forEach(input => input.addEventListener('input', filterRows));
    if (searchInputs.length) filterRows();

    const progressInputs = Array.from(document.querySelectorAll('[data-progress-input]'));
    const progressOutputs = Array.from(document.querySelectorAll('[data-progress-output]'));
    progressInputs.forEach(input => {
      const explicitTarget = input.getAttribute('data-progress-input');
      const scope = input.closest('form') || document;
      const targets = explicitTarget ? progressOutputs.filter(output =>
        output.id === explicitTarget || output.getAttribute('data-progress-output') === explicitTarget
      ) : progressOutputs.filter(output =>
        (input.id && output.getAttribute('for')?.split(/\s+/).includes(input.id)) ||
        (output.closest('form') || document) === scope
      );
      function updateProgress() {
        const value = Number(input.value);
        if (!Number.isFinite(value)) return;
        const formatted = new Intl.NumberFormat('de-DE', { maximumFractionDigits: 2 }).format(value);
        targets.forEach(output => { output.textContent = `${formatted} %`; });
      }
      input.addEventListener('input', updateProgress);
      input.addEventListener('change', updateProgress);
      updateProgress();
    });

    document.querySelectorAll('[data-password-toggle]').forEach(button => {
      const input = document.getElementById(button.getAttribute('data-password-toggle'));
      if (!(input instanceof HTMLInputElement) || input.type !== 'password') return;
      if (button instanceof HTMLButtonElement) button.type = 'button';
      button.setAttribute('aria-controls', input.id);
      button.setAttribute('aria-pressed', 'false');
      button.setAttribute('aria-label', 'Passwort anzeigen');
      button.addEventListener('click', event => {
        event.preventDefault();
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Passwort verbergen' : 'Passwort anzeigen');
        button.title = visible ? 'Passwort verbergen' : 'Passwort anzeigen';
      });
    });

    document.querySelectorAll('[data-dismiss-alert]').forEach(button => {
      if (button instanceof HTMLButtonElement) button.type = 'button';
      if (!button.hasAttribute('aria-label')) button.setAttribute('aria-label', 'Hinweis schließen');
      button.addEventListener('click', event => {
        event.preventDefault();
        const alert = button.closest('.alert');
        if (!alert) return;
        if (alert.contains(document.activeElement)) {
          const next = Array.from(document.querySelectorAll(
            'a[href], button, input, select, textarea, [tabindex]'
          )).find(element => !alert.contains(element) && !element.disabled &&
            element.tabIndex >= 0 && !element.closest('[inert]') &&
            element.getClientRects().length > 0 &&
            Boolean(alert.compareDocumentPosition(element) & Node.DOCUMENT_POSITION_FOLLOWING));
          if (next) next.focus();
        }
        alert.remove();
      });
    });

    const revealElements = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && !reducedMotion.matches) {
      const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          entry.target.classList.remove('reveal-pending');
          entry.target.classList.add('reveal-visible');
          observer.unobserve(entry.target);
        });
      }, { threshold: 0, rootMargin: '0px 0px 20px 0px' });
      revealElements.forEach(element => {
        element.classList.add('reveal-pending');
        observer.observe(element);
      });
      // Keep keyboard navigation visible even before an observation is delivered.
      document.addEventListener('focusin', event => {
        const element = event.target.closest('.reveal');
        if (element) {
          element.classList.remove('reveal-pending');
          observer.unobserve(element);
        }
      });
    }

    function animateCount(element) {
      const raw = element.getAttribute('data-count');
      if (!raw || !raw.trim()) return;
      const value = Number(raw);
      if (!Number.isFinite(value)) return;
      const currency = element.getAttribute('data-format') === 'currency';
      const formatter = new Intl.NumberFormat('de-DE', currency
        ? { style: 'currency', currency: 'EUR' }
        : { maximumFractionDigits: 2 });
      const finalText = formatter.format(value);
      if (reducedMotion.matches || typeof window.requestAnimationFrame !== 'function') {
        element.textContent = finalText;
        return;
      }
      const visual = document.createElement('span');
      visual.setAttribute('aria-hidden', 'true');
      const accessible = document.createElement('span');
      accessible.className = 'count-accessible';
      accessible.textContent = finalText;
      element.replaceChildren(visual, accessible);
      let start;
      function frame(timestamp) {
        if (!element.isConnected) return;
        if (start === undefined) start = timestamp;
        const progress = reducedMotion.matches ? 1 : Math.min((timestamp - start) / 700, 1);
        const current = value * (1 - Math.pow(1 - progress, 3));
        visual.textContent = formatter.format(currency ? current : Math.trunc(current));
        if (progress < 1) window.requestAnimationFrame(frame);
        else element.replaceChildren(document.createTextNode(finalText));
      }
      window.requestAnimationFrame(frame);
    }
    const counts = document.querySelectorAll('[data-count]');
    if ('IntersectionObserver' in window && !reducedMotion.matches) {
      const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          observer.unobserve(entry.target);
          animateCount(entry.target);
        });
      }, { threshold: .15 });
      counts.forEach(element => observer.observe(element));
    } else {
      counts.forEach(animateCount);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
