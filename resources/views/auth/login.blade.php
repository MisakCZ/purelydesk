@extends('layouts.admin')

@section('title', __('auth.login.page_title'))

@push('styles')
    <style>
        .login-wrap {
            max-width: 28rem;
            margin: 0 auto;
        }

        .login-card {
            padding: 1.25rem;
            border: 1px solid var(--line);
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        }

        .login-form {
            display: grid;
            gap: 1rem;
        }

        .demo-login-note {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border: 1px solid var(--line);
            border-radius: 0.75rem;
            background: var(--panel-muted);
            color: var(--muted);
            font-size: 0.8125rem;
            line-height: 1.45;
        }

        .demo-login-note strong {
            color: var(--text);
        }

        .demo-login-note ul {
            margin: 0.5rem 0 0;
            padding-left: 1rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-head">
        <div class="page-head-bar">
            <div>
                <h2>{{ __('auth.login.title') }}</h2>
                <p>{{ __('auth.login.subtitle') }}</p>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="login-wrap">
            <div class="login-card">
                @if ($demoLoginEnabled ?? false)
                    <div class="demo-login-note">
                        <strong>{{ __('auth.login.demo.title') }}</strong>
                        <p>{{ __('auth.login.demo.description') }}</p>
                        <ul>
                            @foreach (__('auth.login.demo.accounts') as $account)
                                <li>{{ $account }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="login-form" method="post" action="{{ route('login.store') }}">
                    @csrf

                    @if ($errors->any())
                        <ul class="error-list" role="alert">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="form-field">
                        <label class="form-label" for="username">{{ __('auth.login.username') }}</label>
                        <input
                            id="username"
                            class="form-input"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="password">{{ __('auth.login.password') }}</label>
                        <input
                            id="password"
                            class="form-input"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <div class="form-actions">
                        <button class="button button-primary" type="submit">{{ __('auth.login.submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
