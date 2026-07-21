@extends('layouts.app')

@section('content')
<section class="hero">
    <p class="eyebrow">Record anything. Define everything.</p>
    <h1>なんでも残せる、定義できる。</h1>
    <p>NOVALはProtocolで構造を定義する記録と、すぐ書けるOpen Recordを、履歴を失わず保存するアーカイブです。</p>
    <div class="actions">
        @auth<a class="button" href="{{ route('dashboard') }}">Dashboardを開く</a>
        @else<a class="button" href="{{ route('register') }}">Core MVPを試す</a><a class="button secondary" href="{{ route('login') }}">Login</a>@endauth
    </div>
</section>
<section class="grid three">
    <article class="card"><h2>Define</h2><p>JSON Schema Draft 2020-12でFieldと制約を定義し、Publish後のVersionを固定します。</p></article>
    <article class="card"><h2>Record</h2><p>NormalizeとValidateを分け、定義外Fieldを保存しません。</p></article>
    <article class="card"><h2>Remember</h2><p>編集と削除を完全snapshotのRevisionとして残します。</p></article>
</section>
@endsection
