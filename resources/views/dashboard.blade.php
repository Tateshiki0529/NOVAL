@extends('layouts.app')
@section('content')
<div class="page-heading"><div><p class="eyebrow">Core MVP workspace</p><h1>{{ auth()->user()->name }}'s archive</h1></div><p>すべてのデータはprivateです。</p></div>

<section class="workspace-grid">
<article class="card span-2">
    <h2>1. Protocol Draft</h2>
    <p class="muted">Draftは不完全でも保存できます。Publish時にSchema安全検査を行います。</p>
    <form method="post" action="{{ route('protocols.store') }}" class="stack">@csrf
        <div class="grid two"><label>Slug<input name="slug" value="{{ old('slug', 'fuel-log') }}" required></label><label>Version<input name="version" value="{{ old('version', '1.0.0') }}" required></label></div>
        <label>JSON Schema<textarea name="schema" rows="15" required>{{ old('schema', json_encode(['$schema'=>'https://json-schema.org/draft/2020-12/schema','type'=>'object','properties'=>['mileage'=>['type'=>'integer','minimum'=>0],'fullTank'=>['type'=>'boolean']],'required'=>['mileage'],'additionalProperties'=>false], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)) }}</textarea></label>
        <label>Metadata<textarea name="metadata" rows="10" required>{{ old('metadata', json_encode(['order'=>['/mileage','/fullTank'],'fields'=>['/mileage'=>['kind'=>'integer','label'=>'走行距離'],'/fullTank'=>['kind'=>'boolean','label'=>'満タン']]], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) }}</textarea></label>
        <button class="button" type="submit">Draftを保存</button>
    </form>
    <div class="item-list">
    @forelse($protocols as $protocol)
        <div class="item"><strong>{{ $protocol->slug }}</strong>@foreach($protocol->versions as $version)<span class="pill {{ $version->state }}">{{ $version->version }} · {{ $version->state }}</span>@if($version->state==='draft')<form method="post" action="{{ route('protocols.publish', $version) }}">@csrf<button class="small-button">Publish</button></form>@endif @endforeach</div>
    @empty<p class="muted">Protocolはまだありません。</p>@endforelse
    </div>
</article>

<article class="card">
    <h2>2. Category</h2>
    <form method="post" action="{{ route('categories.store') }}" class="stack">@csrf
        <label>Name<input name="name" required maxlength="120"></label>
        <label>Description<textarea name="description" rows="3"></textarea></label>
        <button class="button" type="submit">作成</button>
    </form>
    <ul class="plain-list">@foreach($categories as $category)<li>{{ $category->name }}</li>@endforeach</ul>
</article>

<article class="card">
    <h2>3. LogBook</h2>
    <form method="post" action="{{ route('logbooks.store') }}" class="stack">@csrf
        <label>Name<input name="name" required maxlength="120"></label>
        <label>Category<select name="categoryId"><option value="">なし</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></label>
        <label>Published Protocol<select name="protocolVersionId" required><option value="">選択</option>@foreach($protocols as $protocol)@foreach($protocol->versions->where('state','published') as $version)<option value="{{ $version->id }}">{{ $protocol->slug }} {{ $version->version }}</option>@endforeach @endforeach</select></label>
        <label>Description<textarea name="description" rows="3"></textarea></label>
        <button class="button" type="submit">作成</button>
    </form>
</article>

@foreach($logbooks as $logbook)
<article class="card">
    <p class="eyebrow">Protocol Record</p><h2>{{ $logbook->name }}</h2>
    <p class="muted">{{ $logbook->category?->name ?? 'Categoryなし' }} · {{ $logbook->currentProtocolVersion->version }}</p>
    <form method="post" action="{{ route('records.store', $logbook) }}" class="stack">@csrf
        <input type="hidden" name="protocolVersionId" value="{{ $logbook->current_protocol_version_id }}">
        <label>Occurred at<input type="datetime-local" name="occurredAt" value="{{ now()->format('Y-m-d\TH:i') }}" required></label>
        <label>Payload<textarea name="payload" rows="7" required>{}</textarea></label>
        <button class="button" type="submit">Recordを保存</button>
    </form>
    <div class="item-list">
    @foreach($logbook->records as $storedRecord)
        @php($revision = $storedRecord->currentRevision)
        @if($revision && $revision->operation !== 'delete')
        <details class="item block"><summary>Revision {{ $revision->revision_number }} · {{ $revision->occurred_at }}</summary>
            <form method="post" action="{{ route('records.update', [$logbook, $storedRecord]) }}" class="stack compact">@csrf @method('PATCH')
                <input type="hidden" name="baseRevisionId" value="{{ $revision->id }}">
                <label>Occurred at<input type="datetime-local" name="occurredAt" value="{{ $revision->occurred_at->format('Y-m-d\TH:i') }}" required></label>
                <label>Payload<textarea name="payload" rows="6" required>{{ json_encode($revision->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</textarea></label>
                <button class="small-button" type="submit">新しいRevisionを保存</button>
            </form>
            <form method="post" action="{{ route('records.destroy', [$logbook, $storedRecord]) }}" class="danger-form">@csrf @method('DELETE')<input type="hidden" name="baseRevisionId" value="{{ $revision->id }}"><button class="danger-button" type="submit">論理削除</button></form>
        </details>
        @endif
    @endforeach
    </div>
</article>
@endforeach

<article class="card span-2">
    <p class="eyebrow">Protocol不要</p><h2>Open Record</h2>
    <form method="post" action="{{ route('open-records.store') }}" class="stack">@csrf
        <div class="grid two"><label>Title<input name="title" maxlength="200"></label><label>Category<select name="categoryId"><option value="">なし</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></label></div>
        <label>Body<textarea name="body" rows="8" required maxlength="65536"></textarea></label>
        <div class="grid two"><label>Tags（カンマ区切り）<input name="tags"></label><label>Occurred at<input type="datetime-local" name="occurredAt" value="{{ now()->format('Y-m-d\TH:i') }}" required></label></div>
        <button class="button" type="submit">Open Recordを保存</button>
    </form>
    <div class="item-list">@foreach($openRecords as $record)@php($revision=$record->currentRevision)@if($revision && $revision->operation !== 'delete')<details class="item block"><summary><strong>{{ $revision->title ?? '無題' }}</strong></summary><p>{{ $revision->body }}</p>
        <form method="post" action="{{ route('open-records.update', $record) }}" class="stack compact">@csrf @method('PATCH')
            <input type="hidden" name="baseRevisionId" value="{{ $revision->id }}">
            <label>Title<input name="title" value="{{ $revision->title }}" maxlength="200"></label>
            <label>Body<textarea name="body" rows="6" required>{{ $revision->body }}</textarea></label>
            <label>Tags<input name="tags" value="{{ implode(', ', $revision->tags ?? []) }}"></label>
            <input type="hidden" name="categoryId" value="{{ $record->category_id }}">
            <input type="hidden" name="occurredAt" value="{{ $revision->occurred_at->format(DATE_RFC3339) }}">
            <button class="small-button" type="submit">新しいRevisionを保存</button>
        </form>
        <form method="post" action="{{ route('open-records.destroy', $record) }}" class="danger-form">@csrf @method('DELETE')<input type="hidden" name="baseRevisionId" value="{{ $revision->id }}"><button class="danger-button" type="submit">論理削除</button></form>
    </details>@endif @endforeach</div>
</article>
</section>
@endsection
