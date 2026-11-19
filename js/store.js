(function () {
  const { sb, esc, money, num, fmtDate, fmtDateTime, fmtTime, label, badge, rpc, q, toast, route, go, setPage, state, fieldVal, numOrNull, copyText, trackUrl, waLink, C } = PMB;

  const cart = {
    get() { try { return JSON.parse(localStorage.getItem('pmb_cart') || '[]'); } catch (e) { return []; } },
    set(v) { try { localStorage.setItem('pmb_cart', JSON.stringify(v)); } catch (e) { /* storage unavailable */ } },
    clear() { cart.set([]); }
  };
  const saved = {
    get() { try { return JSON.parse(localStorage.getItem('pmb_requests') || '[]'); } catch (e) { return []; } },
    add(r) { try { const l = saved.get().filter((x) => x.token !== r.token); l.unshift(r); localStorage.setItem('pmb_requests', JSON.stringify(l.slice(0, 20))); } catch (e) { /* ignore */ } }
  };

  function publicNav() {
    const n = cart.get().length;
    return `<a href="#/">Menu</a><a href="#/mine">My requests</a><a href="#/request" class="cartlink">Your request${n ? ` <span class="count">${n}</span>` : ''}</a><a href="#/staff" class="quiet">Team login</a>`;
  }

  function priceText(item, tiers) {
    const u = item.unit ? ' per ' + esc(item.unit) : '';
    if (item.pricing_type === 'CUSTOM') return 'Quoted by PMB';
    if (item.pricing_type === 'FIXED') return money(item.base_price);
    if (item.pricing_type === 'TIERED') {
      const t = tiers.filter((x) => x.priceable_id === item.id).map((x) => Number(x.unit_price));
      return t.length ? 'From ' + money(Math.min(...t)) + u : 'Quoted by PMB';
    }
    return 'From ' + money(item.base_price) + u;
  }

  route(/^\/?$/, async function () {
    setPage('<section class="card"><p class="muted">Loading the menu...</p></section>', publicNav());
    let cached = null;
    try { cached = JSON.parse(sessionStorage.getItem('pmb_menu') || 'null'); } catch (e) { cached = null; }
    let cats, prods, svcs, tiers;
    if (cached && Date.now() - cached.t < 60000) {
      ({ cats, prods, svcs, tiers } = cached);
    } else {
      [cats, prods, svcs, tiers] = await Promise.all([
        q(sb.from('categories').select('*').eq('is_active', true).order('sort_order').order('name')),
        q(sb.from('products').select('*, product_options(*, product_option_values(*))').order('sort_order').order('name')),
        q(sb.from('services').select('*').order('sort_order').order('name')),
        q(sb.from('price_tiers').select('*'))
      ]);
      try { sessionStorage.setItem('pmb_menu', JSON.stringify({ t: Date.now(), cats, prods, svcs, tiers })); } catch (e) { /* storage unavailable, fine */ }
    }
    const items = [
      ...prods.map((p) => ({ ...p, _type: 'product' })),
      ...svcs.map((s) => ({ ...s, _type: 'service', product_options: [] }))
    ];
    const byCat = {};
    items.forEach((i) => { const k = i.category_id || 0; (byCat[k] = byCat[k] || []).push(i); });
    let active = 'all';
    function draw() {
      const shown = cats.filter((c) => (active === 'all' || String(c.id) === active) && byCat[c.id]);
      const other = active === 'all' && byCat[0] ? [{ id: 0, name: 'Services', description: '' }] : [];
      const sections = [...shown, ...other].map((c) => `
        <section class="catsec"><h2>${esc(c.name)}</h2>${c.description ? `<p class="muted">${esc(c.description)}</p>` : ''}
        <div class="grid">${(byCat[c.id] || []).map((i) => `
          <article class="item">
            ${i.image_url ? `<img src="${esc(i.image_url)}" alt="" loading="lazy">` : ''}
            <h3>${esc(i.name)}</h3>
            <p class="muted">${esc(i.short_description || '')}</p>
            <p class="price">${priceText(i, tiers)}</p>
            <button class="btn" data-act="open" data-type="${i._type}" data-id="${i.id}">Choose</button>
          </article>`).join('')}</div></section>`).join('');
      document.getElementById('menu').innerHTML = sections || '<p class="muted">Nothing on the menu yet. Please check back soon, or message us on WhatsApp.</p>';
    }
    setPage(`
      <section class="hero"><h1>Pure Meals Basket</h1>
        <p>Fresh catering, juices and celebration cakes from Mombasa. Build your request, we send you a clear quotation, and you confirm when you are happy.</p>
        <p><a class="btn ghost" href="${waLink(C.whatsapp, 'Hello Pure Meals Basket, I would like to ask about an order.')}" target="_blank" rel="noopener">Chat on WhatsApp</a></p></section>
      <div class="chips" id="chips"><button class="chip on" data-act="cat" data-cat="all">All</button>${cats.filter((c) => byCat[c.id]).map((c) => `<button class="chip" data-act="cat" data-cat="${c.id}">${esc(c.name)}</button>`).join('')}</div>
      <div id="menu"></div>`, publicNav());
    draw();
    state.actions.cat = (b) => { active = b.dataset.cat; document.querySelectorAll('#chips .chip').forEach((x) => x.classList.toggle('on', x === b)); draw(); };
    state.actions.open = (b) => {
      const it = items.find((i) => i._type === b.dataset.type && String(i.id) === b.dataset.id);
      openItem(it, tiers);
    };
  });

  function openItem(it, tiers) {
    const qtyBased = !['FIXED', 'CUSTOM'].includes(it.pricing_type);
    const opts = (it.product_options || []).slice().sort((a, b) => a.sort_order - b.sort_order);
    const ov = document.createElement('div');
    ov.className = 'overlay';
    ov.innerHTML = `<div class="modal" role="dialog" aria-modal="true" aria-label="${esc(it.name)}">
      <button class="x" aria-label="Close" data-close>&times;</button>
      <h2>${esc(it.name)}</h2><p class="muted">${esc(it.description || it.short_description || '')}</p>
      <form id="itemform">
        ${opts.map((o) => `<fieldset><legend>${esc(o.name)}${o.is_required ? ' *' : ''}</legend>
          ${(o.product_option_values || []).filter((v) => v.is_available).sort((a, b) => a.sort_order - b.sort_order).map((v) => `
          <label class="radio"><input type="radio" name="opt_${o.id}" value="${v.id}"> ${esc(v.name)} ${Number(v.price_modifier) ? `<span class="muted">+${money(v.price_modifier)}</span>` : ''}</label>`).join('')}
          ${o.is_required ? '' : `<label class="radio"><input type="radio" name="opt_${o.id}" value="" checked> None</label>`}
        </fieldset>`).join('')}
        ${qtyBased ? `<label>Quantity${it.unit ? ' (' + esc(it.unit) + ')' : ''}${it.minimum_quantity ? ` <span class="muted">minimum ${num(it.minimum_quantity)}</span>` : ''}
          <input type="number" name="qty" step="any" min="0" value="${it.minimum_quantity ? esc(it.minimum_quantity) : 1}" required></label>` : ''}
        <label>Notes for us (optional)<textarea name="notes" rows="2" maxlength="500" placeholder="Allergies, colours, message on the cake..."></textarea></label>
        <div class="preview" id="preview" aria-live="polite"></div>
        <div class="row"><button type="button" class="btn ghost" data-close>Cancel</button><button class="btn" type="submit">Add to my request</button></div>
      </form></div>`;
    document.body.appendChild(ov);
    const form = ov.querySelector('#itemform');
    const close = () => ov.remove();
    ov.addEventListener('click', (e) => { if (e.target === ov || e.target.closest('[data-close]')) close(); });
    const read = () => {
      const fd = new FormData(form);
      const ids = opts.map((o) => fd.get('opt_' + o.id)).filter((v) => v).map(Number);
      return { options: ids, quantity: qtyBased ? numOrNull(fd.get('qty')) : 1, notes: String(fd.get('notes') || '').trim() };
    };
    let last = null;
    async function refresh() {
      const r = read(); const box = ov.querySelector('#preview');
      try {
        const p = await rpc('price_item', { p_type: it._type, p_id: it.id, p_qty: r.quantity, p_options: r.options });
        last = p;
        box.className = 'preview';
        box.innerHTML = p.pricing_status === 'QUOTATION_REQUIRED' && p.subtotal == null
          ? '<strong>PMB will quote this item.</strong> Add it to your request and we will price it for you.'
          : `<strong>${money(p.subtotal)}</strong>${p.pricing_status === 'QUOTATION_REQUIRED' ? ' <span class="muted">(estimate, confirmed in your quotation)</span>' : ''}<br><span class="muted">${(p.breakdown || []).map(esc).join(' &middot; ')}</span>`;
      } catch (e) { last = null; box.className = 'preview warn'; box.textContent = e.message; }
    }
    form.addEventListener('change', refresh); form.addEventListener('input', refresh);
    refresh();
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      await refresh();
      if (!last) return;
      const r = read();
      const c = cart.get();
      c.push({ type: it._type, id: it.id, name: it.name, quantity: Number(last.quantity), unit: it.unit, options: r.options, option_names: last.option_names || [], notes: r.notes, subtotal: last.subtotal, status: last.pricing_status });
      cart.set(c);
      document.getElementById('nav').innerHTML = publicNav();
      toast(it.name + ' added to your request');
      close();
    });
  }

  route(/^\/request$/, async function () {
    const items = cart.get();
    const zones = await q(sb.from('delivery_zones').select('*').eq('is_active', true).order('sort_order').order('name'));
    const settings = await rpc('public_settings');
    if (!items.length) {
      setPage('<section class="card"><h1>Your request is empty</h1><p>Choose something from the menu first.</p><p><a class="btn" href="#/">See the menu</a></p></section>', publicNav());
      return;
    }
    const known = items.reduce((s, i) => s + (i.subtotal ? Number(i.subtotal) : 0), 0);
    const hasQuote = items.some((i) => i.status === 'QUOTATION_REQUIRED');
    const today = new Date(Date.now() + 3 * 3600 * 1000).toISOString().slice(0, 10);
    setPage(`
      <section class="card"><h1>Your request</h1>
        <ul class="lines">${items.map((i, n) => `<li><div><strong>${esc(i.name)}</strong> ${i.unit ? '&times; ' + num(i.quantity) + ' ' + esc(i.unit) : (i.quantity > 1 ? '&times; ' + num(i.quantity) : '')}
          <div class="muted">${(i.option_names || []).map(esc).join(', ')}${i.notes ? ' &middot; ' + esc(i.notes) : ''}</div></div>
          <div class="r">${i.subtotal != null ? money(i.subtotal) : 'To be quoted'}${i.status === 'QUOTATION_REQUIRED' && i.subtotal != null ? ' <span class="muted">(estimate)</span>' : ''}
          <button class="link" data-act="rm" data-n="${n}">Remove</button></div></li>`).join('')}</ul>
        <p class="total">Items so far: <strong>${money(known)}</strong>${hasQuote ? ' <span class="muted">plus items PMB will quote</span>' : ''}</p>
        <p class="muted">This is a request, not an order. We reply with a quotation valid for ${esc(settings.quote_valid_days)} days. You confirm it, then pay a ${esc(Number(settings.deposit_percent))}% deposit to secure your date.</p>
      </section>
      <section class="card"><h2>Your details</h2>
      <form data-submit="send" id="reqform" autocomplete="on">
        <div class="two"><label>Your name *<input name="name" required maxlength="120" autocomplete="name"></label>
        <label>Phone (WhatsApp if possible) *<input name="phone" type="tel" required maxlength="20" autocomplete="tel" placeholder="07XX XXX XXX"></label></div>
        <label>Email (optional)<input name="email" type="email" maxlength="200" autocomplete="email"></label>
        <fieldset><legend>How do you want to get your order?</legend>
          <label class="radio"><input type="radio" name="method" value="DELIVERY" checked> Delivery</label>
          <label class="radio"><input type="radio" name="method" value="CUSTOMER_COLLECTION"> I will collect</label>
          <label class="radio"><input type="radio" name="method" value="ON_SITE_SERVICE"> Catering on site at my venue</label></fieldset>
        <div id="deliv"><div class="two">
          <label>Delivery area *<select name="zone"><option value="">Choose an area</option>${zones.map((z) => `<option value="${z.id}">${esc(z.name)}</option>`).join('')}</select></label>
          <label>Delivery type<select name="dtype"><option value="standard">Standard</option><option value="door_to_door">Door to door</option><option value="beyond_stage">Beyond the stage</option></select></label></div>
          <label>Delivery address *<textarea name="address" rows="2" maxlength="300"></textarea></label>
          <p class="muted" id="fee"></p></div>
        <div class="two"><label>Date needed<input type="date" name="date" min="${today}"></label><label>Time<input type="time" name="time"></label></div>
        <label>Event or venue (optional)<input name="location" maxlength="300" placeholder="e.g. Church hall, Nyali"></label>
        <label>Anything else we should know?<textarea name="notes" rows="3" maxlength="2000"></textarea></label>
        <div class="hp" aria-hidden="true"><label>Leave this empty<input name="website" tabindex="-1" autocomplete="off"></label></div>
        <label class="radio"><input type="checkbox" name="consent" required> I agree that Pure Meals Basket may use these details to prepare my quotation and contact me about this request.</label>
        <div class="row"><a class="btn ghost" href="#/">Add more items</a><button class="btn" type="submit">Send my request</button></div>
      </form></section>`, publicNav());
    const form = document.getElementById('reqform');
    const syncMethod = () => { document.getElementById('deliv').style.display = form.method.value === 'DELIVERY' ? '' : 'none'; };
    form.addEventListener('change', async () => {
      syncMethod();
      const z = form.zone.value;
      const f = document.getElementById('fee');
      if (form.method.value === 'DELIVERY' && z) {
        try { const fee = await rpc('delivery_fee', { p_zone: Number(z), p_type: form.dtype.value, p_subtotal: known }); f.textContent = Number(fee) === 0 ? 'Delivery to this area is free for this order (confirmed in your quotation).' : 'Estimated delivery: ' + money(fee) + ' (confirmed in your quotation).'; } catch (e) { f.textContent = ''; }
      } else f.textContent = '';
    });
    syncMethod();
    state.actions.rm = (b) => { const c = cart.get(); c.splice(Number(b.dataset.n), 1); cart.set(c); go('/request'); };
    state.submits.send = async (f, fd) => {
      const method = fieldVal(fd, 'method');
      const res = await rpc('submit_request', {
        p_customer: { name: fieldVal(fd, 'name'), phone: fieldVal(fd, 'phone'), email: fieldVal(fd, 'email') },
        p_items: items.map((i) => ({ type: i.type, id: i.id, quantity: i.quantity, options: i.options, notes: i.notes })),
        p_details: { fulfillment_method: method, delivery_zone_id: fieldVal(fd, 'zone'), delivery_type: fieldVal(fd, 'dtype'), delivery_address: fieldVal(fd, 'address'),
          event_date: fieldVal(fd, 'date'), event_time: fieldVal(fd, 'time'), location: fieldVal(fd, 'location'), notes: fieldVal(fd, 'notes') },
        p_honeypot: fieldVal(fd, 'website')
      });
      saved.add({ ref: res.reference, token: res.token, at: new Date().toISOString() });
      cart.clear();
      try { sessionStorage.setItem('pmb_new', res.token); } catch (e) { /* ignore */ }
      go('/r/' + res.token);
    };
  });

  route(/^\/mine$/, async function () {
    const l = saved.get();
    setPage(`<section class="card"><h1>My requests</h1>${l.length ? `<ul class="lines">${l.map((r) => `<li><div><strong>${esc(r.ref)}</strong><div class="muted">${fmtDate(r.at)}</div></div><div class="r"><a class="btn small" href="#/r/${esc(r.token)}">Open</a></div></li>`).join('')}</ul>
      <p class="muted">These links are saved in this browser only. If you use another phone, open the link we sent you.</p>` : '<p>No requests saved on this device yet.</p><p><a class="btn" href="#/">See the menu</a></p>'}</section>`, publicNav());
  });

  route(/^\/r\/([0-9a-f-]{36})$/i, async function ([token]) {
    const r = await rpc('public_request', { p_token: token });
    if (!r) { setPage('<section class="card"><h1>We could not find that request</h1><p>Please check the link we sent you, or message us on WhatsApp.</p></section>', publicNav()); return; }
    const fresh = (() => { try { const v = sessionStorage.getItem('pmb_new') === token; if (v) sessionStorage.removeItem('pmb_new'); return v; } catch (e) { return false; } })();
    const quo = r.quotation, ord = r.order;
    const wa = waLink(C.whatsapp, 'Hello Pure Meals Basket, about ' + r.reference);
    let body = '';
    if (fresh) body += `<section class="card good"><h2>Thank you, ${esc(r.customer_name.split(' ')[0])}!</h2><p>We received your request <strong>${esc(r.reference)}</strong>. We will review it and send you a quotation on this page. <strong>Please save this page</strong>; it is the link to your request.</p>
      <p><button class="btn small" data-act="copy">Copy my link</button> <a class="btn small ghost" href="${wa}" target="_blank" rel="noopener">Message us on WhatsApp</a></p></section>`;
    body += `<section class="card"><div class="head"><h1>${esc(r.reference)}</h1>${badge(r.status)}</div>
      ${r.staff_message ? `<p class="note"><strong>Message from PMB:</strong> ${esc(r.staff_message)}</p>` : ''}
      <ul class="lines">${r.items.map((i) => `<li><div><strong>${esc(i.name)}</strong> &times; ${num(i.quantity)}${i.unit ? ' ' + esc(i.unit) : ''}${i.notes ? `<div class="muted">${esc(i.notes)}</div>` : ''}</div><div class="r">${i.subtotal != null ? money(i.subtotal) : 'To be quoted'}</div></li>`).join('')}</ul>
      <p class="muted">${label(r.fulfillment_method)}${r.delivery_address ? ' &middot; ' + esc(r.delivery_address) : ''}${r.event_date ? ' &middot; ' + fmtDate(r.event_date) + (r.event_time ? ' ' + fmtTime(r.event_time) : '') : ''}${r.location ? ' &middot; ' + esc(r.location) : ''}</p></section>`;
    if (quo && !ord) {
      body += `<section class="card"><div class="head"><h2>Quotation ${esc(quo.reference)}</h2>${badge(quo.status)}</div>
        ${quo.customer_message ? `<p class="note">${esc(quo.customer_message)}</p>` : ''}
        <ul class="lines">${quo.items.map((i) => `<li><div><strong>${esc(i.name)}</strong> &times; ${num(i.quantity)}${i.description ? `<div class="muted">${esc(i.description)}</div>` : ''}</div><div class="r">${money(i.subtotal)}</div></li>`).join('')}</ul>
        <dl class="sums"><dt>Items</dt><dd>${money(quo.subtotal)}</dd>${Number(quo.discount) ? `<dt>Discount</dt><dd>-${money(quo.discount)}</dd>` : ''}${Number(quo.delivery_fee) ? `<dt>Delivery</dt><dd>${money(quo.delivery_fee)}</dd>` : ''}<dt class="big">Total</dt><dd class="big">${money(quo.total)}</dd></dl>
        ${quo.status === 'SENT' ? `<p class="muted">Valid until ${fmtDate(quo.valid_until)}. After you accept, pay a ${esc(Number(r.business.deposit_percent))}% deposit (${money(Math.round(Number(quo.total) * Number(r.business.deposit_percent)) / 100)}) to confirm your order.</p>
        <div class="row"><button class="btn ghost" data-act="decline">I need changes</button><button class="btn" data-act="accept">Accept quotation</button></div>`
        : quo.status === 'EXPIRED' ? '<p class="muted">This quotation has expired. Message us and we will refresh it.</p>' : quo.status === 'DECLINED' ? '<p class="muted">You asked for changes. We will send an updated quotation.</p>' : ''}</section>`;
    }
    if (ord) {
      const steps = ['CONFIRMED', 'PREPARING', 'READY', ord.status === 'OUT_FOR_DELIVERY' || r.fulfillment_method === 'DELIVERY' ? 'OUT_FOR_DELIVERY' : null, r.fulfillment_method === 'DELIVERY' ? 'DELIVERED' : null, 'COMPLETED'].filter(Boolean);
      const idx = steps.indexOf(ord.status);
      body += `<section class="card"><div class="head"><h2>Order ${esc(ord.reference)}</h2>${badge(ord.status)}</div>
        ${ord.status === 'CANCELLED' ? '' : `<ol class="steps">${steps.map((s, n) => `<li class="${idx >= n ? 'done' : ''}">${esc(label(s))}</li>`).join('')}</ol>`}
        <dl class="sums"><dt>Total</dt><dd>${money(ord.total)}</dd><dt>Deposit required</dt><dd>${money(ord.payment_required)}</dd><dt>Paid so far</dt><dd>${money(ord.amount_paid)}</dd><dt class="big">Balance</dt><dd class="big">${money(ord.balance_due)}</dd></dl>
        ${ord.status === 'PENDING_PAYMENT' ? `<div class="note"><h3>How to pay your deposit</h3><p class="pre">${esc(ord.payment_instructions)}</p><p>Use this reference when you pay: <strong>${esc(ord.reference)}</strong></p><p class="muted">We confirm your payment by hand, so it can take a little while to show here.</p></div>` : ''}
        ${ord.payments.length ? `<h3>Payments</h3><ul class="lines">${ord.payments.map((p) => `<li><div>${esc(p.reference)} &middot; ${esc(label(p.method))}<div class="muted">${fmtDate(p.paid_at)}</div></div><div class="r">${money(p.amount)} ${badge(p.status)}</div></li>`).join('')}</ul>` : ''}</section>`;
    }
    body += `<p class="center"><button class="btn small ghost" data-act="reload">Refresh</button> <a class="btn small ghost" href="${wa}" target="_blank" rel="noopener">Message us on WhatsApp</a></p>`;
    setPage(body, publicNav());
    state.actions.copy = () => copyText(trackUrl(token));
    state.actions.reload = () => PMB.render();
    state.actions.accept = async () => {
      if (!PMB.confirmBox('Accept this quotation? We will then create your order and you can pay the deposit.')) return;
      await rpc('public_decide_quote', { p_token: token, p_decision: 'accept', p_message: null });
      toast('Quotation accepted'); PMB.render();
    };
    state.actions.decline = async () => {
      const msg = window.prompt('What would you like changed? (optional)', '');
      if (msg === null) return;
      await rpc('public_decide_quote', { p_token: token, p_decision: 'decline', p_message: msg });
      toast('We have your request for changes'); PMB.render();
    };
  });
})();
