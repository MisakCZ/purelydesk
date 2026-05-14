<?php

return [
    'page_title' => 'Dashboard',
    'heading' => 'Dashboard',
    'subheading' => 'A compact overview of your helpdesk work queue.',
    'actions' => [
        'new_ticket' => 'New ticket',
        'all_tickets' => 'Full ticket list',
        'open_list' => 'Open list',
        'open_full_list' => 'Open full list',
        'view_all' => 'View all',
        'new_ticket_hint' => 'Before creating a new request, check whether it is already being handled:',
        'check_open_tickets' => 'open tickets',
        'new_ticket_tooltip' => 'Before creating a general ticket, please check whether it has already been submitted by another user.',
    ],
    'announcements' => [
        'label' => 'Active service announcements',
        'heading' => 'Service announcements',
        'view_all' => 'View all announcements',
        'open' => 'Open announcement',
        'validity' => 'Valid',
        'from' => 'from',
        'to' => 'until',
    ],
    'summary' => [
        'label' => 'Work queue summary',
        'new_unassigned_tickets' => 'New unassigned',
        'my_assigned_tickets' => 'My assigned',
        'due_today' => 'Due today',
        'due_soon' => 'Due soon',
        'waiting_for_user' => 'Waiting for user',
        'without_expected_resolution' => 'Without deadline',
        'due_soon_or_overdue' => 'Overdue / due soon',
    ],
    'summary_notes' => [
        'new_unassigned_tickets' => 'Open tickets without assignee',
        'my_assigned_tickets' => 'Your active queue',
        'due_today' => 'Expected resolution today',
        'due_soon' => 'Within 24 hours',
        'waiting_for_user' => 'Waiting for requester input',
    ],
    'pinned' => [
        'label' => 'Pinned tickets',
        'heading' => 'Important open tickets',
        'subheading' => 'Pinned tickets visible to you. They help verify whether the issue is already being handled.',
    ],
    'personal' => [
        'heading' => 'Tickets I requested',
        'open_requested' => '{0} You have no open requested tickets.|{1} You have 1 open requested ticket.|[2,*] You have :count open requested tickets.',
        'open_link' => 'Open tickets I requested',
    ],
    'sections' => [
        'limited_preview' => 'Showing the latest :count items. The full result is available in the list.',
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
        'without_expected_resolution' => [
            'heading' => 'Without expected resolution',
            'subheading' => 'Your assigned open tickets without an expected resolution date.',
            'empty' => 'Your assigned open tickets all have an expected resolution date.',
        ],
    ],
    'current' => [
        'heading' => 'Current tickets',
        'subheading' => 'Showing the latest :count items. The full list is available in the ticket list.',
        'empty' => 'There are no current tickets for the work overview.',
        'columns' => [
            'id' => 'ID',
            'subject' => 'Subject',
            'requester' => 'Requester',
            'priority' => 'Priority',
            'status' => 'Status',
            'deadline' => 'Deadline',
            'updated_at' => 'Updated',
        ],
    ],
    'sla' => [
        'heading' => 'SLA / Deadlines',
        'none' => 'No relevant ticket',
        'overdue' => [
            'label' => 'Overdue',
            'ticket' => 'Oldest: :ticket',
            'note' => 'Needs attention',
        ],
        'due_soon' => [
            'label' => 'Due soon (≤ 24 h)',
            'ticket' => 'Nearest: :ticket',
            'note' => 'Less than 24 h left',
        ],
        'due_today' => [
            'label' => 'Due today',
            'ticket' => 'Nearest: :ticket',
            'note' => 'By the end of the day',
        ],
        'resolved' => [
            'label' => 'Resolved',
            'ticket' => 'Latest: :ticket',
            'note_solver' => 'Your resolved tickets',
            'note_requester' => 'Your resolved requests',
        ],
    ],
    'diagnostics' => [
        'heading' => 'Deadline check',
        'missing_expected_resolution' => '{1} 1 assigned open ticket has no expected resolution date.|[2,*] :count assigned open tickets have no expected resolution date.',
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
