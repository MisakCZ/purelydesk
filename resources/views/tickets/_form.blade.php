@php
    $ticket ??= null;
    $subjectValue = old('subject', $ticket?->subject);
    $priorityValue = (string) old('priority_id', $ticket?->ticket_priority_id);
    $categoryValue = (string) old('category_id', $ticket?->ticket_category_id);
    $descriptionValue = old('description', $ticket?->description);
    $visibilityValue = (string) old('visibility', $ticket?->visibility ?? \App\Models\Ticket::VISIBILITY_PUBLIC);
    $pinnedValue = old('pinned', $ticket?->is_pinned ? '1' : null);
@endphp

<div class="form-grid">
    <div class="field field-full">
        <label class="label" for="subject">Předmět</label>
        <input
            class="input"
            id="subject"
            name="subject"
            type="text"
            value="{{ $subjectValue }}"
            maxlength="255"
            required
        >
        <div class="hint">Stručný název ticketu pro seznam a orientaci.</div>
        @if ($viewErrors->has('subject'))
            <div class="field-error">{{ $viewErrors->first('subject') }}</div>
        @endif
    </div>

    <div class="field">
        <label class="label" for="priority_id">Priorita</label>
        <select class="select" id="priority_id" name="priority_id" required>
            <option value="">Vyberte prioritu</option>
            @foreach ($priorities as $priority)
                <option value="{{ $priority->id }}" @selected($priorityValue === (string) $priority->id)>
                    {{ $priority->name }}
                </option>
            @endforeach
        </select>
        @if ($viewErrors->has('priority_id'))
            <div class="field-error">{{ $viewErrors->first('priority_id') }}</div>
        @endif
    </div>

    <div class="field">
        <label class="label" for="category_id">Kategorie</label>
        <select class="select" id="category_id" name="category_id" required>
            <option value="">Vyberte kategorii</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected($categoryValue === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @if ($viewErrors->has('category_id'))
            <div class="field-error">{{ $viewErrors->first('category_id') }}</div>
        @endif
    </div>

    @if ($ticket && $canManageVisibility)
        <div class="field">
            <label class="label" for="visibility">Viditelnost</label>
            <select class="select" id="visibility" name="visibility" required>
                @foreach ($visibilityOptions as $value => $label)
                    <option value="{{ $value }}" @selected($visibilityValue === (string) $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <div class="hint">Internal ticket vidí requester, solveři a admin. Private ticket vidí requester, assignee a admin.</div>
            @if ($viewErrors->has('visibility'))
                <div class="field-error">{{ $viewErrors->first('visibility') }}</div>
            @endif
        </div>
    @endif

    <div class="field field-full">
        <label class="label" for="description">Popis</label>
        <textarea class="textarea" id="description" name="description" required>{{ $descriptionValue }}</textarea>
        <div class="hint">Detailnější popis problému nebo požadavku.</div>
        @if ($viewErrors->has('description'))
            <div class="field-error">{{ $viewErrors->first('description') }}</div>
        @endif
    </div>

    @if ($canManagePin)
        <div class="field field-full">
            <label class="checkbox-field" for="pinned">
                <input id="pinned" name="pinned" type="checkbox" value="1" @checked((string) $pinnedValue === '1')>
                Připnout ticket
            </label>
            @if ($pinningEnabled)
                <div class="hint">Připnutý ticket se zobrazí i v horním bloku připnutých ticketů.</div>
            @else
                <div class="hint">Připnutí bude funkční po spuštění databázové migrace pro pinning ticketů.</div>
            @endif
            @if ($viewErrors->has('pinned'))
                <div class="field-error">{{ $viewErrors->first('pinned') }}</div>
            @endif
        </div>
    @endif
</div>
