@extends('layouts.site')
@section('title', __('saas.meta.title'))
@section('body')
  <h1>{{ __('saas.home.headline') }}</h1>
  <p class="lede">{{ __('saas.home.lede') }}</p>

  <p>
    <a class="btn" href="{{ route('apply') }}">{{ __('saas.home.cta_apply') }}</a>
    <a class="btn ghost" href="{{ config('subdomain.demo_url') }}">{{ __('saas.home.cta_demo') }}</a>
    <a class="btn ghost" href="{{ route('system') }}">{{ __('saas.home.cta_doc') }}</a>
  </p>
  <p class="muted">{{ __('saas.home.demo_note') }}</p>

  <h2>{{ __('saas.home.what_it_does') }}</h2>
  <div class="grid">
    @foreach(['ponds','feed','health','money'] as $k)
      <div class="card">
        <strong>{{ __("saas.home.does.$k.title") }}</strong>
        <div class="muted">{{ __("saas.home.does.$k.body") }}</div>
      </div>
    @endforeach
  </div>

  <h2>{{ __('saas.home.bundles') }}</h2>
  <div class="grid">
    @foreach($bundles as $b)
      <div class="card">
        <strong>{{ __("saas.bundle.$b.title") }}</strong>
        <div class="muted">{{ __("saas.bundle.$b.body") }}</div>
        {{-- No price. Habib has not set them, and a number invented here would
             be quoted back at him by a customer. --}}
        <div style="margin-top:.5rem"><a href="{{ route('apply') }}">{{ __('saas.bundle.price') }}</a></div>
      </div>
    @endforeach
  </div>
  <p class="muted">{{ __('saas.home.bands') }}</p>

  <h2>{{ __('saas.home.how') }}</h2>
  <ol class="muted">
    @foreach(['apply','talk','setup','use'] as $s)<li>{{ __("saas.home.step.$s") }}</li>@endforeach
  </ol>

  <p><a class="btn" href="{{ route('apply') }}">{{ __('saas.home.cta_apply') }}</a></p>
@endsection
