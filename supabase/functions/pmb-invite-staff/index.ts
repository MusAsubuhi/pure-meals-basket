import "jsr:@supabase/functions-js/edge-runtime.d.ts";
import { createClient } from "jsr:@supabase/supabase-js@2";

// Owner-only: adds a person to the PMB team. Creates a login with a one-time temporary password
// (shown once to the owner), or reuses an existing login, then records the PMB role.
const ORIGINS = ["https://team.puremealsbasket.co.ke", "https://app.puremealsbasket.co.ke"];
const ROLES = ["owner", "manager", "staff", "cashier", "cook", "driver"];

function cors(req: Request) {
  const o = req.headers.get("origin") || "";
  return {
    "Access-Control-Allow-Origin": ORIGINS.includes(o) ? o : ORIGINS[0],
    "Access-Control-Allow-Headers": "authorization, apikey, content-type, x-client-info",
    "Access-Control-Allow-Methods": "POST, OPTIONS",
    "Vary": "Origin",
  };
}
const json = (req: Request, body: unknown, status = 200) =>
  new Response(JSON.stringify(body), { status, headers: { ...cors(req), "Content-Type": "application/json" } });

function tempPassword() {
  const chars = "ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789";
  const b = crypto.getRandomValues(new Uint8Array(12));
  return Array.from(b, (x) => chars[x % chars.length]).join("");
}

Deno.serve(async (req) => {
  if (req.method === "OPTIONS") return new Response("ok", { headers: cors(req) });
  if (req.method !== "POST") return json(req, { error: "Method not allowed" }, 405);

  const url = Deno.env.get("SUPABASE_URL")!;
  const svc = createClient(url, Deno.env.get("SUPABASE_SERVICE_ROLE_KEY")!, { auth: { persistSession: false } });

  const token = (req.headers.get("authorization") || "").replace(/^Bearer\s+/i, "");
  const { data: u } = await svc.auth.getUser(token);
  if (!u?.user) return json(req, { error: "Please sign in again." }, 401);

  const { data: me } = await svc.schema("pmb").from("staff").select("role,active").eq("user_id", u.user.id).maybeSingle();
  if (!me || !me.active || me.role !== "owner") return json(req, { error: "Only an owner can add team members." }, 403);

  let body: Record<string, unknown> = {};
  try { body = await req.json(); } catch { /* handled below */ }
  const email = String(body.email || "").trim().toLowerCase();
  const name = String(body.name || "").trim().slice(0, 100);
  const role = String(body.role || "");
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) return json(req, { error: "Enter a valid email address." }, 400);
  if (!name) return json(req, { error: "Enter the person's full name." }, 400);
  if (!ROLES.includes(role)) return json(req, { error: "Choose a role." }, 400);

  let userId: string | null = null;
  let password: string | null = null;
  let existing = false;

  const pw = tempPassword();
  const created = await svc.auth.admin.createUser({
    email, password: pw, email_confirm: true,
    user_metadata: { full_name: name, pmb_must_change_password: true },
  });
  if (created.data?.user) {
    userId = created.data.user.id; password = pw;
  } else {
    const msg = created.error?.message || "";
    if (!/already|registered|exists/i.test(msg)) return json(req, { error: "Could not create the login: " + msg }, 400);
    existing = true;
    for (let page = 1; page <= 20 && !userId; page++) {
      const { data } = await svc.auth.admin.listUsers({ page, perPage: 200 });
      const hit = data?.users?.find((x) => (x.email || "").toLowerCase() === email);
      if (hit) userId = hit.id;
      if (!data || data.users.length < 200) break;
    }
    if (!userId) return json(req, { error: "That email already has a login but it could not be found." }, 400);
  }

  const { error } = await svc.schema("pmb").from("staff").upsert({ user_id: userId, full_name: name, role, active: true }, { onConflict: "user_id" });
  if (error) return json(req, { error: "Login made but the team record failed: " + error.message }, 500);

  return json(req, { ok: true, existing, email, name, role, password });
});
