(function () {
  const C = window.PMB_CONFIG;
  const sb = supabase.createClient(C.url, C.key, {
    db: { schema: 'pmb' },
    auth: { persistSession: true, autoRefreshToken: true, detectSessionInUrl: true }
  });

  const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const money = (n) => (n == null || n === '' ? '-' : 'KES ' + Number(n).toLocaleString('en-KE', { minimumFractionDigits: 0, maximumFractionDigits: 2 }));
  const num = (n) => Number(n).toLocaleString('en-KE', { maximumFractionDigits: 3 });
  const fmtDate = (d) => (d ? new Date(d).toLocaleDateString('en-KE', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'Africa/Nairobi' }) : '-');
  const fmtDateTime = (d) => (d ? new Date(d).toLocaleString('en-KE', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: 'Africa/Nairobi' }) : '-');
  const fmtTime = (t) => (t ? String(t).slice(0, 5) : '');
  const slugify = (s) => String(s).toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

  const LABELS = {
    SUBMITTED: 'New', UNDER_REVIEW: 'Under review', NEEDS_INFORMATION: 'Needs information', QUOTATION_REQUIRED: 'Quotation needed',
    READY_FOR_CHECKOUT: 'Ready', DECLINED: 'Declined', CANCELLED: 'Cancelled', DRAFT: 'Draft', SENT: 'Sent', ACCEPTED: 'Accepted',
    WITHDRAWN: 'Withdrawn', EXPIRED: 'Expired', PENDING_PAYMENT: 'Awaiting payment', CONFIRMED: 'Confirmed', PREPARING: 'Preparing',
    READY: 'Ready', OUT_FOR_DELIVERY: 'Out for delivery', DELIVERED: 'Delivered', COMPLETED: 'Completed', UNPAID: 'Unpaid',
    PARTIALLY_PAID: 'Part paid', PAID: 'Paid', PENDING: 'Pending', SUCCESS: 'Confirmed', FAILED: 'Rejected', REVERSED: 'Reversed',
    PROCESSING: 'Processing', MPESA: 'M-Pesa', CASH: 'Cash', DELIVERY: 'Delivery', CUSTOMER_COLLECTION: 'Customer collects', ON_SITE_SERVICE: 'On-site service',
    standard: 'Standard', door_to_door: 'Door to door', beyond_stage: 'Beyond the stage', FIXED: 'Fixed price', CUSTOM: 'Custom quote',
    PER_UNIT: 'Per unit', PER_WEIGHT: 'Per weight', PER_VOLUME: 'Per volume', PER_PERSON: 'Per person', TIERED: 'Tiered', ACTIVE: 'Active', ARCHIVED: 'Archived'
  };
  const label = (k) => LABELS[k] || k;
  const TONE = {
    SUBMITTED: 'gold', UNDER_REVIEW: 'blue', NEEDS_INFORMATION: 'orange', DRAFT: 'grey', SENT: 'blue', ACCEPTED: 'green', DECLINED: 'red',
    CANCELLED: 'red', WITHDRAWN: 'grey', EXPIRED: 'grey', PENDING_PAYMENT: 'orange', CONFIRMED: 'green', PREPARING: 'blue', READY: 'green',
    OUT_FOR_DELIVERY: 'blue', DELIVERED: 'green', COMPLETED: 'green', UNPAID: 'orange', PARTIALLY_PAID: 'gold', PAID: 'green', PENDING: 'orange',
    SUCCESS: 'green', FAILED: 'red', REVERSED: 'red', READY_FOR_CHECKOUT: 'green'
  };
  const badge = (k) => `<span class="badge ${TONE[k] || 'grey'}">${esc(label(k))}</span>`;

  async function rpc(name, args) {
    const { data, error } = await sb.rpc(name, args || {});
    if (error) throw new Error(cleanError(error));
    return data;
  }
  async function q(promise) {
    const { data, error } = await promise;
    if (error) throw new Error(cleanError(error));
    return data;
  }
  function cleanError(e) {
    const m = (e && e.message) || 'Something went wrong';
    if (/permission denied|not allowed/i.test(m)) return 'You do not have permission to do that.';
    if (/duplicate key/i.test(m)) return 'That already exists.';
    if (/violates foreign key/i.test(m)) return 'This record is in use, so it cannot be removed. Archive it instead.';
    if (/JWT|fetch|network/i.test(m)) return 'Could not reach the server. Check your connection and try again.';
    return m;
  }

  let toastTimer;
  function toast(msg, kind) {
    let t = document.getElementById('toast');
    if (!t) { t = document.createElement('div'); t.id = 'toast'; t.setAttribute('role', 'status'); document.body.appendChild(t); }
    t.className = 'toast ' + (kind || 'ok');
    t.textContent = msg;
    t.style.display = 'block';
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { t.style.display = 'none'; }, kind === 'err' ? 7000 : 3500);
  }

  // router
  const routes = [];
  const app = document.getElementById('app');
  const state = { actions: {}, submits: {}, changes: {} };
  function route(pattern, handler) { routes.push([pattern, handler]); }
  function go(path) { if (location.hash === '#' + path) render(); else location.hash = path; }
  async function render() {
    const path = location.hash.replace(/^#/, '') || '/';
    state.actions = {}; state.submits = {}; state.changes = {};
    for (const [re, h] of routes) {
      const m = path.match(re);
      if (m) {
        try { await h(m.slice(1)); } catch (e) { setPage(`<section class="card"><h1>Something went wrong</h1><p>${esc(e.message)}</p><p><a class="btn" href="#/">Back to start</a></p></section>`); }
        window.scrollTo(0, 0);
        return;
      }
    }
    setPage('<section class="card"><h1>Page not found</h1><p><a class="btn" href="#/">Back to start</a></p></section>');
  }
  function setPage(html, nav) {
    app.innerHTML = html;
    document.getElementById('nav').innerHTML = nav || '';
    document.title = 'Pure Meals Basket';
  }
  app.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-act]');
    if (!b) return;
    const fn = state.actions[b.dataset.act];
    if (!fn) return;
    e.preventDefault();
    if (b.disabled) return;
    const was = b.disabled; b.disabled = true;
    try { await fn(b, e); } catch (err) { toast(err.message, 'err'); } finally { b.disabled = was; }
  });
  app.addEventListener('submit', async (e) => {
    const f = e.target.closest('form[data-submit]');
    if (!f) return;
    e.preventDefault();
    const fn = state.submits[f.dataset.submit];
    if (!fn) return;
    const btn = f.querySelector('button[type=submit]');
    if (btn) btn.disabled = true;
    try { await fn(f, new FormData(f)); } catch (err) { toast(err.message, 'err'); } finally { if (btn) btn.disabled = false; }
  });
  app.addEventListener('change', (e) => {
    const el = e.target.closest('[data-change]');
    if (el && state.changes[el.dataset.change]) state.changes[el.dataset.change](el, e);
  });
  window.addEventListener('hashchange', render);

  function confirmBox(message) { return window.confirm(message); }
  function fieldVal(fd, k) { const v = fd.get(k); return v == null ? '' : String(v).trim(); }
  function numOrNull(v) { return v === '' || v == null ? null : Number(v); }
  function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) return navigator.clipboard.writeText(text).then(() => toast('Copied'));
    const ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); toast('Copied');
  }
  const trackUrl = (token) => location.origin + '/#/r/' + token;
  const waLink = (phone, text) => 'https://wa.me/' + String(phone).replace(/\D/g, '').replace(/^0/, '254') + '?text=' + encodeURIComponent(text);

  window.PMB = { sb, esc, money, num, fmtDate, fmtDateTime, fmtTime, slugify, label, badge, rpc, q, toast, route, go, render, setPage, state, app, confirmBox, fieldVal, numOrNull, copyText, trackUrl, waLink, C };
})();
