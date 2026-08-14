@extends('layouts.console')
@section('title', __('saas.console.audit'))
@section('console')
  <h1>{{ __('saas.console.audit') }}</h1>
  <p class="muted">{{ __('saas.audit.lede') }}</p>
  <div class="card scroll">
    <table>
      <thead><tr><th>{{ __('saas.a.when') }}</th><th>{{ __('saas.audit.actor') }}</th>
        <th>{{ __('saas.audit.action') }}</th><th>{{ __('saas.audit.subject') }}</th>
        <th>{{ __('saas.audit.detail') }}</th></tr></thead>
      <tbody>
      @forelse($events as $e)
        <tr>
          <td class="muted">{{ $e->created_at->translatedFormat('d M H:i') }}</td>
          <td>{{ $e->actor }}</td>
          <td>{{ $e->action }}</td>
          <td class="muted">{{ $e->subject_type ? $e->subject_type . ' ' . $e->subject_id : '-' }}</td>
          <td class="muted">{{ $e->detail ? json_encode($e->detail, JSON_UNESCAPED_UNICODE) : '-' }}</td>
        </tr>
      @empty<tr><td colspan="5" class="muted">{{ __('saas.t.no_events') }}</td></tr>@endforelse
      </tbody>
    </table>
  </div>
  {{ $events->links() }}
@endsection
