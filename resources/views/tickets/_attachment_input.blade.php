@props([
    'id',
    'errors' => null,
    'showErrors' => true,
])

@php
    $maxFiles = (int) config('helpdesk.attachments.max_files', 10);
    $maxSizeMb = (int) config('helpdesk.attachments.max_size_mb', 20);
    $extensions = array_values((array) config('helpdesk.attachments.allowed_extensions', []));
    $accept = collect($extensions)
        ->map(fn ($extension) => '.'.ltrim((string) $extension, '.'))
        ->implode(',');
@endphp

<label class="label" for="{{ $id }}">{{ __('tickets.attachments.form_label') }}</label>
<input
    class="input file-input"
    id="{{ $id }}"
    name="attachments[]"
    type="file"
    multiple
    accept="{{ $accept }}"
    data-attachment-input
    data-max-files="{{ $maxFiles }}"
    data-max-size="{{ $maxSizeMb * 1024 * 1024 }}"
    data-allowed-extensions="{{ implode(',', $extensions) }}"
    data-label-remove="{{ __('tickets.attachments.queue_remove') }}"
    data-label-too-many="{{ __('tickets.attachments.queue_too_many', ['max' => $maxFiles]) }}"
    data-label-too-large="{{ __('tickets.attachments.queue_too_large', ['max' => $maxSizeMb]) }}"
    data-label-type="{{ __('tickets.attachments.queue_type_not_allowed') }}"
    data-label-empty="{{ __('tickets.attachments.queue_empty') }}"
>
<div class="hint">{{ __('tickets.attachments.form_hint', ['max' => $maxSizeMb]) }}</div>
<div class="attachment-queue" data-attachment-queue aria-live="polite"></div>
@if ($showErrors && $errors && ($errors->has('attachments') || $errors->has('attachments.*')))
    <div class="field-error">{{ $errors->first('attachments') ?: $errors->first('attachments.*') }}</div>
@endif
