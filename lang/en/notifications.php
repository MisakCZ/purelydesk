<?php

return [
    'ticket' => [
        'subject' => '[Helpdesk #:number] :event',
        'greeting' => 'Hello,',
        'action' => 'View ticket detail',
        'reply_marker' => 'Reply above this line.',
        'events' => [
            'created' => 'New ticket',
            'public_comment' => 'New comment',
            'assignee_changed' => 'Assignee changed',
            'status_changed' => 'Status changed',
            'resolved' => 'Ticket resolved',
            'problem_persists' => 'Problem persists',
            'closed' => 'Ticket closed',
            'expected_resolution_changed' => 'Expected resolution changed',
        ],
        'lines' => [
            'event' => 'Event: :event',
            'number' => 'Ticket number: :number',
            'subject' => 'Subject: :subject',
            'ticket_description' => 'Ticket description:',
            'comment_body' => 'Comment content:',
            'footer' => 'This message was sent automatically from the helpdesk.',
        ],
        'descriptions' => [
            'created' => 'A new ticket was created in the helpdesk.',
            'public_comment' => 'A new comment was added to the ticket.',
            'assignee_changed' => 'The ticket assignee was changed to: :assignee.',
            'status_changed' => 'The ticket status was changed to: :status.',
            'resolved' => 'The ticket was marked as resolved. In the ticket detail you can confirm the resolution or report that the problem still persists.',
            'problem_persists' => 'The requester marked that the problem still persists.',
            'closed' => 'The ticket was closed.',
            'expected_resolution_changed' => 'The expected resolution was changed to: :expected_resolution_at.',
        ],
    ],
    'inbound' => [
        'console' => [
            'finished' => 'Inbound mail processed: :processed processed, :ignored ignored, :failed failed.',
        ],
        'attachments_ignored' => [
            'comment_note' => '[Helpdesk note: The e-mail contained attachments, but e-mail reply attachments are not imported automatically yet. Please upload them directly in the ticket detail.]',
            'subject' => '[Helpdesk] :number: attachments were not added',
            'greeting' => 'Hello,',
            'body' => 'Your text reply to ticket :number was accepted and saved as a comment. Attachments from the e-mail are not imported automatically yet, so please upload them directly in the ticket detail.',
            'action' => 'Open ticket detail',
        ],
    ],
];
