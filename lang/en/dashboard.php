<?php

return [
    'page_title' => 'Dashboard',
    'heading' => 'Dashboard',
    'subheading' => 'A compact overview of your helpdesk work queue.',
    'actions' => [
        'new_ticket' => 'New ticket',
        'all_tickets' => 'Full ticket list',
        'open_list' => 'Open list',
    ],
    'summary' => [
        'label' => 'Work queue summary',
        'new_unassigned_tickets' => 'New unassigned',
        'my_assigned_tickets' => 'Assigned to me',
        'waiting_for_user' => 'Waiting for user',
        'due_soon_or_overdue' => 'Overdue / due soon',
    ],
    'personal' => [
        'heading' => 'Tickets I requested',
        'open_requested' => '{0} You have no open requested tickets.|{1} You have 1 open requested ticket.|[2,*] You have :count open requested tickets.',
        'open_link' => 'Open tickets I requested',
    ],
    'sections' => [
        'my_open_tickets' => [
            'heading' => 'My open tickets',
            'subheading' => 'Tickets you requested that are not closed or cancelled.',
            'empty' => 'You have no open tickets.',
        ],
        'waiting_for_confirmation' => [
            'heading' => 'Waiting for my confirmation',
            'subheading' => 'Your resolved tickets waiting for confirmation.',
            'empty' => 'No tickets are waiting for your confirmation.',
        ],
        'new_unassigned_tickets' => [
            'heading' => 'New unassigned tickets',
            'subheading' => 'Visible new tickets that are not assigned to a solver yet.',
            'empty' => 'There are no new unassigned tickets.',
        ],
        'my_assigned_tickets' => [
            'heading' => 'My assigned tickets',
            'subheading' => 'Open tickets currently assigned to you.',
            'empty' => 'You have no assigned open tickets.',
        ],
        'waiting_for_user' => [
            'heading' => 'Waiting for user',
            'subheading' => 'Tickets waiting for requester input.',
            'empty' => 'No visible tickets are waiting for user input.',
        ],
        'resolved_waiting_confirmation' => [
            'heading' => 'Resolved waiting for confirmation',
            'subheading' => 'Resolved tickets visible to you.',
            'empty' => 'No resolved tickets are waiting for confirmation.',
        ],
        'due_soon_or_overdue' => [
            'heading' => 'Overdue / due soon',
            'subheading' => 'Open tickets with expected resolution within the next three days or already overdue.',
            'empty' => 'No visible tickets are due soon or overdue.',
        ],
    ],
    'admin' => [
        'heading' => 'Administration',
        'subheading' => 'Quick links for administrative work.',
        'links' => [
            'tickets' => 'Ticket list',
            'archive' => 'Archived tickets',
            'announcements' => 'Announcements',
        ],
    ],
    'ticket' => [
        'updated_at' => 'Updated :date',
        'expected_resolution_at' => 'Expected resolution: :date',
    ],
];
