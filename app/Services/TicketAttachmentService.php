<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketAttachmentService
{
    /**
     * @return array<string, array<int, string>>
     */
    public function validationRules(string $field = 'attachments'): array
    {
        return [
            $field => ['nullable', 'array', 'max:'.$this->maxFiles()],
            $field.'.*' => [
                'file',
                'max:'.$this->maxSizeKilobytes(),
                'mimes:'.implode(',', $this->allowedExtensions()),
                'mimetypes:'.implode(',', $this->allowedMimeTypes()),
            ],
        ];
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function storeMany(
        Ticket $ticket,
        array $files,
        User $uploader,
        ?TicketComment $comment = null,
        string $visibility = 'public',
    ): void {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $attachment = $this->store($ticket, $file, $uploader, $comment, $visibility);
            $this->recordHistory($ticket, $attachment, 'attachment_added', $uploader);
        }
    }

    public function delete(TicketAttachment $attachment, User $actor): void
    {
        $ticket = $attachment->ticket;
        $attachmentName = $attachment->original_name;

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        if ($ticket instanceof Ticket) {
            $this->recordHistory($ticket, null, 'attachment_deleted', $actor, $attachmentName);
        }
    }

    private function store(
        Ticket $ticket,
        UploadedFile $file,
        User $uploader,
        ?TicketComment $comment,
        string $visibility,
    ): TicketAttachment {
        $disk = $this->disk();
        $directory = trim((string) config('helpdesk.attachments.path', 'ticket-attachments'), '/').'/'.$ticket->id;
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $storedName = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs($directory, $storedName, $disk);

        return TicketAttachment::query()->create([
            'ticket_id' => $ticket->id,
            'ticket_comment_id' => $comment?->id,
            'user_id' => $uploader->id,
            'uploader_id' => $uploader->id,
            'visibility' => $visibility,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize() ?: 0,
        ]);
    }

    private function recordHistory(
        Ticket $ticket,
        ?TicketAttachment $attachment,
        string $action,
        User $actor,
        ?string $deletedName = null,
    ): void {
        $ticket->history()->create([
            'user_id' => $actor->id,
            'event' => TicketHistory::EVENT_UPDATED,
            'field' => 'attachments',
            'old_value' => null,
            'new_value' => [
                'attachment_id' => $attachment?->id,
                'name' => $attachment?->original_name ?? $deletedName,
            ],
            'meta' => [
                'action' => $action,
                'changed_fields' => ['attachments'],
            ],
        ]);
    }

    private function disk(): string
    {
        return (string) config('helpdesk.attachments.disk', 'local');
    }

    private function maxFiles(): int
    {
        return max(1, (int) config('helpdesk.attachments.max_files', 10));
    }

    private function maxSizeKilobytes(): int
    {
        return max(1, (int) config('helpdesk.attachments.max_size_mb', 20)) * 1024;
    }

    /**
     * @return array<int, string>
     */
    private function allowedExtensions(): array
    {
        return array_values((array) config('helpdesk.attachments.allowed_extensions', []));
    }

    /**
     * @return array<int, string>
     */
    private function allowedMimeTypes(): array
    {
        return array_values((array) config('helpdesk.attachments.allowed_mime_types', []));
    }
}
