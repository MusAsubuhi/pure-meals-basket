(function () {
  const { sb, esc, money, num, fmtDate, fmtDateTime, fmtTime, slugify, label, badge, rpc, q, toast, route, go, setPage, state, confirmBox, fieldVal, numOrNull, copyText, trackUrl, waLink } = PMB;

  let me = null; // { id, email, role }
  async function loadMe() {
    const { data } = await sb.auth.getSession();
    if (!data.session) { me = null; return null; }
    const role = await rpc('my_role');
    me = role ? { id: data.session.user.id, email: data.session.user.email, role } : { id: data.session.user.id, email: data.session.user.email, role: null };
    return me;
  }
  const isMgr = () => me && (me.role === 'owner' || me.role === 'manager');
  const isOwner = () => me && me.role === 'owner';
  const isFinance = () => me && ['owner', 'manager', 'cashier'].includes(me.role);
  const isOffice = () => me && ['owner', 'manager', 'staff', 'cashier'].includes(me.role);

  // Which screens each role may open. The database enforces the same limits; this only decides what is shown.
  const ACCESS = {
    home: ['owner', 'manager', 'staff', 'cashier'], requests: ['owner', 'manager', 'staff'], orders: ['owner', 'manager', 'staff', 'cashier'],
    payments: ['owner', 'manager', 'staff', 'cashier'], catalogue: ['owner', 'manager'], zones: ['owner', 'manager'], settings: ['owner'], team: ['owner'],
    kitchen: ['owner', 'manager', 'staff', 'cook'], deliveries: ['owner', 'manager', 'staff', 'driver']
  };
  const roleHome = (r) => (r === 'cook' ? '/staff/kitchen' : r === 'driver' ? '/staff/deliveries' : '/staff');
  const ROLE_INFO = {
    owner: ['Super admin', 'Everything, including settings, the team and reversing payments.'],
    manager: ['Manager', 'Requests, quotations, orders, payments, catalogue and delivery prices, plus the kitchen and deliveries boards.'],
    staff: ['Front desk', 'Handles requests, quotations and orders, assigns drivers and records payments. A manager confirms the money.'],
    cashier: ['Cashier', 'Sees orders and payments only. Records, confirms and rejects payments.'],
    cook: ['Cook', 'Sees only the Kitchen board: what to prepare and for when. No prices, phone numbers or addresses.'],
    driver: ['Driver', 'Sees only deliveries: address, contact and balance to collect. Takes a delivery, then marks it out and delivered.']
  };

  function staffNav(active) {
    const l = (h, t, k) => (ACCESS[k].includes(me.role) ? `<a href="#/staff/${h}" class="${active === k ? 'on' : ''}">${t}</a>` : '');
    return `${l('', 'Today', 'home')}${l('requests', 'Requests', 'requests')}${l('orders', 'Orders', 'orders')}${l('payments', 'Payments', 'payments')}${l('kitchen', 'Kitchen', 'kitchen')}${l('deliveries', 'Deliveries', 'deliveries')}${l('catalogue', 'Catalogue', 'catalogue')}${l('zones', 'Delivery prices', 'zones')}${l('settings', 'Settings', 'settings')}${l('team', 'Team', 'team')}<a href="#/">Customer site</a><button class="link" data-act="logout">Sign out (${esc((ROLE_INFO[me.role] || [me.role])[0])})</button>`;
  }
  function wireLogout() { state.actions.logout = async () => { await sb.auth.signOut(); me = null; go('/staff'); }; }

  async function guard(active, fn) {
    await loadMe();
    if (!me) { loginPage(); return; }
    if (!me.role) {
      setPage(`<section class="card"><h1>No access yet</h1><p>You are signed in as ${esc(me.email)}, but this account is not part of the PMB team. Ask the owner to add you.</p><p><button class="btn" data-act="logout">Sign out</button></p></section>`, '');
      state.actions.logout = async () => { await sb.auth.signOut(); me = null; go('/staff'); };
      return;
    }
    wireLogout();
    if (!(ACCESS[active] || []).includes(me.role)) { go(roleHome(me.role)); return; }
    await fn(() => staffNav(active));
  }

  function loginPage(msg) {
    setPage(`<section class="card narrow"><h1>Team sign in</h1>${msg ? `<p class="note">${esc(msg)}</p>` : ''}
      <form data-submit="login"><label>Email<input name="email" type="email" required autocomplete="username"></label>
      <label>Password<input name="password" type="password" required autocomplete="current-password"></label>
      <button class="btn" type="submit">Sign in</button></form>
      <p><button class="link" data-act="forgot">Forgot your password?</button></p></section>`, '<a href="#/">Customer site</a>');
    state.submits.login = async (f, fd) => {
      const { error } = await sb.auth.signInWithPassword({ email: fieldVal(fd, 'email'), password: String(fd.get('password')) });
      if (error) throw new Error('Email or password is not correct.');
      go('/staff');
    };
    state.actions.forgot = async () => {
      const email = window.prompt('Enter your email and we will send a reset link.');
      if (!email) return;
      const { error } = await sb.auth.resetPasswordForEmail(email.trim(), { redirectTo: location.origin + '/#/staff/reset' });
      if (error) throw new Error(error.message);
      toast('If that email has an account, a reset link is on its way.');
    };
  }

  sb.auth.onAuthStateChange((ev) => { if (ev === 'PASSWORD_RECOVERY') location.hash = '/staff/reset'; });
  route(/^\/staff\/reset$/, async function () {
    setPage(`<section class="card narrow"><h1>Choose a new password</h1><form data-submit="reset"><label>New password (at least 10 characters)<input name="pw" type="password" minlength="10" required autocomplete="new-password"></label><button class="btn" type="submit">Save password</button></form></section>`, '');
    state.submits.reset = async (f, fd) => {
      const { error } = await sb.auth.updateUser({ password: String(fd.get('pw')) });
      if (error) throw new Error(error.message);
      toast('Password updated'); go('/staff');
    };
  });

  route(/^\/staff$/, () => guard('home', async (nav) => {
    const d = await rpc('staff_dashboard');
    const [reqs, pays] = await Promise.all([
      q(sb.from('requests').select('id,reference,status,submitted_at,event_date,customers(name)').in('status', ['SUBMITTED', 'UNDER_REVIEW', 'NEEDS_INFORMATION']).order('submitted_at', { ascending: false }).limit(8)),
      q(sb.from('payments').select('id,reference,amount,method,orders(id,reference,customer_name)').eq('status', 'PENDING').order('created_at').limit(8))
    ]);
    const tile = (n, t, href, tone) => `<a class="tile ${n > 0 && tone ? tone : ''}" href="#/staff/${href}"><strong>${n}</strong><span>${t}</span></a>`;
    setPage(`<h1>Today</h1>
      <div class="tiles">${tile(d.new_requests, 'New requests', 'requests?s=SUBMITTED', 'gold')}${tile(d.quotes_draft, 'Quotations to finish', 'requests?s=UNDER_REVIEW')}${tile(d.quotes_waiting, 'Quotations awaiting customers', 'requests?s=quoted')}${tile(d.quotes_expiring, 'Expiring within 48 hours', 'requests?s=quoted', 'orange')}
      ${tile(d.awaiting_payment, 'Orders awaiting deposit', 'orders?s=PENDING_PAYMENT', 'orange')}${tile(d.payments_to_confirm, 'Payments to confirm', 'payments', 'gold')}${tile(d.in_progress, 'Orders in progress', 'orders?s=active')}${tile(d.upcoming_7d, 'Due in the next 7 days', 'orders?s=active')}</div>
      <div class="two"><section class="card"><h2>Balance outstanding</h2><p class="bignum">${money(d.balance_outstanding)}</p><p class="muted">Collected this month: <strong>${money(d.collected_this_month)}</strong></p></section>
      <section class="card"><h2>Payments waiting for confirmation</h2>${pays.length ? `<ul class="lines">${pays.map((p) => `<li><div><a href="#/staff/order/${p.orders.id}">${esc(p.orders.reference)}</a> ${esc(p.orders.customer_name)}<div class="muted">${esc(p.reference)} &middot; ${esc(label(p.method))}</div></div><div class="r">${money(p.amount)}</div></li>`).join('')}</ul>` : '<p class="muted">Nothing waiting.</p>'}</section></div>
      <section class="card"><h2>Requests needing attention</h2>${reqs.length ? `<ul class="lines">${reqs.map((r) => `<li><div><a href="#/staff/request/${r.id}">${esc(r.reference)}</a> ${esc(r.customers.name)}<div class="muted">${fmtDateTime(r.submitted_at)}${r.event_date ? ' &middot; for ' + fmtDate(r.event_date) : ''}</div></div><div class="r">${badge(r.status)}</div></li>`).join('')}</ul>` : '<p class="muted">All caught up.</p>'}</section>`, nav());
  }));

  function paramsOf() { const h = location.hash.split('?')[1] || ''; return new URLSearchParams(h); }

  route(/^\/staff\/requests(\?.*)?$/, () => guard('requests', async (nav) => {
    const s = paramsOf().get('s') || '';
    let query = sb.from('requests').select('id,reference,status,submitted_at,event_date,fulfillment_method,customers(name,phone),quotations(status),orders(id,reference,status)').order('submitted_at', { ascending: false }).limit(200);
    if (['SUBMITTED', 'UNDER_REVIEW', 'NEEDS_INFORMATION', 'DECLINED', 'CANCELLED'].includes(s)) query = query.eq('status', s);
    let rows = await q(query);
    if (s === 'quoted') rows = rows.filter((r) => r.quotations.some((x) => x.status === 'SENT'));
    const filters = [['', 'All'], ['SUBMITTED', 'New'], ['UNDER_REVIEW', 'Under review'], ['quoted', 'Quotation sent'], ['NEEDS_INFORMATION', 'Needs information'], ['DECLINED', 'Declined'], ['CANCELLED', 'Cancelled']];
    setPage(`<h1>Requests</h1><div class="chips">${filters.map(([k, t]) => `<a class="chip ${s === k ? 'on' : ''}" href="#/staff/requests${k ? '?s=' + k : ''}">${t}</a>`).join('')}</div>
      <section class="card">${rows.length ? `<table class="tbl"><thead><tr><th>Reference</th><th>Customer</th><th>Needed</th><th>Status</th><th></th></tr></thead><tbody>${rows.map((r) => {
        const live = r.quotations.find((x) => ['DRAFT', 'SENT', 'ACCEPTED'].includes(x.status));
        return `<tr><td><a href="#/staff/request/${r.id}">${esc(r.reference)}</a><div class="muted">${fmtDate(r.submitted_at)}</div></td><td>${esc(r.customers.name)}<div class="muted">${esc(r.customers.phone)}</div></td><td>${fmtDate(r.event_date)}</td><td>${badge(r.orders && r.orders.status ? r.orders.status : r.status)}${live && !r.orders ? ' ' + badge(live.status) : ''}</td><td><a class="btn small" href="#/staff/request/${r.id}">Open</a></td></tr>`;
      }).join('')}</tbody></table>` : '<p class="muted">No requests here.</p>'}</section>`, nav());
  }));

  route(/^\/staff\/request\/([0-9a-f-]{36})$/i, ([id]) => guard('requests', async (nav) => {
    const [r, kids] = await Promise.all([q(sb.from('requests').select('*, customers(*), delivery_zones(name), request_items(*), request_events(*), quotations(*, quotation_items(*)), orders(id,reference,status)').eq('id', id).single()),
      q(sb.from('requests').select('id,reference,delivery_number,event_date,orders(id,reference,status)').eq('recurrence_parent_id', id).order('delivery_number'))]);
    const quo = (r.quotations || []).find((x) => ['DRAFT', 'SENT', 'ACCEPTED'].includes(x.status));
    const past = (r.quotations || []).filter((x) => x !== quo);
    const ord = r.orders && r.orders.id ? r.orders : (Array.isArray(r.orders) ? r.orders[0] : null);
    const link = trackUrl(r.access_token);
    const wa = waLink(r.customers.phone, `Hello ${r.customers.name.split(' ')[0]}, this is Pure Meals Basket about ${r.reference}. You can see your request and quotation here: ${link}`);
    const canEditReq = !ord && ['SUBMITTED', 'UNDER_REVIEW', 'NEEDS_INFORMATION'].includes(r.status);
    const items = (r.request_items || []).sort((a, b) => a.created_at.localeCompare(b.created_at));
    const qItems = quo ? (quo.quotation_items || []).sort((a, b) => a.sort_order - b.sort_order) : [];
    const draft = quo && quo.status === 'DRAFT';
    setPage(`<p><a href="#/staff/requests">&larr; Requests</a></p>
      <section class="card"><div class="head"><h1>${esc(r.reference)}</h1>${badge(r.status)}</div>
        <div class="two"><div><h3>Customer</h3><p><strong>${esc(r.customers.name)}</strong><br>${esc(r.customers.phone)}${r.customers.email ? '<br>' + esc(r.customers.email) : ''}</p></div>
        <div><h3>Fulfilment</h3><p>${esc(label(r.fulfillment_method))}${r.delivery_zones ? ' &middot; ' + esc(r.delivery_zones.name) + (r.delivery_type ? ' (' + esc(label(r.delivery_type)) + ')' : '') : ''}<br>${esc(r.delivery_address || '')}<br>Needed ${fmtDate(r.event_date)} ${fmtTime(r.event_time)} ${esc(r.location || '')}</p></div></div>
        ${r.recurrence_frequency ? `<p class="note">🔁 <strong>Regular order:</strong> ${r.recurrence_count} deliveries, ${r.recurrence_frequency === 'WEEKLY' ? 'every week' : r.recurrence_frequency === 'FORTNIGHTLY' ? 'every 2 weeks' : 'every month'}, first delivery ${fmtDate(r.event_date)}. The recurring discount is applied automatically to the quotation.</p>` : ''}
        ${r.recurrence_parent_id ? `<p class="note">🔁 Delivery ${r.delivery_number} of a regular order, created from the first request.</p>` : ''}
        ${kids.length ? `<h3>Repeat deliveries</h3><ul class="lines">${kids.map((k) => { const ko = Array.isArray(k.orders) ? k.orders[0] : k.orders; return `<li><div>Delivery ${k.delivery_number} &middot; ${fmtDate(k.event_date)}</div><div class="r">${ko ? `<a href="#/staff/order/${ko.id}">${esc(ko.reference)}</a> ${badge(ko.status)}` : esc(k.reference)}</div></li>`; }).join('')}</ul>` : ''}
        ${r.notes ? `<p class="note"><strong>Customer notes:</strong> ${esc(r.notes)}</p>` : ''}
        <p><button class="btn small ghost" data-act="copylink">Copy customer link</button> <a class="btn small ghost" target="_blank" rel="noopener" href="${wa}">Send on WhatsApp</a></p>
        <h3>Requested items</h3><ul class="lines">${items.map((i) => `<li><div><strong>${esc(i.name)}</strong> &times; ${num(i.quantity)}${i.unit ? ' ' + esc(i.unit) : ''}${i.notes ? `<div class="muted">${esc(i.notes)}</div>` : ''}<div class="muted">${(i.pricing_breakdown || []).map(esc).join(' &middot; ')}</div></div><div class="r">${i.subtotal != null ? money(i.subtotal) : 'Needs a price'} ${i.pricing_status === 'QUOTATION_REQUIRED' ? badge('DRAFT').replace('Draft', 'Quote') : ''}</div></li>`).join('')}</ul>
        ${ord ? `<p><a class="btn" href="#/staff/order/${ord.id}">Open order ${esc(ord.reference)}</a></p>` : ''}
        ${canEditReq ? `<div class="row"><button class="btn small ghost" data-act="status" data-s="UNDER_REVIEW" ${r.status === 'UNDER_REVIEW' ? 'hidden' : ''}>Mark under review</button><button class="btn small ghost" data-act="status" data-s="NEEDS_INFORMATION">Ask the customer for information</button><button class="btn small ghost danger" data-act="status" data-s="DECLINED">Decline</button><button class="btn small ghost danger" data-act="status" data-s="CANCELLED">Cancel</button></div>` : ''}
        ${r.staff_message ? `<p class="muted">Last message to customer: ${esc(r.staff_message)}</p>` : ''}</section>
      ${!ord && !quo && !['DECLINED', 'CANCELLED'].includes(r.status) ? `<section class="card"><h2>Quotation</h2><p>Build a quotation from the requested items. You can adjust prices and add lines before sending.</p><button class="btn" data-act="build">Create quotation</button></section>` : ''}
      ${quo ? `<section class="card"><div class="head"><h2>Quotation ${esc(quo.reference)}</h2>${badge(quo.status)}</div>
        <table class="tbl"><thead><tr><th>Item</th><th class="r">Qty</th><th class="r">Unit price</th><th class="r">Total</th>${draft ? '<th></th>' : ''}</tr></thead><tbody>${qItems.map((i) => `<tr class="${i.needs_price ? 'warnrow' : ''}"><td>${esc(i.name)}${i.description ? `<div class="muted">${esc(i.description)}</div>` : ''}${i.needs_price ? '<div class="muted">Set a price</div>' : ''}</td>
          ${draft ? `<td class="r"><input class="mini" id="q_${i.id}" type="number" step="any" min="0" value="${i.quantity}"></td><td class="r"><input class="mini" id="p_${i.id}" type="number" step="0.01" min="0" value="${i.needs_price ? '' : i.unit_price}"></td>` : `<td class="r">${num(i.quantity)}</td><td class="r">${money(i.unit_price)}</td>`}
          <td class="r">${money(i.subtotal)}</td>${draft ? `<td><button class="link" data-act="saveline" data-id="${i.id}">Save</button> <button class="link danger" data-act="rmline" data-id="${i.id}">Remove</button></td>` : ''}</tr>`).join('')}</tbody></table>
        ${draft ? `<form data-submit="addline" class="inline"><input name="name" placeholder="Add a line (e.g. Service staff)" required><input name="qty" type="number" step="any" min="0" value="1" class="mini"><input name="price" type="number" step="0.01" min="0" placeholder="Price" class="mini" required><button class="btn small" type="submit">Add line</button></form>
        <form data-submit="terms"><div class="two"><label>Discount (KES)<input name="discount" type="number" step="0.01" min="0" value="${quo.discount}"></label><label>Delivery fee (KES)<input name="fee" type="number" step="0.01" min="0" value="${quo.delivery_fee}"></label></div>
        <label>Message to the customer (shown on the quotation)<textarea name="msg" rows="2" maxlength="1000">${esc(quo.customer_message || '')}</textarea></label>
        <label>Internal notes<textarea name="notes" rows="2" maxlength="1000">${esc(quo.notes || '')}</textarea></label><button class="btn small ghost" type="submit">Save terms</button></form>` : `${quo.customer_message ? `<p class="note">${esc(quo.customer_message)}</p>` : ''}`}
        <dl class="sums"><dt>Items</dt><dd>${money(quo.subtotal)}</dd><dt>Discount</dt><dd>-${money(quo.discount)}</dd><dt>Delivery</dt><dd>${money(quo.delivery_fee)}</dd><dt class="big">Total</dt><dd class="big">${money(quo.total)}</dd></dl>
        ${quo.valid_until ? `<p class="muted">Valid until ${fmtDate(quo.valid_until)}</p>` : ''}
        <div class="row">${draft ? '<button class="btn" data-act="send">Send to customer</button>' : ''}${['DRAFT', 'SENT'].includes(quo.status) ? '<button class="btn ghost danger" data-act="withdraw">Withdraw quotation</button>' : ''}</div></section>` : ''}
      ${past.length ? `<section class="card"><h3>Earlier quotations</h3><ul class="lines">${past.map((p) => `<li><div>${esc(p.reference)}<div class="muted">${fmtDate(p.created_at)}</div></div><div class="r">${money(p.total)} ${badge(p.status)}</div></li>`).join('')}</ul></section>` : ''}
      <section class="card"><h3>History</h3><ul class="lines">${(r.request_events || []).sort((a, b) => b.created_at.localeCompare(a.created_at)).map((e) => `<li><div>${esc(e.event.replace(/_/g, ' ').toLowerCase())}${e.detail ? `<div class="muted">${esc(e.detail)}</div>` : ''}</div><div class="r muted">${fmtDateTime(e.created_at)}</div></li>`).join('')}</ul></section>`, nav());
    const reload = () => PMB.render();
    state.actions.copylink = () => copyText(link);
    state.actions.status = async (b) => {
      const s = b.dataset.s; let msg = null;
      if (s !== 'UNDER_REVIEW') { msg = window.prompt(s === 'NEEDS_INFORMATION' ? 'What do you need from the customer?' : 'Message for the customer (required):', ''); if (!msg) return; }
      await rpc('set_request_status', { p_request: id, p_status: s, p_message: msg }); toast('Updated'); reload();
    };
    state.actions.build = async () => { await rpc('build_quotation', { p_request: id }); toast('Quotation created'); reload(); };
    state.actions.saveline = async (b) => {
      const i = b.dataset.id;
      await rpc('quotation_set_line', { p_item: i, p_unit_price: numOrNull(document.getElementById('p_' + i).value), p_quantity: numOrNull(document.getElementById('q_' + i).value) }); toast('Line saved'); reload();
    };
    state.actions.rmline = async (b) => { if (!confirmBox('Remove this line?')) return; await rpc('quotation_remove_line', { p_item: b.dataset.id }); reload(); };
    state.submits.addline = async (f, fd) => { await rpc('quotation_add_line', { p_q: quo.id, p_name: fieldVal(fd, 'name'), p_quantity: numOrNull(fieldVal(fd, 'qty')), p_unit_price: numOrNull(fieldVal(fd, 'price')), p_description: null }); reload(); };
    state.submits.terms = async (f, fd) => { await rpc('quotation_set_terms', { p_q: quo.id, p_discount: numOrNull(fieldVal(fd, 'discount')) || 0, p_delivery_fee: numOrNull(fieldVal(fd, 'fee')) || 0, p_notes: fieldVal(fd, 'notes'), p_message: fieldVal(fd, 'msg') }); toast('Terms saved'); reload(); };
    state.actions.send = async () => { if (!confirmBox('Send this quotation? The customer can then accept it from their link.')) return; await rpc('send_quotation', { p_q: quo.id }); toast('Quotation sent. Share the link with the customer.'); reload(); };
    state.actions.withdraw = async () => { const why = window.prompt('Why are you withdrawing it?', ''); if (!why) return; await rpc('withdraw_quotation', { p_q: quo.id, p_reason: why }); reload(); };
  }));

  route(/^\/staff\/orders(\?.*)?$/, () => guard('orders', async (nav) => {
    const s = paramsOf().get('s') || '';
    let query = sb.from('orders').select('id,reference,status,payment_status,total,balance_due,event_date,customer_name,fulfillment_method').order('created_at', { ascending: false }).limit(200);
    if (s === 'active') query = query.in('status', ['CONFIRMED', 'PREPARING', 'READY', 'OUT_FOR_DELIVERY']);
    else if (s) query = query.eq('status', s);
    const rows = await q(query);
    const filters = [['', 'All'], ['PENDING_PAYMENT', 'Awaiting deposit'], ['active', 'In progress'], ['DELIVERED', 'Delivered'], ['COMPLETED', 'Completed'], ['CANCELLED', 'Cancelled']];
    setPage(`<h1>Orders</h1><div class="chips">${filters.map(([k, t]) => `<a class="chip ${s === k ? 'on' : ''}" href="#/staff/orders${k ? '?s=' + k : ''}">${t}</a>`).join('')}</div>
      <section class="card">${rows.length ? `<table class="tbl"><thead><tr><th>Order</th><th>Customer</th><th>Needed</th><th class="r">Total</th><th class="r">Balance</th><th>Status</th></tr></thead><tbody>${rows.map((o) => `<tr><td><a href="#/staff/order/${o.id}">${esc(o.reference)}</a></td><td>${esc(o.customer_name)}</td><td>${fmtDate(o.event_date)}</td><td class="r">${money(o.total)}</td><td class="r">${money(o.balance_due)}</td><td>${badge(o.status)} ${badge(o.payment_status)}</td></tr>`).join('')}</tbody></table>` : '<p class="muted">No orders here.</p>'}</section>`, nav());
  }));

  route(/^\/staff\/order\/([0-9a-f-]{36})$/i, ([id]) => guard('orders', async (nav) => {
    const o = await q(sb.from('orders').select('*, order_items(*), payments(*), order_events(*), fulfillments(*), delivery_zones(name), requests(id,reference,access_token,recurrence_frequency,recurrence_count,recurrence_parent_id,delivery_number)').eq('id', id).single());
    const kids = o.requests.recurrence_frequency ? await q(sb.from('requests').select('id').eq('recurrence_parent_id', o.requests.id)) : [];
    const parent = o.requests.recurrence_parent_id ? await q(sb.from('requests').select('id,reference,recurrence_count').eq('id', o.requests.recurrence_parent_id).single()) : null;
    const f = Array.isArray(o.fulfillments) ? o.fulfillments[0] : o.fulfillments;
    const drivers = o.fulfillment_method === 'DELIVERY' ? await q(sb.from('staff').select('user_id,full_name').eq('role', 'driver').eq('active', true).order('full_name')) : [];
    const pays = (o.payments || []).sort((a, b) => a.created_at.localeCompare(b.created_at));
    const next = { CONFIRMED: ['PREPARING', 'Start preparing'], PREPARING: ['READY', 'Mark ready'], READY: [o.fulfillment_method === 'DELIVERY' ? 'OUT_FOR_DELIVERY' : 'COMPLETED', o.fulfillment_method === 'DELIVERY' ? 'Send out for delivery' : 'Handed over, complete'], OUT_FOR_DELIVERY: ['DELIVERED', 'Mark delivered'], DELIVERED: ['COMPLETED', 'Complete order'] }[o.status];
    const open = ['PENDING_PAYMENT', 'CONFIRMED', 'PREPARING', 'READY', 'OUT_FOR_DELIVERY', 'DELIVERED'].includes(o.status);
    const wa = waLink(o.customer_phone, `Hello ${o.customer_name.split(' ')[0]}, Pure Meals Basket here about order ${o.reference}. Track it here: ${trackUrl(o.requests.access_token)}`);
    setPage(`<p><a href="#/staff/orders">&larr; Orders</a></p>
      <section class="card"><div class="head"><h1>${esc(o.reference)}</h1><span>${badge(o.status)} ${badge(o.payment_status)}</span></div>
        <p><strong>${esc(o.customer_name)}</strong> &middot; ${esc(o.customer_phone)}${o.customer_email ? ' &middot; ' + esc(o.customer_email) : ''} &middot; <a href="#/staff/request/${o.requests.id}">${esc(o.requests.reference)}</a></p>
        <p class="muted">${esc(label(o.fulfillment_method))}${o.delivery_zones ? ' &middot; ' + esc(o.delivery_zones.name) : ''} ${esc(o.delivery_address || '')} &middot; Needed ${fmtDate(o.event_date)} ${fmtTime(o.event_time)}</p>
        ${o.requests.recurrence_frequency ? `<p class="note">🔁 <strong>Regular order</strong>, ${o.requests.recurrence_count} deliveries ${o.requests.recurrence_frequency === 'WEEKLY' ? 'every week' : o.requests.recurrence_frequency === 'FORTNIGHTLY' ? 'every 2 weeks' : 'every month'}. ${kids.length ? 'The repeat deliveries are scheduled, see the request page.' : (o.status === 'CANCELLED' ? '' : 'Create the other deliveries once the customer is happy to go ahead. Each one becomes its own order with its own deposit.')}</p>${!kids.length && o.status !== 'CANCELLED' ? '<p><button class="btn" data-act="sched">Schedule the repeat deliveries</button></p>' : ''}` : ''}
        ${parent ? `<p class="note">🔁 Delivery ${o.requests.delivery_number} of ${parent.recurrence_count} in a regular order. <a href="#/staff/request/${parent.id}">See all deliveries</a></p>` : ''}
        ${o.fulfillment_method === 'DELIVERY' && open && o.status !== 'PENDING_PAYMENT' ? `<form data-submit="driver" class="row"><label>Driver<select name="driver"><option value="">Not assigned</option>${drivers.map((d) => `<option value="${d.user_id}" ${f && f.driver_id === d.user_id ? 'selected' : ''}>${esc(d.full_name)}</option>`).join('')}</select></label><button class="btn small" type="submit">Save driver</button></form>` : ''}
        <p><a class="btn small ghost" target="_blank" rel="noopener" href="${wa}">Message customer</a> <button class="btn small ghost" data-act="copylink">Copy customer link</button></p>
        <ul class="lines">${o.order_items.map((i) => `<li><div><strong>${esc(i.name)}</strong> &times; ${num(i.quantity)}${i.description ? `<div class="muted">${esc(i.description)}</div>` : ''}</div><div class="r">${money(i.subtotal)}</div></li>`).join('')}</ul>
        <dl class="sums"><dt>Items</dt><dd>${money(o.subtotal)}</dd><dt>Discount</dt><dd>-${money(o.discount)}</dd><dt>Delivery</dt><dd>${money(o.delivery_fee)}</dd><dt class="big">Total</dt><dd class="big">${money(o.total)}</dd><dt>Deposit required</dt><dd>${money(o.payment_required)}</dd><dt>Paid</dt><dd>${money(o.amount_paid)}</dd><dt class="big">Balance</dt><dd class="big">${money(o.balance_due)}</dd></dl>
        <div class="row">${next ? `<button class="btn" data-act="adv" data-s="${next[0]}">${next[1]}</button>` : ''}${o.status === 'OUT_FOR_DELIVERY' ? '<button class="btn ghost" data-act="failed">Delivery failed</button>' : ''}${['PENDING_PAYMENT', 'CONFIRMED'].includes(o.status) ? '<button class="btn ghost danger" data-act="cancel">Cancel order</button>' : ''}</div>
        ${f && f.failure_reason ? `<p class="note">Last delivery problem: ${esc(f.failure_reason)}</p>` : ''}</section>
      <section class="card"><h2>Payments</h2>
        ${pays.length ? `<table class="tbl"><thead><tr><th>Reference</th><th>Method</th><th>Date</th><th class="r">Amount</th><th>Status</th><th></th></tr></thead><tbody>${pays.map((p) => `<tr><td>${esc(p.reference)}${p.provider_reference ? `<div class="muted">${esc(p.provider_reference)}</div>` : ''}${p.note ? `<div class="muted">${esc(p.note)}</div>` : ''}</td><td>${esc(label(p.method))}</td><td>${fmtDate(p.paid_at)}</td><td class="r">${money(p.amount)}</td><td>${badge(p.status)}</td>
          <td>${p.status === 'PENDING' && isFinance() ? `<button class="link" data-act="confirm" data-id="${p.id}">Confirm</button> <button class="link danger" data-act="reject" data-id="${p.id}">Reject</button>` : ''}${p.status === 'SUCCESS' && isOwner() && ['PENDING_PAYMENT', 'CONFIRMED'].includes(o.status) ? `<button class="link danger" data-act="reverse" data-id="${p.id}">Reverse</button>` : ''}</td></tr>`).join('')}</tbody></table>` : '<p class="muted">No payments recorded yet.</p>'}
        ${open && Number(o.balance_due) > 0 ? `<h3>Record a payment</h3><form data-submit="pay"><div class="two"><label>Amount (KES)<input name="amount" type="number" step="0.01" min="1" required value="${o.status === 'PENDING_PAYMENT' ? Math.max(0, Number(o.payment_required) - Number(o.amount_paid)) : Number(o.balance_due)}"></label>
          <label>Method<select name="method"><option value="MPESA">M-Pesa</option><option value="CASH">Cash</option></select></label></div>
          <div class="two"><label>M-Pesa code (required for M-Pesa)<input name="ref" maxlength="30"></label><label>Date received<input name="date" type="date" max="${new Date(Date.now() + 3 * 3600e3).toISOString().slice(0, 10)}" value="${new Date(Date.now() + 3 * 3600e3).toISOString().slice(0, 10)}"></label></div>
          <label>Note<input name="note" maxlength="200"></label>
          ${isFinance() ? '<label class="radio"><input type="checkbox" name="confirm" checked> Confirm it now (I have seen the money)</label>' : '<p class="muted">A manager will confirm this payment.</p>'}
          <button class="btn" type="submit">Record payment</button></form>` : ''}</section>
      <section class="card"><h3>History</h3><ul class="lines">${(o.order_events || []).sort((a, b) => b.created_at.localeCompare(a.created_at)).map((e) => `<li><div>${esc(e.event.replace(/_/g, ' ').toLowerCase())}${e.detail ? `<div class="muted">${esc(e.detail)}</div>` : ''}</div><div class="r muted">${fmtDateTime(e.created_at)}</div></li>`).join('')}</ul></section>`, nav());
    const reload = () => PMB.render();
    state.actions.copylink = () => copyText(trackUrl(o.requests.access_token));
    state.submits.driver = async (fm, fd) => { await rpc('assign_driver', { p_order: id, p_driver: fieldVal(fd, 'driver') || null }); toast('Driver saved'); reload(); };
    state.actions.sched = async () => {
      if (!confirmBox('Create all the repeat deliveries now? Each becomes its own order with the same prices and its own deposit.')) return;
      const n = await rpc('schedule_recurring', { p_order: id }); toast(n + ' repeat deliveries created'); reload();
    };
    state.actions.adv = async (b) => {
      let note = null;
      if (b.dataset.s === 'DELIVERED') note = window.prompt('Who received the order? (optional)', '') || null;
      if (b.dataset.s === 'COMPLETED' && Number(o.balance_due) > 0) { toast('Record the remaining balance first.', 'err'); return; }
      await rpc('advance_order', { p_order: id, p_status: b.dataset.s, p_note: note }); toast('Order updated'); reload();
    };
    state.actions.failed = async () => { const why = window.prompt('What went wrong with the delivery?', ''); if (!why) return; await rpc('advance_order', { p_order: id, p_status: 'READY', p_note: why }); reload(); };
    state.actions.cancel = async () => { const why = window.prompt('Reason for cancelling (required):', ''); if (!why) return; if (Number(o.amount_paid) > 0 && !confirmBox('Payments exist on this order. Refunds are handled outside the app. Cancel anyway?')) return; await rpc('cancel_order', { p_order: id, p_reason: why }); reload(); };
    state.actions.confirm = async (b) => { await rpc('confirm_payment', { p_payment: b.dataset.id }); toast('Payment confirmed'); reload(); };
    state.actions.reject = async (b) => { const why = window.prompt('Why is this payment rejected?', ''); if (!why) return; await rpc('reject_payment', { p_payment: b.dataset.id, p_reason: why }); reload(); };
    state.actions.reverse = async (b) => { const why = window.prompt('Why is this payment being reversed?', ''); if (!why) return; await rpc('reverse_payment', { p_payment: b.dataset.id, p_reason: why }); reload(); };
    state.submits.pay = async (f, fd) => {
      const d = fieldVal(fd, 'date');
      await rpc('record_payment', { p_order: id, p_amount: numOrNull(fieldVal(fd, 'amount')), p_method: fieldVal(fd, 'method'), p_provider_ref: fieldVal(fd, 'ref') || null, p_paid_at: d ? new Date(d + 'T12:00:00+03:00').toISOString() : null, p_note: fieldVal(fd, 'note') || null, p_confirm: fd.get('confirm') === 'on' });
      toast('Payment recorded'); reload();
    };
  }));

  route(/^\/staff\/payments$/, () => guard('payments', async (nav) => {
    const rows = await q(sb.from('payments').select('*, orders(id,reference,customer_name)').order('created_at', { ascending: false }).limit(200));
    const pending = rows.filter((p) => p.status === 'PENDING');
    const tbl = (list) => `<table class="tbl"><thead><tr><th>Payment</th><th>Order</th><th>Method</th><th>Date</th><th class="r">Amount</th><th>Status</th></tr></thead><tbody>${list.map((p) => `<tr><td>${esc(p.reference)}${p.provider_reference ? `<div class="muted">${esc(p.provider_reference)}</div>` : ''}</td><td><a href="#/staff/order/${p.orders.id}">${esc(p.orders.reference)}</a><div class="muted">${esc(p.orders.customer_name)}</div></td><td>${esc(label(p.method))}</td><td>${fmtDate(p.paid_at)}</td><td class="r">${money(p.amount)}</td><td>${badge(p.status)}</td></tr>`).join('')}</tbody></table>`;
    setPage(`<h1>Payments</h1><section class="card"><h2>Waiting for confirmation</h2>${pending.length ? tbl(pending) : '<p class="muted">Nothing waiting.</p>'}<p class="muted">Open the order to confirm or reject a payment.</p></section><section class="card"><h2>All payments</h2>${rows.length ? tbl(rows) : '<p class="muted">No payments yet.</p>'}</section>`, nav());
  }));

  // ---- kitchen board (cooks) ----
  route(/^\/staff\/kitchen$/, () => guard('kitchen', async (nav) => {
    const d = await rpc('kitchen_board');
    const when = (o) => (o.event_date ? `${fmtDate(o.event_date)} ${fmtTime(o.event_time)}` : 'No date set');
    const choices = (c) => (Array.isArray(c) ? c.map((x) => (typeof x === 'object' ? Object.values(x).join(': ') : x)).join(', ') : (c && typeof c === 'object' ? Object.values(c).join(', ') : ''));
    const tot = {}; (d.totals || []).forEach((t) => { (tot[t.event_date || 'none'] = tot[t.event_date || 'none'] || []).push(t); });
    setPage(`<h1>Kitchen</h1>
      <section class="card"><h2>What to prepare, by day</h2>${Object.keys(tot).length ? Object.keys(tot).sort().map((k) => `<h3>${k === 'none' ? 'No date set' : fmtDate(k)}</h3><ul class="lines">${tot[k].map((t) => `<li><div>${esc(t.name)}</div><div class="r"><strong>${num(t.quantity)}</strong> ${esc(t.unit || '')}</div></li>`).join('')}</ul>`).join('') : '<p class="muted">Nothing to prepare right now.</p>'}</section>
      ${(d.orders || []).map((o) => `<section class="card"><div class="head"><h2>${esc(o.reference)}</h2><span>${badge(o.status)}</span></div><p class="muted">${esc(when(o))} &middot; ${esc(label(o.fulfillment_method))}</p>
        <ul class="lines">${o.items.map((i) => `<li><div><strong>${esc(i.name)}</strong>${choices(i.choices) ? `<div class="muted">${esc(choices(i.choices))}</div>` : ''}${i.notes ? `<div class="note">${esc(i.notes)}</div>` : ''}</div><div class="r">&times; ${num(i.quantity)}</div></li>`).join('')}</ul>
        ${o.notes ? `<p class="note">${esc(o.notes)}</p>` : ''}
        <div class="row">${o.status === 'CONFIRMED' ? `<button class="btn" data-act="kadv" data-id="${o.id}" data-s="PREPARING">Start preparing</button>` : ''}${o.status === 'PREPARING' ? `<button class="btn" data-act="kadv" data-id="${o.id}" data-s="READY">Mark ready</button>` : ''}${o.status === 'READY' ? '<span class="muted">Ready. Waiting for pickup or the driver.</span>' : ''}</div></section>`).join('')}`, nav());
    state.actions.kadv = async (b) => { await rpc('advance_order', { p_order: b.dataset.id, p_status: b.dataset.s }); toast('Updated'); PMB.render(); };
  }));

  // ---- deliveries board (drivers) ----
  route(/^\/staff\/deliveries$/, () => guard('deliveries', async (nav) => {
    const list = await rpc('driver_board');
    const office = isOffice();
    const card = (o) => `<section class="card"><div class="head"><h2>${esc(o.reference)}</h2><span>${badge(o.status)}</span></div>
      <p><strong>${esc(o.customer_name)}</strong> &middot; <a href="tel:${esc(o.customer_phone)}">${esc(o.customer_phone)}</a> &middot; <a target="_blank" rel="noopener" href="${waLink(o.customer_phone, 'Hello, this is Pure Meals Basket about your delivery ' + o.reference)}">WhatsApp</a></p>
      <p class="muted">${esc(o.zone || '')} ${esc(o.address || '')} ${o.location ? '&middot; ' + esc(o.location) : ''}</p>
      <p class="muted">Needed ${fmtDate(o.event_date)} ${fmtTime(o.event_time)}</p>${o.delivery_notes ? `<p class="note">${esc(o.delivery_notes)}</p>` : ''}
      <ul class="lines">${o.items.map((i) => `<li><div>${esc(i.name)}</div><div class="r">&times; ${num(i.quantity)}</div></li>`).join('')}</ul>
      ${Number(o.balance_due) > 0 ? `<p class="note">Collect on delivery: <strong>${money(o.balance_due)}</strong>. Hand the cash or M-Pesa code to the office.</p>` : ''}
      <div class="row">${!o.driver_id && !office ? `<button class="btn" data-act="claim" data-id="${o.id}">Take this delivery</button>` : ''}${o.driver_id ? `<span class="muted">Driver: ${esc(o.mine ? 'you' : o.driver_name || '')}</span>` : ''}
      ${(o.mine || office) && o.status === 'READY' ? `<button class="btn" data-act="dadv" data-id="${o.id}" data-s="OUT_FOR_DELIVERY">Out for delivery</button>` : ''}
      ${(o.mine || office) && o.status === 'OUT_FOR_DELIVERY' ? `<button class="btn" data-act="dadv" data-id="${o.id}" data-s="DELIVERED">Delivered</button><button class="btn ghost" data-act="dfail" data-id="${o.id}">Could not deliver</button>` : ''}</div></section>`;
    const mine = list.filter((o) => o.mine), rest = list.filter((o) => !o.mine);
    setPage(`<h1>Deliveries</h1>${office ? '' : `<h2>My deliveries</h2>${mine.length ? mine.map(card).join('') : '<p class="muted">You have not taken any delivery yet.</p>'}<h2>Available to take</h2>`}${(office ? list : rest).length ? (office ? list : rest).map(card).join('') : '<p class="muted">No deliveries waiting.</p>'}`, nav());
    state.actions.claim = async (b) => { await rpc('claim_delivery', { p_order: b.dataset.id }); toast('Delivery is yours'); PMB.render(); };
    state.actions.dadv = async (b) => {
      let note = null; if (b.dataset.s === 'DELIVERED') note = window.prompt('Who received the order? (optional)', '') || null;
      await rpc('advance_order', { p_order: b.dataset.id, p_status: b.dataset.s, p_note: note }); toast('Updated'); PMB.render();
    };
    state.actions.dfail = async (b) => { const why = window.prompt('What went wrong with the delivery?', ''); if (!why) return; await rpc('advance_order', { p_order: b.dataset.id, p_status: 'READY', p_note: why }); PMB.render(); };
  }));

  // ---- catalogue ----
  route(/^\/staff\/catalogue$/, () => guard('catalogue', async (nav) => {
    if (!isMgr()) { setPage('<section class="card"><p>Managers only.</p></section>', nav()); return; }
    const [cats, prods, svcs] = await Promise.all([q(sb.from('categories').select('*').order('sort_order').order('name')), q(sb.from('products').select('id,name,status,is_available,pricing_type,base_price,category_id').order('name')), q(sb.from('services').select('id,name,status,is_available,pricing_type,base_price').order('name'))]);
    const catName = (id) => (cats.find((c) => c.id === id) || {}).name || '';
    setPage(`<h1>Catalogue</h1>
      <section class="card"><div class="head"><h2>Categories</h2></div>
        <ul class="lines">${cats.map((c) => `<li><div><strong>${esc(c.name)}</strong> ${c.is_active ? '' : badge('ARCHIVED').replace('Archived', 'Hidden')}<div class="muted">${esc(c.description || '')}</div></div><div class="r"><button class="link" data-act="editcat" data-id="${c.id}">Edit</button></div></li>`).join('') || '<li class="muted">No categories yet.</li>'}</ul>
        <button class="btn small" data-act="newcat">Add category</button></section>
      <section class="card"><div class="head"><h2>Products</h2><a class="btn small" href="#/staff/product/new">Add product</a></div>
        ${prods.length ? `<table class="tbl"><thead><tr><th>Name</th><th>Category</th><th>Pricing</th><th>Status</th></tr></thead><tbody>${prods.map((p) => `<tr><td><a href="#/staff/product/${p.id}">${esc(p.name)}</a></td><td>${esc(catName(p.category_id))}</td><td>${esc(label(p.pricing_type))}${p.base_price != null ? ' ' + money(p.base_price) : ''}</td><td>${badge(p.status)}${p.is_available ? '' : ' <span class="muted">unavailable</span>'}</td></tr>`).join('')}</tbody></table>` : '<p class="muted">No products yet.</p>'}</section>
      <section class="card"><div class="head"><h2>Services</h2><a class="btn small" href="#/staff/service/new">Add service</a></div>
        ${svcs.length ? `<table class="tbl"><thead><tr><th>Name</th><th>Pricing</th><th>Status</th></tr></thead><tbody>${svcs.map((p) => `<tr><td><a href="#/staff/service/${p.id}">${esc(p.name)}</a></td><td>${esc(label(p.pricing_type))}${p.base_price != null ? ' ' + money(p.base_price) : ''}</td><td>${badge(p.status)}${p.is_available ? '' : ' <span class="muted">unavailable</span>'}</td></tr>`).join('')}</tbody></table>` : '<p class="muted">No services yet.</p>'}</section>`, nav());
    function catForm(c) {
      const name = window.prompt('Category name', c ? c.name : ''); if (!name) return null;
      const desc = window.prompt('Short description (optional)', c ? c.description || '' : '');
      return { name: name.trim(), slug: slugify(name), description: desc ? desc.trim() : null };
    }
    state.actions.newcat = async () => { const v = catForm(null); if (!v) return; await q(sb.from('categories').insert({ ...v, sort_order: cats.length })); toast('Category added'); PMB.render(); };
    state.actions.editcat = async (b) => {
      const c = cats.find((x) => String(x.id) === b.dataset.id); const v = catForm(c); if (!v) return;
      const hide = confirmBox(c.is_active ? 'Keep this category visible to customers?\n\nOK = visible, Cancel = hide it' : 'Show this category to customers?\n\nOK = visible, Cancel = keep hidden');
      await q(sb.from('categories').update({ name: v.name, description: v.description, is_active: hide }).eq('id', c.id)); toast('Saved'); PMB.render();
    };
  }));

  function itemEditor(kind) {
    const table = kind === 'product' ? 'products' : 'services';
    return ([id]) => guard('catalogue', async (nav) => {
      if (!isMgr()) { setPage('<section class="card"><p>Managers only.</p></section>', nav()); return; }
      const isNew = id === 'new';
      const [cats, row, tiers] = await Promise.all([
        q(sb.from('categories').select('id,name').order('name')),
        isNew ? Promise.resolve({ pricing_type: 'FIXED', status: 'DRAFT', is_available: true, requires_review: false }) : q(sb.from(table).select(kind === 'product' ? '*, product_options(*, product_option_values(*))' : '*').eq('id', id).single()),
        isNew ? Promise.resolve([]) : q(sb.from('price_tiers').select('*').eq('priceable_type', kind).eq('priceable_id', id).order('min_quantity'))
      ]);
      const types = ['FIXED', 'PER_UNIT', 'PER_WEIGHT', 'PER_VOLUME', 'PER_PERSON', 'TIERED', 'CUSTOM'];
      const opts = (row.product_options || []).sort((a, b) => a.sort_order - b.sort_order);
      setPage(`<p><a href="#/staff/catalogue">&larr; Catalogue</a></p>
        <section class="card"><h1>${isNew ? 'New ' + kind : esc(row.name)}</h1>
        <form data-submit="save"><div class="two"><label>Name *<input name="name" required maxlength="150" value="${esc(row.name || '')}"></label>
          <label>Category ${kind === 'product' ? '*' : ''}<select name="category" ${kind === 'product' ? 'required' : ''}><option value="">${kind === 'product' ? 'Choose' : 'None'}</option>${cats.map((c) => `<option value="${c.id}" ${row.category_id === c.id ? 'selected' : ''}>${esc(c.name)}</option>`).join('')}</select></label></div>
          <label>Short description<input name="short" maxlength="300" value="${esc(row.short_description || '')}"></label>
          <label>Full description<textarea name="desc" rows="3">${esc(row.description || '')}</textarea></label>
          <label>Image link (optional)<input name="image" type="url" value="${esc(row.image_url || '')}"></label>
          <div class="two"><label>Pricing<select name="ptype">${types.map((t) => `<option value="${t}" ${row.pricing_type === t ? 'selected' : ''}>${esc(label(t))}</option>`).join('')}</select></label>
          <label>Base price (KES)<input name="price" type="number" step="0.01" min="0" value="${row.base_price == null ? '' : row.base_price}"></label></div>
          <div class="three"><label>Unit (e.g. person, litre, kg)<input name="unit" maxlength="20" value="${esc(row.unit || '')}"></label><label>Minimum quantity<input name="min" type="number" step="any" min="0" value="${row.minimum_quantity == null ? '' : row.minimum_quantity}"></label><label>Maximum quantity<input name="max" type="number" step="any" min="0" value="${row.maximum_quantity == null ? '' : row.maximum_quantity}"></label></div>
          <div class="two"><label>Status<select name="status"><option value="DRAFT" ${row.status === 'DRAFT' ? 'selected' : ''}>Draft (hidden)</option><option value="ACTIVE" ${row.status === 'ACTIVE' ? 'selected' : ''}>Active (on the menu)</option><option value="ARCHIVED" ${row.status === 'ARCHIVED' ? 'selected' : ''}>Archived</option></select></label>
          <div><label class="radio"><input type="checkbox" name="avail" ${row.is_available ? 'checked' : ''}> Available to order</label><label class="radio"><input type="checkbox" name="review" ${row.requires_review ? 'checked' : ''}> PMB must confirm the price in the quotation</label></div></div>
          <p class="muted">Fixed price uses the base price once. Per-unit, weight, volume and person types multiply by the quantity. Tiered uses the brackets below. Custom items are priced by you in the quotation.</p>
          <button class="btn" type="submit">${isNew ? 'Create' : 'Save changes'}</button></form></section>
        ${!isNew && row.pricing_type === 'TIERED' ? `<section class="card"><h2>Price brackets</h2><ul class="lines">${tiers.map((t) => `<li><div>${num(t.min_quantity)} to ${t.max_quantity == null ? 'and above' : num(t.max_quantity)}</div><div class="r">${money(t.unit_price)} each <button class="link danger" data-act="rmtier" data-id="${t.id}">Remove</button></div></li>`).join('') || '<li class="muted">No brackets yet.</li>'}</ul>
          <form data-submit="tier" class="inline"><input name="min" type="number" step="any" min="0" placeholder="From" class="mini" required><input name="max" type="number" step="any" min="0" placeholder="To (blank = no limit)" class="mini"><input name="price" type="number" step="0.01" min="0" placeholder="Unit price" class="mini" required><button class="btn small" type="submit">Add bracket</button></form></section>` : ''}
        ${!isNew && kind === 'product' ? `<section class="card"><h2>Options</h2>${opts.map((o) => `<div class="optbox"><div class="head"><strong>${esc(o.name)}</strong> ${o.is_required ? '<span class="muted">required</span>' : ''} ${o.per_unit ? '<span class="muted">charged per person or unit</span>' : ''}<button class="link danger" data-act="rmopt" data-id="${o.id}">Remove option</button></div>
          <ul class="lines">${(o.product_option_values || []).sort((a, b) => a.sort_order - b.sort_order).map((v) => `<li><div>${esc(v.name)} ${v.is_available ? '' : '<span class="muted">unavailable</span>'}</div><div class="r">${Number(v.price_modifier) ? '+' + money(v.price_modifier) : 'included'} <button class="link" data-act="togval" data-id="${v.id}" data-on="${v.is_available}">${v.is_available ? 'Hide' : 'Show'}</button> <button class="link danger" data-act="rmval" data-id="${v.id}">Remove</button></div></li>`).join('')}</ul>
          <form data-submit="val" data-opt="${o.id}" class="inline"><input name="name" placeholder="Choice (e.g. Large)" required><input name="mod" type="number" step="0.01" placeholder="Extra price" class="mini"><button class="btn small" type="submit">Add choice</button></form></div>`).join('') || '<p class="muted">No options yet.</p>'}
          <form data-submit="opt" class="inline"><input name="name" placeholder="New option (e.g. Size)" required><label class="radio"><input type="checkbox" name="req"> Required</label><label class="radio"><input type="checkbox" name="perunit"> Price applies to every person or unit</label><button class="btn small" type="submit">Add option</button></form></section>` : ''}`, nav());
      const reload = () => PMB.render();
      state.submits.save = async (f, fd) => {
        const rec = { name: fieldVal(fd, 'name'), short_description: fieldVal(fd, 'short') || null, description: fieldVal(fd, 'desc') || null, image_url: fieldVal(fd, 'image') || null, pricing_type: fieldVal(fd, 'ptype'), base_price: numOrNull(fieldVal(fd, 'price')), unit: fieldVal(fd, 'unit') || null, minimum_quantity: numOrNull(fieldVal(fd, 'min')), maximum_quantity: numOrNull(fieldVal(fd, 'max')), status: fieldVal(fd, 'status'), is_available: fd.get('avail') === 'on', requires_review: fd.get('review') === 'on', category_id: numOrNull(fieldVal(fd, 'category')) };
        if (!['CUSTOM', 'TIERED'].includes(rec.pricing_type) && rec.base_price == null) throw new Error('Enter a base price for this pricing type.');
        if (!['FIXED', 'CUSTOM'].includes(rec.pricing_type) && !rec.unit) throw new Error('Enter a unit, for example person or litre.');
        if (isNew) { rec.slug = slugify(rec.name) + '-' + Math.random().toString(36).slice(2, 6); const r = await q(sb.from(table).insert(rec).select('id').single()); toast('Created'); go('/staff/' + kind + '/' + r.id); }
        else { await q(sb.from(table).update(rec).eq('id', id)); toast('Saved'); reload(); }
      };
      state.submits.tier = async (f, fd) => { await q(sb.from('price_tiers').insert({ priceable_type: kind, priceable_id: Number(id), min_quantity: numOrNull(fieldVal(fd, 'min')), max_quantity: numOrNull(fieldVal(fd, 'max')), unit_price: numOrNull(fieldVal(fd, 'price')) })); reload(); };
      state.actions.rmtier = async (b) => { await q(sb.from('price_tiers').delete().eq('id', b.dataset.id)); reload(); };
      state.submits.opt = async (f, fd) => { await q(sb.from('product_options').insert({ product_id: Number(id), name: fieldVal(fd, 'name'), is_required: fd.get('req') === 'on', per_unit: fd.get('perunit') === 'on', sort_order: opts.length })); reload(); };
      state.actions.rmopt = async (b) => { if (!confirmBox('Remove this option and its choices?')) return; await q(sb.from('product_options').delete().eq('id', b.dataset.id)); reload(); };
      state.submits.val = async (f, fd) => { await q(sb.from('product_option_values').insert({ product_option_id: Number(f.dataset.opt), name: fieldVal(fd, 'name'), price_modifier: numOrNull(fieldVal(fd, 'mod')) || 0 })); reload(); };
      state.actions.togval = async (b) => { await q(sb.from('product_option_values').update({ is_available: b.dataset.on !== 'true' }).eq('id', b.dataset.id)); reload(); };
      state.actions.rmval = async (b) => { await q(sb.from('product_option_values').delete().eq('id', b.dataset.id)); reload(); };
    });
  }
  route(/^\/staff\/product\/([0-9a-z]+)$/, itemEditor('product'));
  route(/^\/staff\/service\/([0-9a-z]+)$/, itemEditor('service'));

  route(/^\/staff\/zones$/, () => guard('zones', async (nav) => {
    if (!isMgr()) { setPage('<section class="card"><p>Managers only.</p></section>', nav()); return; }
    const zones = await q(sb.from('delivery_zones').select('*').order('sort_order').order('name'));
    setPage(`<h1>Delivery areas</h1><section class="card"><ul class="lines">${zones.map((z) => `<li><div><strong>${esc(z.name)}</strong> ${z.is_active ? '' : '<span class="muted">hidden</span>'}<div class="muted">Base ${money(z.base_charge)} &middot; door to door +${money(z.door_to_door_surcharge)} &middot; beyond stage +${money(z.beyond_stage_surcharge)}${z.free_delivery_above != null ? ' &middot; free above ' + money(z.free_delivery_above) : ''}</div></div><div class="r"><button class="link" data-act="edit" data-id="${z.id}">Edit</button></div></li>`).join('') || '<li class="muted">No delivery areas yet. Customers cannot choose delivery until you add one.</li>'}</ul></section>
      <section class="card"><h2 id="zt">Add an area</h2><form data-submit="zone"><input type="hidden" name="id" value="">
        <div class="two"><label>Area name *<input name="name" required maxlength="100"></label><label>Description<input name="desc" maxlength="200"></label></div>
        <div class="three"><label>Base charge (KES)<input name="base" type="number" step="0.01" min="0" value="0"></label><label>Door-to-door extra<input name="d2d" type="number" step="0.01" min="0" value="0"></label><label>Beyond-the-stage extra<input name="bey" type="number" step="0.01" min="0" value="0"></label></div>
        <div class="two"><label>Free delivery above (KES, optional)<input name="free" type="number" step="0.01" min="0"></label><label class="radio"><input type="checkbox" name="active" checked> Customers can choose this area</label></div>
        <button class="btn" type="submit">Save area</button></form></section>`, nav());
    state.actions.edit = (b) => {
      const z = zones.find((x) => String(x.id) === b.dataset.id); const f = document.querySelector('form[data-submit=zone]');
      f.id.value = z.id; f.name.value = z.name; f.desc.value = z.description || ''; f.base.value = z.base_charge; f.d2d.value = z.door_to_door_surcharge; f.bey.value = z.beyond_stage_surcharge; f.free.value = z.free_delivery_above == null ? '' : z.free_delivery_above; f.active.checked = z.is_active;
      document.getElementById('zt').textContent = 'Edit ' + z.name; f.scrollIntoView({ behavior: 'smooth' });
    };
    state.submits.zone = async (f, fd) => {
      const rec = { name: fieldVal(fd, 'name'), description: fieldVal(fd, 'desc') || null, base_charge: numOrNull(fieldVal(fd, 'base')) || 0, door_to_door_surcharge: numOrNull(fieldVal(fd, 'd2d')) || 0, beyond_stage_surcharge: numOrNull(fieldVal(fd, 'bey')) || 0, free_delivery_above: numOrNull(fieldVal(fd, 'free')), is_active: fd.get('active') === 'on' };
      const id = fieldVal(fd, 'id');
      if (id) await q(sb.from('delivery_zones').update(rec).eq('id', id)); else await q(sb.from('delivery_zones').insert({ ...rec, slug: slugify(rec.name) + '-' + Math.random().toString(36).slice(2, 5) }));
      toast('Saved'); PMB.render();
    };
  }));

  route(/^\/staff\/settings$/, () => guard('settings', async (nav) => {
    if (!isOwner()) { setPage('<section class="card"><p>Owner only.</p></section>', nav()); return; }
    const s = await q(sb.from('settings').select('*').eq('id', 1).single());
    setPage(`<h1>Settings</h1><section class="card"><form data-submit="save">
      <label>Business name<input name="name" required maxlength="100" value="${esc(s.business_name)}"></label>
      <div class="two"><label>Deposit required to confirm an order (%)<input name="dep" type="number" min="0" max="100" step="1" required value="${s.deposit_percent}"></label><label>Quotation valid for (days)<input name="days" type="number" min="1" max="60" required value="${s.quote_valid_days}"></label></div>
      <div class="two"><label>Regular-order discount on the food (%)<input name="rdisc" type="number" min="0" max="50" step="1" required value="${s.recurring_discount_percent}"></label><label>Fewest deliveries for a regular order<input name="rmin" type="number" min="2" max="52" step="1" required value="${s.recurring_min_deliveries}"></label></div>
      <div class="two"><label>Contact phone<input name="phone" maxlength="30" value="${esc(s.contact_phone || '')}"></label><label>Contact email<input name="email" type="email" maxlength="120" value="${esc(s.contact_email || '')}"></label></div>
      <label>How customers pay (shown on their order page)<textarea name="instr" rows="6" required maxlength="1500">${esc(s.payment_instructions)}</textarea></label>
      <p class="muted">Put your M-Pesa till or paybill number here, and tell customers to quote their order reference. Changes apply immediately to every unpaid order.</p>
      <button class="btn" type="submit">Save settings</button></form></section>`, nav());
    state.submits.save = async (f, fd) => {
      await q(sb.from('settings').update({ business_name: fieldVal(fd, 'name'), deposit_percent: numOrNull(fieldVal(fd, 'dep')), quote_valid_days: numOrNull(fieldVal(fd, 'days')), recurring_discount_percent: numOrNull(fieldVal(fd, 'rdisc')), recurring_min_deliveries: numOrNull(fieldVal(fd, 'rmin')), contact_phone: fieldVal(fd, 'phone') || null, contact_email: fieldVal(fd, 'email') || null, payment_instructions: fieldVal(fd, 'instr') }).eq('id', 1));
      toast('Settings saved');
    };
  }));

  route(/^\/staff\/team$/, () => guard('team', async (nav) => {
    if (!isOwner()) { setPage('<section class="card"><p>Owner only.</p></section>', nav()); return; }
    const team = await q(sb.from('staff').select('*').order('created_at'));
    setPage(`<h1>Team</h1><section class="card"><ul class="lines">${team.map((t) => `<li><div><strong>${esc(t.full_name)}</strong> ${t.active ? '' : '<span class="muted">no access</span>'}<div class="muted">${esc(label(t.role) || t.role)}</div></div><div class="r">${t.user_id === me.id ? '<span class="muted">you</span>' : `<button class="link" data-act="role" data-id="${t.user_id}" data-role="${t.role}" data-active="${t.active}">Change</button>`}</div></li>`).join('')}</ul></section>
      <section class="card"><h2>Add a team member</h2><p class="muted">They must already have a login. Create it in the Supabase dashboard under Authentication, then add them here by email. </p><ul class="lines">${Object.keys(ROLE_INFO).map((k) => `<li><div><strong>${esc(ROLE_INFO[k][0])}</strong><div class="muted">${esc(ROLE_INFO[k][1])}</div></div></li>`).join('')}</ul>
      <form data-submit="add"><div class="two"><label>Email<input name="email" type="email" required></label><label>Full name<input name="name" required maxlength="100"></label></div><label>Role<select name="role">${Object.keys(ROLE_INFO).reverse().map((k) => `<option value="${k}">${esc(ROLE_INFO[k][0])}</option>`).join('')}</select></label><button class="btn" type="submit">Add</button></form></section>`, nav());
    state.submits.add = async (f, fd) => { await rpc('add_staff_by_email', { p_email: fieldVal(fd, 'email'), p_name: fieldVal(fd, 'name'), p_role: fieldVal(fd, 'role') }); toast('Added'); PMB.render(); };
    state.actions.role = async (b) => {
      const r = window.prompt('Role: owner, manager, staff (front desk), cashier, cook or driver. Type "remove" to take away access.', b.dataset.role); if (!r) return;
      const remove = r.trim().toLowerCase() === 'remove';
      await rpc('update_staff', { p_user: b.dataset.id, p_role: remove ? b.dataset.role : r.trim().toLowerCase(), p_active: !remove }); toast('Updated'); PMB.render();
    };
  }));
})();
