@props([
    'attachments',
    'canDelete' => false,
])

@if ($attachments->isNotEmpty())
    <div class="attachments-list">
        @foreach ($attachments as $attachment)
            <article class="attachment-item">
                @if ($attachment->isImage())
                    <a
                        class="attachment-thumb"
                        href="{{ route('ticket-attachments.download', $attachment) }}"
                        data-attachment-lightbox
                        data-preview-url="{{ route('ticket-attachments.preview', $attachment) }}"
                        data-download-url="{{ route('ticket-attachments.download', $attachment) }}"
                        data-title="{{ $attachment->original_name }}"
                    >
                        <img
                            src="{{ route('ticket-attachments.preview', $attachment) }}"
                            alt="{{ $attachment->original_name }}"
                            loading="lazy"
                        >
                    </a>
                @else
                    <span class="attachment-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                            <path d="M14 2v6h6"/>
                        </svg>
                    </span>
                @endif

                <div class="attachment-body">
                    <a class="attachment-name" href="{{ route('ticket-attachments.download', $attachment) }}">
                        {{ $attachment->original_name }}
                    </a>
                    <span class="attachment-meta">{{ $attachment->formattedSize() }}</span>
                </div>

                @if ($canDelete)
                    <form method="post" action="{{ route('ticket-attachments.destroy', $attachment) }}">
                        @csrf
                        @method('delete')
                        <button class="attachment-delete" type="submit">
                            {{ __('tickets.attachments.delete') }}
                        </button>
                    </form>
                @endif
            </article>
        @endforeach
    </div>
@endif
