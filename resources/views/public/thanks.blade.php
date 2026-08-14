@extends('layouts.site')
@section('title', __('saas.thanks.title'))
@section('body')
  <h1>{{ __('saas.thanks.title') }}</h1>
  <div class="ok">{{ __('saas.thanks.body') }}</div>
  <p class="muted">{{ __('saas.thanks.no_email') }}</p>
  <p>
    <a class="btn ghost" href="{{ config('subdomain.demo_url') }}">{{ __('saas.thanks.meanwhile') }}</a>
    <a href="{{ route('home') }}">{{ __('saas.thanks.back') }}</a>
  </p>
@endsection
