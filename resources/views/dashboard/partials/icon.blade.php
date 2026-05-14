@props(['name'])

@switch($name)
    @case('inbox')
        <svg class="dashboard-icon-svg" viewBox="0 0 48 48" fill="none" focusable="false" aria-hidden="true">
            <path d="M12 18.5 16.4 10h15.2l4.4 8.5" />
            <path d="M10 18.5h9.2l2.1 5h5.4l2.1-5H38v15A4.5 4.5 0 0 1 33.5 38h-19A4.5 4.5 0 0 1 10 33.5v-15Z" />
            <circle class="dashboard-icon-accent" cx="35.5" cy="11.5" r="4.2" />
        </svg>
        @break

    @case('user')
        <svg class="dashboard-icon-svg" viewBox="0 0 48 48" fill="none" focusable="false" aria-hidden="true">
            <circle cx="24" cy="15.5" r="6" />
            <path d="M13 37c1.4-7.2 5.2-10.8 11-10.8S33.6 29.8 35 37" />
        </svg>
        @break

    @case('clock')
        <svg class="dashboard-icon-svg" viewBox="0 0 48 48" fill="none" focusable="false" aria-hidden="true">
            <circle cx="24" cy="24" r="14" />
            <path d="M24 15.8V24l6.2 4.2" />
        </svg>
        @break

    @case('users')
        <svg class="dashboard-icon-svg" viewBox="0 0 48 48" fill="none" focusable="false" aria-hidden="true">
            <circle cx="20" cy="17" r="5.2" />
            <path d="M10.5 36c1.2-6.4 4.5-9.6 9.5-9.6s8.3 3.2 9.5 9.6" />
            <path d="M30.2 14.2a4.7 4.7 0 0 1 0 9.1" />
            <path d="M32.6 27.3c3 .9 5 3.8 5.9 8.7" />
        </svg>
        @break

    @case('calendar')
        <svg class="dashboard-icon-svg" viewBox="0 0 48 48" fill="none" focusable="false" aria-hidden="true">
            <path d="M15 10v6" />
            <path d="M33 10v6" />
            <rect x="10.5" y="14" width="27" height="24" rx="4" />
            <path d="M10.5 21.5h27" />
            <path d="M17 28h5" />
            <path d="M26 28h5" />
            <path d="M17 33h5" />
        </svg>
        @break

    @default
        <svg class="dashboard-icon-svg" viewBox="0 0 48 48" fill="none" focusable="false" aria-hidden="true">
            <circle cx="24" cy="24" r="14" />
        </svg>
@endswitch
