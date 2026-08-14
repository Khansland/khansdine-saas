<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', __('saas.meta.title'))</title>
<meta name="description" content="{{ __('saas.meta.description') }}">
{{-- C.4: this page SHOULD be found. The demo carries noindex; this one must not. --}}
<meta name="robots" content="index, follow">
<style>
:root{--ink:#0f172a;--muted:#64748b;--line:#e2e8f0;--brand:#0f766e;--brandink:#115e59;--bg:#f8fafc}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);
  font:16px/1.6 system-ui,-apple-system,'Segoe UI',Roboto,'Noto Sans Bengali',sans-serif}
a{color:var(--brand)}
.wrap{max-width:920px;margin:0 auto;padding:0 1rem}
header{background:#fff;border-bottom:1px solid var(--line)}
header .wrap{display:flex;align-items:center;gap:1rem;height:60px}
header .brand{text-decoration:none;color:var(--ink);display:flex;flex-direction:column;line-height:1.15}
.brand-name{font-weight:700;font-size:1.02rem}
.brand-product{font-size:.7rem;color:var(--muted);font-weight:600;letter-spacing:.01em}
header nav{margin-left:auto;display:flex;gap:.75rem;align-items:center;font-size:.9rem}
.lang a{padding:.15rem .45rem;border-radius:4px;text-decoration:none;color:var(--muted);font-size:.8rem}
.lang a.on{background:var(--ink);color:#fff}
main{padding:2rem 0 3rem}
h1{font-size:1.9rem;line-height:1.25;margin:0 0 .6rem}
h2{font-size:1.2rem;margin:2rem 0 .5rem}
.lede{color:var(--muted);font-size:1.05rem;margin:0 0 1.5rem}
.card{background:#fff;border:1px solid var(--line);border-radius:12px;padding:1.1rem;margin:.6rem 0}
.grid{display:grid;gap:.7rem;grid-template-columns:repeat(auto-fit,minmax(210px,1fr))}
.btn{display:inline-block;background:var(--brand);color:#fff;text-decoration:none;border:0;
  border-radius:8px;padding:.7rem 1.15rem;font:inherit;font-weight:600;cursor:pointer;min-height:44px}
.btn:hover{background:var(--brandink)}
.btn.ghost{background:#fff;color:var(--brand);border:1px solid var(--brand)}
label{display:block;font-size:.85rem;font-weight:600;margin:.8rem 0 .25rem}
input[type=text],input[type=tel],input[type=email],input[type=number],input[type=password],textarea,select{
  width:100%;padding:.65rem .7rem;border:1px solid #cbd5e1;border-radius:8px;font:inherit;min-height:44px;background:#fff}
textarea{min-height:110px}
.hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
.err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;padding:.7rem;margin:.6rem 0}
.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;border-radius:8px;padding:.7rem;margin:.6rem 0}
.muted{color:var(--muted);font-size:.88rem}
footer{border-top:1px solid var(--line);background:#fff;padding:1.2rem 0;color:var(--muted);font-size:.85rem}
table{width:100%;border-collapse:collapse;font-size:.9rem}
th,td{text-align:left;padding:.5rem .55rem;border-bottom:1px solid var(--line);vertical-align:top}
th{color:var(--muted);font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.03em}
code,pre{font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
pre{background:var(--ink);color:#e2e8f0;padding:.9rem;border-radius:8px;overflow-x:auto;font-size:.82rem}
.pill{display:inline-block;padding:.1rem .5rem;border-radius:999px;font-size:.75rem;font-weight:600}
.pill.active{background:#dcfce7;color:#166534}.pill.suspended{background:#fee2e2;color:#991b1b}
.pill.provisioning{background:#fef3c7;color:#92400e}.pill.new{background:#dbeafe;color:#1e40af}
.pill.contacted{background:#e0e7ff;color:#3730a3}.pill.approved{background:#dcfce7;color:#166534}
.pill.rejected{background:#f1f5f9;color:#475569}.pill.provisioned{background:#ccfbf1;color:#115e59}
.scroll{overflow-x:auto}
/* The backup column. Four verdicts and a never-collected, each a different
   colour, because the whole point is that one of them has to be noticed at a
   glance on a night when nobody is looking for it. */
.bk{display:inline-block;padding:.12rem .5rem;border-radius:4px;font-size:.75rem;font-weight:700}
.bk-ok{background:#dcfce7;color:#166534}
.bk-stale{background:#fef3c7;color:#92400e;border:1px solid #f59e0b}
.bk-none{background:#fee2e2;color:#991b1b;border:1px solid #ef4444}
.bk-unknown{background:#e2e8f0;color:#475569;border:1px dashed #94a3b8}
.bk-detail{font-size:.74rem;color:var(--muted);margin-top:.15rem}
</style>
</head>
<body>
<header><div class="wrap">
  <a class="brand" href="{{ route('home') }}">
    <span class="brand-name notranslate" translate="no">{{ __('saas.brand.name') }}</span>
    <span class="brand-product">{{ __('saas.brand.product') }}</span>
  </a>
  <nav>
    <a href="{{ route('apply') }}">{{ __('saas.nav.apply') }}</a>
    <a href="{{ config('subdomain.demo_url') }}">{{ __('saas.nav.demo') }}</a>
    <span class="lang">
      @foreach(config('subdomain.locales') as $code => $label)
        <a href="/lang/{{ $code }}" class="{{ app()->getLocale() === $code ? 'on' : '' }}">{{ $label }}</a>
      @endforeach
    </span>
  </nav>
</div></header>
<main><div class="wrap">@yield('body')</div></main>
<footer><div class="wrap">
  &copy; {{ date('Y') }}
  <span class="notranslate" translate="no">{{ __('saas.brand.name') }}</span>
  &middot; {{ __('saas.brand.product') }} &middot; {{ config('subdomain.phone') }}
  @auth('console') &middot; <a href="{{ route('console.tenants') }}">{{ __('saas.nav.console') }}</a>@endauth
</div></footer>
</body>
</html>
