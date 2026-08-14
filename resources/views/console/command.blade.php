@extends('layouts.console')
@section('title', __("saas.verb.$verb"))
@section('console')
  <h1>{{ __("saas.verb.$verb") }} &mdash; {{ $tenant->subdomain }}</h1>

  @if($destructive)
    <div class="err"><strong>{{ __('saas.cmd.destructive') }}</strong></div>
  @endif

  <p class="lede">{{ __('saas.cmd.lede') }}</p>

  <div class="card">
    <pre>{{ $line }}</pre>
    <p class="muted">{{ __('saas.cmd.why') }}</p>
  </div>

  @if($guards)
    <h2>{{ __('saas.cmd.guards') }}</h2>
    <div class="card"><ul>@foreach($guards as $g)<li>{{ $g }}</li>@endforeach</ul>
      <p class="muted">{{ __('saas.cmd.guards_note') }}</p></div>
  @endif

  <p><a href="{{ route('console.tenants.show', $tenant->subdomain) }}">{{ __('saas.cmd.back') }}</a></p>
@endsection
