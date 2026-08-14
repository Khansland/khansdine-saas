@extends('layouts.site')
@section('title', __('saas.apply.title'))
@section('body')
  <h1>{{ __('saas.apply.title') }}</h1>
  <p class="lede">{{ __('saas.apply.lede') }}</p>

  @if($errors->any())
    <div class="err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
  @endif

  <form method="POST" action="{{ route('apply.submit') }}" class="card">
    @csrf
    {{-- The honeypot. Named like a field a bot wants to fill, hidden from a
         person, and never shown to a screen reader either (aria-hidden +
         tabindex -1), so nobody legitimate can trip it by accident. --}}
    <div class="hp" aria-hidden="true">
      {{-- No label text: nobody sees this field, so an English word here would
           be the only untranslated string on an otherwise Bengali page, and it
           would be there for a bot's benefit rather than a reader's. --}}
      <input type="text" id="company_website" name="company_website" tabindex="-1"
             autocomplete="off" aria-hidden="true">
    </div>

    <label for="phone">{{ __('saas.apply.phone') }} *</label>
    <input type="tel" id="phone" name="phone" required inputmode="tel"
           placeholder="017XXXXXXXX" value="{{ old('phone') }}">
    <div class="muted">{{ __('saas.apply.phone_note') }}</div>

    <label for="farm_name">{{ __('saas.apply.farm_name') }} *</label>
    <input type="text" id="farm_name" name="farm_name" required value="{{ old('farm_name') }}">

    <label for="owner_name">{{ __('saas.apply.owner_name') }} *</label>
    <input type="text" id="owner_name" name="owner_name" required value="{{ old('owner_name') }}">

    <label for="district">{{ __('saas.apply.district') }}</label>
    <input type="text" id="district" name="district" value="{{ old('district') }}">

    <label for="pond_count">{{ __('saas.apply.pond_count') }}</label>
    <input type="number" id="pond_count" name="pond_count" min="0" max="10000" value="{{ old('pond_count') }}">

    <label for="species">{{ __('saas.apply.species') }}</label>
    <input type="text" id="species" name="species" placeholder="{{ __('saas.apply.species_ph') }}"
           value="{{ old('species') }}">

    <label>{{ __('saas.apply.bundles') }}</label>
    @foreach($bundles as $b)
      <label style="font-weight:400;display:flex;gap:.5rem;align-items:flex-start;margin:.3rem 0">
        <input type="checkbox" name="bundles[]" value="{{ $b }}" style="width:auto;min-height:0;margin-top:.35rem"
               @checked(in_array($b, (array) old('bundles', []), true))>
        <span><strong>{{ __("saas.bundle.$b.title") }}</strong>
          <span class="muted">— {{ __("saas.bundle.$b.body") }}</span></span>
      </label>
    @endforeach

    <label for="note">{{ __('saas.apply.note') }}</label>
    <textarea id="note" name="note">{{ old('note') }}</textarea>

    <p style="margin-top:1rem"><button class="btn" type="submit">{{ __('saas.apply.submit') }}</button></p>
    <p class="muted">{{ __('saas.apply.privacy') }}</p>
  </form>
@endsection
