<!doctype html>
<html lang="{{ $lang }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('doctitle')</title>
<meta name="description" content="@yield('docdesc')">
<meta name="robots" content="index, follow">
<style>
:root{--ink:#0f172a;--muted:#64748b;--line:#e2e8f0;--brand:#0f766e;--warn:#b45309;--bg:#fff}
*{box-sizing:border-box}
body{margin:0;background:#f1f5f9;color:var(--ink);
  font:16px/1.75 system-ui,-apple-system,'Segoe UI',Roboto,'Noto Sans Bengali','Hind Siliguri',sans-serif}
.sheet{max-width:820px;margin:0 auto;background:var(--bg);padding:2.2rem 2.4rem 3rem}
.top{border-bottom:3px solid var(--brand);padding-bottom:1rem;margin-bottom:1.6rem}
.brandname{font-weight:800;font-size:1.35rem;letter-spacing:-.01em}
.brandline{color:var(--muted);font-size:.9rem;font-weight:600}
h1{font-size:1.75rem;line-height:1.3;margin:1.4rem 0 .5rem}
h2{font-size:1.2rem;margin:2.2rem 0 .5rem;padding-top:.6rem;border-top:1px solid var(--line);color:var(--brand)}
h3{font-size:1rem;margin:1.3rem 0 .3rem}
p{margin:.6rem 0}
.lede{font-size:1.08rem;color:#334155}
ul,ol{margin:.5rem 0 .9rem;padding-left:1.3rem}
li{margin:.3rem 0}
.proof{background:#f8fafc;border-left:4px solid var(--brand);padding:.9rem 1.1rem;margin:1rem 0;border-radius:0 8px 8px 0}
.proof .n{font-weight:800;color:var(--brand);font-size:1.05rem}
.proof .src{display:block;color:var(--muted);font-size:.78rem;margin-top:.45rem}
.warnbox{background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:.9rem 1.1rem;margin:1rem 0}
.callout{background:#ecfdf5;border:1px solid #6ee7b7;border-radius:8px;padding:1rem 1.15rem;margin:1.3rem 0}
table{width:100%;border-collapse:collapse;margin:.8rem 0;font-size:.93rem}
th,td{text-align:left;padding:.5rem .55rem;border-bottom:1px solid var(--line);vertical-align:top}
th{color:var(--muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.03em}
.muted{color:var(--muted);font-size:.9rem}
.cta{background:var(--ink);color:#fff;border-radius:10px;padding:1.2rem 1.3rem;margin:1.6rem 0}
.cta a{color:#7dd3fc}
.cta .big{font-size:1.1rem;font-weight:700}
.foot{margin-top:2rem;padding-top:1rem;border-top:1px solid var(--line);color:var(--muted);font-size:.85rem}
.lang{text-align:right;font-size:.8rem;margin-bottom:.6rem}
.lang a{color:var(--muted);text-decoration:none;padding:.15rem .45rem;border-radius:4px}
.lang a.on{background:var(--ink);color:#fff}
.shot{border:1px dashed #94a3b8;background:#f8fafc;color:var(--muted);border-radius:8px;
  padding:1.4rem;text-align:center;font-size:.85rem;margin:.9rem 0}
.shotfig{margin:1.1rem 0}
.shotfig img{display:block;width:100%;max-width:100%;height:auto;border:1px solid var(--line);border-radius:8px}
.shotfig figcaption{color:var(--muted);font-size:.8rem;margin-top:.35rem}
@media print{
  body{background:#fff;font-size:11.5pt}
  .sheet{max-width:none;padding:0}
  .lang,.noprint{display:none}
  h2{break-after:avoid}
  .proof,.callout,.warnbox,table{break-inside:avoid}
  .shotfig{break-inside:avoid}
  .shotfig img{width:auto;max-width:100%;max-height:8.5cm;margin:0 auto;border-radius:0}
  a{color:inherit;text-decoration:none}
  .cta{background:#fff;color:var(--ink);border:2px solid var(--ink)}
  .cta a{color:var(--ink)}
}
</style>
</head>
<body>
<div class="sheet">
  <div class="lang noprint">
    @foreach(['bn' => 'বাংলা', 'en' => 'English'] as $code => $label)
      <a href="/lang/{{ $code }}" class="{{ $lang === $code ? 'on' : '' }}">{{ $label }}</a>
    @endforeach
  </div>
  <div class="top">
    <div class="brandname notranslate" translate="no">Khan's Systems</div>
    <div class="brandline">@yield('product')</div>
  </div>
  @yield('doc')
</div>
</body>
</html>
