<?php

return [
    'ticket' => [
        'subject' => '[Helpdesk #:number] :event',
        'greeting' => 'Hello,',
        'action' => 'View ticket detail',
        'reply_marker' => 'Reply above this line.',
        'no_reply_instruction' => 'Please do not reply to this e-mail. Add your comment in the ticket detail using the link below.',
        'events' => [
            'created' => 'New ticket',
            'public_comment' => 'New comment',
            'assignee_changed' => 'Assignee changed',
            'status_changed' => 'Status changed',
            'resolved' => 'Ticket resolved',
            'problem_persists' => 'Problem persists',
            'closed' => 'Ticket closed',
            'expected_resolution_changed' => 'Expected resolution changed',
            'expected_resolution_due_soon' => 'Expected resolution is due soon',
            'expected_resolution_overdue' => 'Expected resolution is overdue',
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
            'closed' => 'The ticket has been closed in the helpdesk.',
            'closed_by_requester' => 'The requester confirmed the resolution. The ticket has therefore been closed.',
            'closed_automatically' => 'The ticket was automatically closed because the requester did not respond within :days days after it was marked as resolved.',
            'expected_resolution_changed' => 'The expected resolution changed from :old_expected_resolution_at to :expected_resolution_at.',
            'expected_resolution_due_soon' => 'The expected resolution date is :expected_resolution_at. If the deadline cannot be met, you can update the expected resolution date from the ticket detail/edit form. The requester will be notified by e-mail about the changed deadline. Please add a clear reason for the postponement, preferably as a public comment if the requester should see the reason.',
            'expected_resolution_overdue' => 'The expected resolution date :expected_resolution_at has passed. If the deadline cannot be met, you can update the expected resolution date from the ticket detail/edit form. The requester will be notified by e-mail about the changed deadline. Please add a clear reason for the postponement, preferably as a public comment if the requester should see the reason.',
        ],
    ],
    'expected_resolution_deadlines' => [
        'console' => [
            'finished' => 'Expected resolution deadline reminders processed: :due_soon due soon, :overdue overdue.',
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
