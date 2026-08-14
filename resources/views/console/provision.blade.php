@extends('layouts.console')
@section('title', __('saas.p.title'))
@section('console')
  <h1>{{ __('saas.p.title') }}</h1>
  <p class="lede">{{ __('saas.p.lede', ['farm' => $a->farm_name]) }}</p>

  @if($errors->any())<div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

  <form method="POST" action="{{ route('console.applications.provision.command', $a->id) }}" class="card">
    @csrf
    <label for="subdomain">{{ __('saas.p.subdomain') }} *</label>
    <input type="text" id="subdomain" name="subdomain" required value="{{ old('subdomain', $suggested) }}">
    <div class="muted">{{ __('saas.p.subdomain_note') }}</div>

    <label for="business_name">{{ __('saas.p.business_name') }}</label>
    <input type="text" id="business_name" name="business_name" value="{{ old('business_name', $a->farm_name) }}">

    <label for="admin_email">{{ __('saas.p.admin_email') }}</label>
    <input type="email" id="admin_email" name="admin_email" value="{{ old('admin_email') }}">
    <div class="muted">{{ __('saas.p.admin_email_note') }}</div>

    <p style="margin-top:1rem"><button class="btn">{{ __('saas.p.build') }}</button></p>
  </form>

  @if($line)
    <h2>{{ __('saas.p.run_this') }}</h2>
    <div class="card">
      <pre>{{ $line }}</pre>
      <p class="muted">{{ __('saas.p.run_note') }}</p>
    </div>
  @endif

  <p><a href="{{ route('console.applications.show', $a->id) }}">{{ __('saas.cmd.back') }}</a></p>
@endsection
