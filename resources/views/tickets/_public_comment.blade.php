@php
    $depth = (int) ($depth ?? 0);
    $canReplyToComment = $commentThreadingEnabled && $canCommentPublic && $depth < 2;
@endphp

<article id="comment-{{ $comment->id }}" class="comment-card {{ $depth > 0 ? 'reply-card reply-card-depth-'.$depth : '' }} {{ in_array((int) $comment->id, $unreadPublicCommentIds ?? [], true) ? 'is-unread-activity' : '' }}">
    <div class="comment-head">
        <div class="comment-author">{{ $comment->user?->displayName() ?? __('tickets.common.unknown_user') }}</div>
        <div class="comment-time">{{ $comment->created_at?->locale($locale)->translatedFormat($dateTimeFormat) ?? __('tickets.common.not_available') }}</div>
    </div>
    <div class="comment-body">
        {!! nl2br(e($comment->body)) !!}
    </div>

    @include('tickets._attachments', [
        'attachments' => $comment->attachments,
        'canDelete' => $canDeleteAttachments,
    ])

    @if ($canReplyToComment)
        <div class="comment-actions">
            <button
                class="comment-link ticket-detail-action button-compact"
                type="button"
                data-editor-toggle="reply-editor-{{ $comment->id }}"
                aria-controls="reply-editor-{{ $comment->id }}"
                aria-expanded="false"
            >
                <span class="ticket-detail-action-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 10l-5 5 5 5"></path>
                        <path d="M4 15h10a6 6 0 0 0 6-6V5"></path>
                    </svg>
                </span>
                <span>{{ __('tickets.show.comments.reply') }}</span>
            </button>
        </div>

        <form
            id="reply-editor-{{ $comment->id }}"
            class="comment-form reply-form"
            data-editor-panel
            method="post"
            action="{{ route('tickets.comments.store', $ticket) }}"
            enctype="multipart/form-data"
            hidden
        >
            @csrf
            <input type="hidden" name="parent_id" value="{{ $comment->id }}">

            <div class="comment-form-head">
                <h3>{{ __('tickets.show.comments.reply_heading') }}</h3>
                <p>{{ __('tickets.show.comments.reply_subheading') }}</p>
            </div>

            @if ($replyParentId === (string) $comment->id && $commentErrors->any())
                <ul class="field-error-list">
                    @foreach ($commentErrors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <div>
                <textarea class="textarea" name="body" required>{{ $replyParentId === (string) $comment->id ? old('body') : '' }}</textarea>
                @if ($replyParentId === (string) $comment->id && $commentErrors->has('body'))
                    <div class="field-error">{{ $commentErrors->first('body') }}</div>
                @endif
                @if ($replyParentId === (string) $comment->id && $commentErrors->has('parent_id'))
                    <div class="field-error">{{ $commentErrors->first('parent_id') }}</div>
                @endif
            </div>

            <div>
                @include('tickets._attachment_input', [
                    'id' => 'reply-attachments-'.$comment->id,
                    'errors' => $commentErrors,
                    'showErrors' => $replyParentId === (string) $comment->id,
                ])
            </div>

            <div class="comment-form-actions">
                <button class="button button-primary ticket-detail-action" type="submit">
                    <span class="ticket-detail-action-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12.5l4.2 4.2L19 6.8"></path>
                            <path d="M19 13v5a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h7"></path>
                        </svg>
                    </span>
                    <span>{{ __('tickets.show.comments.reply_submit') }}</span>
                </button>
                <button class="button button-secondary ticket-detail-action" type="button" data-editor-cancel="reply-editor-{{ $comment->id }}">
                    <span class="ticket-detail-action-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 6l12 12"></path>
                            <path d="M18 6L6 18"></path>
                        </svg>
                    </span>
                    <span>{{ __('tickets.common.close') }}</span>
                </button>
            </div>
        </form>
    @endif

    @if ($depth < 2 && $comment->publicReplies->isNotEmpty())
        <div class="comment-children comment-children-depth-{{ $depth + 1 }}" aria-label="{{ __('tickets.show.comments.children_label') }}">
            @foreach ($comment->publicReplies as $reply)
                @include('tickets._public_comment', ['comment' => $reply, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</article>
