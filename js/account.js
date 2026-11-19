(function () {
  const { sb, esc, money, fmtDate, label, badge, rpc, toast, route, go, setPage, state, fieldVal } = PMB;

  // Who is signed in on this device. Boot waits for this so the menu is right on first paint.
  PMB.user = null;
  const cameFromEmailLink = /access_token=|type=signup|type=magiclink/.test(location.hash);
  PMB.ready = sb.auth.getSession().then(({ data }) => {
    PMB.user = data.session ? data.session.user : null;
    if (cameFromEmailLink && PMB.user && !PMB.IS_TEAM) location.hash = '/account';
  }).catch(() => { /* signed out */ });
  sb.auth.onAuthStateChange((ev, session) => { PMB.user = session ? session.user : null; });

  // Tracking links remembered on this device prove the order is theirs, so they attach to the account after sign-in.
  const pend = {
    get() { try { return JSON.parse(localStorage.getItem('pmb_claim') || '[]'); } catch (e) { return []; } },
    set(v) { try { localStorage.setItem('pmb_claim', JSON.stringify(v.slice(0, 50))); } catch (e) { /* ignore */ } }
  };
  const uuid = /^[0-9a-f-]{36}$/i;
  async function claimAll(extra) {
    const t = new Set(pend.get());
    PMB.saved.get().forEach((r) => t.add(r.token));
    if (extra) t.add(extra);
    const list = [...t].filter((x) => uuid.test(x)).slice(0, 50);
    if (!list.length) return null;
    try { const r = await rpc('claim_customer_account', { p_tokens: list }); pend.set([]); return r; } catch (e) { return null; }
  }
  const parseQ = (qs) => new URLSearchParams(String(qs || '').replace(/^\?/, ''));
  const nav = () => PMB.publicNav();

  route(/^\/account\/signup(\?.*)?$/, async function ([qs]) {
    if (PMB.user) { go('/account'); return; }
    const p = parseQ(qs); const r = uuid.test(p.get('r') || '') ? p.get('r') : '';
    setPage(`<section class="card narrow"><h1>Create your account</h1>
      <p class="muted">See all your orders and receipts in one place, and order again in a tap. Use the email you gave us when you ordered.</p>
      <form data-submit="signup"><label>Email<input name="email" type="email" required autocomplete="username" value="${esc(p.get('e') || '')}"></label>
      <label>Choose a password (at least 8 characters)<input name="password" type="password" minlength="8" required autocomplete="new-password"></label>
      <button class="btn" type="submit">Create my account</button></form>
      <p class="muted">Already have an account? <a href="#/account/signin${r ? '?r=' + esc(r) : ''}">Sign in</a></p></section>`, nav());
    state.submits.signup = async (f, fd) => {
      const email = fieldVal(fd, 'email').toLowerCase();
      if (r) pend.set([...new Set([...pend.get(), r])]);
      const { data, error } = await sb.auth.signUp({ email, password: String(fd.get('password')), options: { emailRedirectTo: location.origin + '/' } });
      if (error) throw new Error(/not allowed|disabled/i.test(error.message) ? 'Account sign-up is not open yet. Please message us on WhatsApp and we will help.' : error.message);
      if (data.session) { PMB.user = data.session.user; await claimAll(r); toast('Welcome! Your account is ready.'); go('/account'); return; }
      setPage(`<section class="card narrow good"><h1>Check your email</h1><p>We sent a confirmation link to <strong>${esc(email)}</strong>. Open it on this phone or computer to finish. If you already have an account with us, <a href="#/account/signin">sign in here</a>.</p></section>`, nav());
    };
  });

  route(/^\/account\/signin(\?.*)?$/, async function ([qs]) {
    if (PMB.user) { go('/account'); return; }
    const p = parseQ(qs); const r = uuid.test(p.get('r') || '') ? p.get('r') : '';
    setPage(`<section class="card narrow"><h1>Sign in</h1>
      <form data-submit="signin"><label>Email<input name="email" type="email" required autocomplete="username"></label>
      <label>Password<input name="password" type="password" required autocomplete="current-password"></label>
      <button class="btn" type="submit">Sign in</button></form>
      <p><button class="link" data-act="forgot">Forgot your password?</button></p>
      <p class="muted">New here? Orders do not need an account. After your first order you can <a href="#/account/signup${r ? '?r=' + esc(r) : ''}">create one</a> to keep your history and receipts.</p></section>`, nav());
    state.submits.signin = async (f, fd) => {
      const { data, error } = await sb.auth.signInWithPassword({ email: fieldVal(fd, 'email'), password: String(fd.get('password')) });
      if (error) throw new Error(/confirm/i.test(error.message) ? 'Please confirm your email first. We sent you a link when you signed up.' : 'Email or password is not correct.');
      PMB.user = data.user; await claimAll(r); go('/account');
    };
    state.actions.forgot = async () => {
      const email = window.prompt('Enter your email and we will send a reset link.');
      if (!email) return;
      try { localStorage.setItem('pmb_reset_for', 'customer'); } catch (e) { /* ignore */ }
      const { error } = await sb.auth.resetPasswordForEmail(email.trim(), { redirectTo: location.origin + '/' });
      if (error) throw new Error(error.message);
      toast('If that email has an account, a reset link is on its way.');
    };
  });

  route(/^\/account\/reset$/, async function () {
    setPage('<section class="card narrow"><h1>Choose a new password</h1><form data-submit="reset"><label>New password (at least 8 characters)<input name="pw" type="password" minlength="8" required autocomplete="new-password"></label><button class="btn" type="submit">Save password</button></form></section>', nav());
    state.submits.reset = async (f, fd) => {
      const { error } = await sb.auth.updateUser({ password: String(fd.get('pw')) });
      if (error) throw new Error(error.message);
      try { localStorage.removeItem('pmb_reset_for'); } catch (e) { /* ignore */ }
      toast('Password updated'); go('/account');
    };
  });

  const statusOf = (o) => o.order_status || o.status;
  const orderCard = (o) => `<li class="acct-order"><div class="head"><strong>${esc(o.order_reference || o.reference)}</strong>${badge(statusOf(o))}</div>
    <div class="muted">${fmtDate(o.created_at)}${o.event_date ? ' &middot; for ' + fmtDate(o.event_date) : ''}</div>
    <div>${esc(o.summary || '')}</div>
    ${o.total != null ? `<div class="muted">Total ${money(o.total)} &middot; Paid ${money(o.amount_paid)} &middot; Balance ${money(o.balance_due)}</div>` : o.quoted_total != null ? `<div class="muted">Quotation ${money(o.quoted_total)}</div>` : ''}
    <div class="acts"><a class="btn small" href="#/r/${esc(o.token)}">Open</a> <button class="btn small ghost" data-act="reorder" data-id="${esc(o.id)}">Order again</button></div></li>`;

  route(/^\/account$/, async function () {
    const { data } = await sb.auth.getSession();
    if (!data.session) { go('/account/signin'); return; }
    PMB.user = data.session.user;
    await claimAll();
    const [acct, orders, receipts] = await Promise.all([rpc('my_account'), rpc('my_orders'), rpc('my_receipts')]);
    const who = (acct && acct.name ? acct.name.split(' ')[0] : '') || (acct && acct.email) || '';
    setPage(`<section class="card"><div class="head"><h1>My account</h1><button class="btn small ghost" data-act="logout">Sign out</button></div>
      <p class="muted">${who ? 'Signed in as ' + esc(acct && acct.email ? acct.email : who) : ''}</p></section>
      <section class="card"><h2>My orders</h2>${orders.length ? `<ul class="lines acct-orders">${orders.map(orderCard).join('')}</ul>`
        : `<p>No orders on this account yet.</p><p class="muted">If you have ordered before, open the tracking link we sent you while you are signed in, and that order will be added here.</p><p><a class="btn" href="#/">See the menu</a></p>`}</section>
      <section class="card"><h2>My receipts</h2>${receipts.length ? `<ul class="lines">${receipts.map((x) => `<li><div><strong>${esc(x.receipt_no)}</strong><div class="muted">${esc(x.order_reference)} &middot; ${fmtDate(x.paid_at)} &middot; ${esc(label(x.method))}</div></div><div class="r">${money(x.amount)}<div><a href="#/account/receipt/${esc(x.id)}">View receipt</a></div></div></li>`).join('')}</ul>`
        : '<p class="muted">A receipt appears here automatically as soon as we confirm a payment.</p>'}</section>`, nav());
    state.actions.logout = async () => { await sb.auth.signOut(); PMB.user = null; go('/'); };
    state.actions.reorder = async (b) => {
      const items = await rpc('my_reorder', { p_request: b.dataset.id });
      const c = PMB.cart.get(); let added = 0; const skipped = [];
      for (const it of items) {
        if (!it.available) { skipped.push(it.name); continue; }
        try {
          const p = await rpc('price_item', { p_type: it.type, p_id: it.id, p_qty: Number(it.quantity), p_options: it.options });
          c.push({ type: it.type, id: it.id, name: it.name, quantity: Number(p.quantity), unit: it.unit, options: p.options || it.options, option_names: p.option_names || [], notes: it.notes || '', subtotal: p.subtotal, status: p.pricing_status, collect: it.collect !== false });
          added++;
        } catch (e) { skipped.push(it.name); }
      }
      if (!added) throw new Error('Those items are not on the menu right now. Please browse the menu.');
      PMB.cart.set(c);
      toast(skipped.length ? 'Added to your basket. Not available now: ' + skipped.join(', ') : 'Added to your basket at today\'s prices. Check it and send.');
      go('/request');
    };
  });

  function showReceipt(r, back, backLabel) {
    if (!r) { setPage('<section class="card"><h1>We could not find that receipt</h1><p>A receipt is issued once a payment is confirmed.</p><p><a class="btn" href="' + back + '">Back</a></p></section>', nav()); return; }
    const b = r.business || {};
    setPage(`<section class="card receipt"><div class="head"><h1>Receipt</h1><span class="badge green">Payment confirmed</span></div>
      <p><strong>${esc(b.name || 'Pure Meals Basket')}</strong><br><span class="muted">${esc(b.phone || '')}${b.email ? ' &middot; ' + esc(b.email) : ''}</span></p>
      <dl class="sums"><dt>Receipt number</dt><dd>${esc(r.receipt_no)}</dd><dt>Date paid</dt><dd>${fmtDate(r.paid_at)}</dd><dt>Received from</dt><dd>${esc(r.customer_name)}</dd><dt>Order</dt><dd>${esc(r.order_reference)}</dd><dt>Paid by</dt><dd>${esc(label(r.method))}</dd>${r.provider_reference ? `<dt>Payment reference</dt><dd>${esc(r.provider_reference)}</dd>` : ''}<dt class="big">Amount received</dt><dd class="big">${money(r.amount)}</dd></dl>
      <dl class="sums"><dt>Order total</dt><dd>${money(r.order_total)}</dd><dt>Total paid so far</dt><dd>${money(r.amount_paid)}</dd><dt>Balance now</dt><dd>${money(r.balance_due)}</dd></dl>
      <p class="muted">Thank you for choosing Pure Meals Basket.</p>
      <p class="row"><button class="btn" data-act="print">Print or save as PDF</button> <a class="btn ghost" href="${back}">${esc(backLabel)}</a></p></section>`, nav());
    document.title = 'Receipt ' + r.receipt_no + ' | Pure Meals Basket';
    state.actions.print = () => window.print();
  }
  route(/^\/receipt\/([0-9a-f-]{36})\/([0-9a-f-]{36})$/i, async ([token, id]) => showReceipt(await rpc('public_receipt', { p_token: token, p_payment: id }), '#/r/' + token, 'Back to my order'));
  route(/^\/account\/receipt\/([0-9a-f-]{36})$/i, async ([id]) => {
    const { data } = await sb.auth.getSession();
    if (!data.session) { go('/account/signin'); return; }
    showReceipt(await rpc('my_receipt', { p_payment: id }), '#/account', 'Back to my account');
  });
})();
