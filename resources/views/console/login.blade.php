@extends('layouts.site')
@section('title', __('saas.console.title'))
@section('body')
  <h1>{{ __('saas.console.title') }}</h1>
  <p class="lede">{{ __('saas.console.login_lede') }}</p>
  @if($errors->any())<div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
  <form method="POST" action="{{ route('console.login.post') }}" class="card" style="max-width:420px">
    @csrf
    <label for="email">{{ __('saas.console.email') }}</label>
    <input type="email" id="email" name="email" required autocomplete="username" value="{{ old('email') }}">
    <label for="password">{{ __('saas.console.password') }}</label>
    <input type="password" id="password" name="password" required autocomplete="current-password">
    <label style="font-weight:400;display:flex;gap:.5rem;align-items:center;margin-top:.7rem">
      <input type="checkbox" name="remember" value="1" style="width:auto;min-height:0"> {{ __('saas.console.remember') }}
    </label>
    <p style="margin-top:1rem"><button class="btn">{{ __('saas.console.sign_in') }}</button></p>
  </form>
@endsection
