@extends('layouts.app')
@section('content')
<section class="auth-panel card">
    <h1>Register</h1>
    <form method="post" action="{{ route('register') }}" class="stack">@csrf
        <label>Name<input name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name"></label>
        <label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
        <label>Password<input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
        <label>Confirm password<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <button class="button" type="submit">Create account</button>
    </form>
</section>
@endsection
