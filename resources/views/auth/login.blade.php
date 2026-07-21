@extends('layouts.app')
@section('content')
<section class="auth-panel card">
    <h1>Login</h1>
    <form method="post" action="{{ route('login') }}" class="stack">@csrf
        <label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
        <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
        <label class="check"><input type="checkbox" name="remember" value="1"> ログイン状態を保持</label>
        <button class="button" type="submit">Login</button>
    </form>
</section>
@endsection
