@php
    $announcement ??= null;
    $isEditing = $announcement !== null;
    $selectedType = old('type', $announcement?->type ?? \App\Models\Announcement::TYPE_INFO);
    $startsAtValue = old('starts_at', $announcement?->starts_at?->format('Y-m-d\TH:i'));
    $endsAtValue = old('ends_at', $announcement?->ends_at?->format('Y-m-d\TH:i'));
    $isActive = old('is_active', $announcement?->is_active ?? true);
@endphp

<div class="form-field">
    <label class="form-label" for="title">Title</label>
    <input class="form-input" id="title" name="title" type="text" value="{{ old('title', $announcement?->title) }}" required>
    @error('title')
        <div class="field-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-field">
    <label class="form-label" for="type">Typ</label>
    <select class="form-select" id="type" name="type" required>
        @foreach ($announcementTypes as $value => $label)
            <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('type')
        <div class="field-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-field">
    <label class="form-label" for="body">Body</label>
    <textarea class="form-textarea" id="body" name="body" required>{{ old('body', $announcement?->body) }}</textarea>
    @error('body')
        <div class="field-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-grid">
    <div class="form-field">
        <label class="form-label" for="starts_at">Aktivní od</label>
        <input class="form-input" id="starts_at" name="starts_at" type="datetime-local" value="{{ $startsAtValue }}">
        @error('starts_at')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-field">
        <label class="form-label" for="ends_at">Aktivní do</label>
        <input class="form-input" id="ends_at" name="ends_at" type="datetime-local" value="{{ $endsAtValue }}">
        @error('ends_at')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>
</div>

<label class="checkbox-field" for="is_active">
    <input id="is_active" name="is_active" type="checkbox" value="1" @checked((bool) $isActive)>
    Oznámení je aktivní
</label>

@if (! $isEditing)
    @error('author')
        <div class="field-error">{{ $message }}</div>
    @enderror
@endif
