<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Catalogue') — Pure Meals Basket</title>
    <style>
        :root { --gold:#8A6D1D; --gold-light:#B9962E; --bg:#FAF7F0; --ink:#2D2A22; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; background:var(--bg); color:var(--ink); }
        .container { max-width:1100px; margin:0 auto; padding:2rem 1.25rem 4rem; }
        .topbar { background:#fff; border-bottom:3px solid var(--gold); padding:1rem 0; }
        .topbar-inner { max-width:1100px; margin:0 auto; padding:0 1.25rem; display:flex; justify-content:space-between; align-items:center; }
        .brand { font-weight:700; color:var(--gold); font-size:1.15rem; text-decoration:none; }
        h1 { margin:1.5rem 0 .5rem; font-size:1.75rem; }
        .subtitle { color:#7a7466; margin-bottom:1.5rem; }
        .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:1.25rem; }
        .card { background:#fff; border-radius:.75rem; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.08); display:block; text-decoration:none; color:inherit; transition:transform .15s; }
        .card:hover { transform:translateY(-2px); }
        .card-body { padding:1rem; }
        .price-tag { color:var(--gold); font-weight:600; margin-top:.35rem; }
        .badge { display:inline-block; font-size:.72rem; padding:.15rem .5rem; border-radius:999px; background:#F1E9D2; color:var(--gold); }
        .unavailable { color:#b0483a; font-size:.8rem; font-style:italic; }
        .btn-gold { display:inline-block; background:linear-gradient(135deg,var(--gold),var(--gold-light)); color:#fff; border:none; padding:.65rem 1.5rem; border-radius:.5rem; cursor:pointer; font-weight:600; }
        .quote-box { position:sticky; bottom:0; background:#fff; border-top:3px solid var(--gold); padding:1rem; box-shadow:0 -2px 10px rgba(0,0,0,.06); }
        label { font-size:.85rem; font-weight:500; display:block; margin-bottom:.25rem; }
        input[type=number], select { width:100%; padding:.55rem; border:1px solid #ddd; border-radius:.45rem; margin-bottom:.75rem; }
        .total-line { font-size:1.25rem; font-weight:700; color:var(--gold); }
        a.back { color:var(--gold); text-decoration:none; font-size:.9rem; }
    </style>
</head>
<body>
<div class="topbar">
    <div class="topbar-inner">
        <a href="{{ url('/') }}" class="brand">PMB · Pure Meals Basket</a>
        <a href="{{ route('catalogue.index') }}" class="back">Catalogue</a>
    </div>
</div>

@yield('content')
</body>
</html>
