<?php

return [
    'ticket' => [
        'subject' => '[Helpdesk] :number: :event',
        'greeting' => 'Hello,',
        'action' => 'View ticket detail',
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
            'resolved' => 'The ticket was marked as resolved.',
            'problem_persists' => 'The requester marked that the problem still persists.',
            'closed' => 'The ticket was closed.',
            'expected_resolution_changed' => 'The expected resolution was changed to: :expected_resolution_at.',
        ],
    ],
];
