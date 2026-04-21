@extends('layouts.admin')

@section('title', 'Upravit oznámení')

@push('styles')
    <style>
        .panel {
            padding: 1.25rem;
            border: 1px solid #e5ebf1;
            border-radius: 1rem;
            background: #fff;
        }

        .panel h3 {
            margin: 0;
            font-size: 1.05rem;
            color: #13202b;
        }

        .panel p {
            margin: 0.5rem 0 0;
            color: #5b6b79;
            line-height: 1.6;
        }

        .announcement-form {
            display: grid;
            gap: 1rem;
        }

        .form-field {
            display: grid;
            gap: 0.45rem;
        }

        .form-label {
            font-size: 0.92rem;
            font-weight: 600;
            color: #13202b;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            min-height: 2.9rem;
            padding: 0.8rem 0.95rem;
            border: 1px solid #cfd8e3;
            border-radius: 0.9rem;
            background: #fff;
            color: #13202b;
            font: inherit;
        }

        .form-textarea {
            min-height: 9rem;
            resize: vertical;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: 2px solid rgba(15, 118, 110, 0.16);
            border-color: #0f766e;
        }

        .form-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .checkbox-field {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            color: #13202b;
            font-weight: 600;
        }

        .field-error {
            color: #b91c1c;
            font-size: 0.9rem;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        @media (max-width: 720px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-head">
        <div class="page-head-bar">
            <div>
                <h2>Upravit oznámení</h2>
                <p>První verze interní administrace provozních oznámení.</p>
            </div>

            <a class="button button-secondary" href="{{ route('announcements.index') }}">Zpět na oznámení</a>
        </div>
    </div>

    <div class="page-body">
        <section class="panel" aria-label="Editace oznámení">
            <h3>{{ $announcement->title }}</h3>
            <p>Upravte obsah, aktivitu a časové okno oznámení.</p>

            <form class="announcement-form" method="post" action="{{ route('announcements.update', $announcement) }}" style="margin-top: 1rem;">
                @csrf
                @method('patch')

                @include('announcements._form', ['announcement' => $announcement, 'announcementTypes' => $announcementTypes])

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Uložit změny</button>
                    <a class="button button-secondary" href="{{ route('announcements.index') }}">Zrušit</a>
                </div>
            </form>
        </section>
    </div>
@endsection
