@extends('layouts.site')
@section('body')
  <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-bottom:1rem">
    <strong>{{ __('saas.console.title') }}</strong>
    <nav style="display:flex;gap:.9rem;font-size:.9rem">
      <a href="{{ route('console.tenants') }}">{{ __('saas.console.tenants') }}</a>
      <a href="{{ route('console.applications') }}">{{ __('saas.console.applications') }}</a>
      <a href="{{ route('console.audit') }}">{{ __('saas.console.audit') }}</a>
    </nav>
    <form method="POST" action="{{ route('console.logout') }}" style="margin-left:auto">
      @csrf<button class="btn ghost" style="min-height:34px;padding:.25rem .7rem">{{ __('saas.console.logout') }}</button>
    </form>
  </div>
  @if(session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
  @yield('console')
@endsection
