<?php

namespace App\Http\Controllers;

use App\Models\TicketAttachment;
use App\Policies\TicketPolicy;
use App\Services\TicketAttachmentService;
use App\Support\ResolvesHelpdeskUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    use ResolvesHelpdeskUser;

    public function preview(TicketAttachment $attachment): Response|StreamedResponse
    {
        $this->authorizeAttachmentView($attachment);

        abort_unless($attachment->isImage(), 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'],
        );
    }

    public function download(TicketAttachment $attachment): StreamedResponse
    {
        $this->authorizeAttachmentView($attachment);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'],
        );
    }

    public function destroy(TicketAttachment $attachment): RedirectResponse
    {
        $ticket = $attachment->ticket;
        abort_unless($ticket !== null, 404);
        $actor = $this->currentHelpdeskUser();

        abort_unless(
            app(TicketPolicy::class)->deleteAttachment($actor, $ticket),
            403,
        );

        app(TicketAttachmentService::class)->delete($attachment, $actor);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', __('tickets.flash.attachment_deleted'));
    }

    private function authorizeAttachmentView(TicketAttachment $attachment): void
    {
        $ticket = $attachment->ticket;
        abort_unless($ticket !== null, 404);

        abort_unless(
            app(TicketPolicy::class)->view($this->currentHelpdeskUser(), $ticket),
            403,
        );
    }
}
