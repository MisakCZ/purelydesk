<?php

return [
    'formats' => [
        'datetime' => 'M j, Y H:i',
    ],
    'index' => [
        'page_title' => 'Announcements',
        'heading' => 'Announcements',
        'subheading' => 'Simple internal administration of announcements displayed above the ticket list.',
        'actions' => [
            'back_to_tickets' => 'Back to tickets',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'save' => 'Save announcement',
        ],
        'sections' => [
            'existing_heading' => 'Existing announcements',
            'existing_subheading' => 'Only announcements that are currently active and fall within the selected time window are shown above the ticket list.',
            'create_heading' => 'New announcement',
            'create_subheading' => 'Without full permission management for now. The new announcement is stored as public and active according to the entered dates.',
        ],
        'empty' => [
            'heading' => 'No announcements have been created yet',
            'body' => 'Once the first announcement is created, it will appear here and above the ticket list when it is currently active.',
        ],
        'meta' => [
            'author' => 'Author: :name',
            'visibility' => 'Visibility: :value',
            'starts_at' => 'From: :date',
            'ends_at' => 'Until: :date',
            'created_at' => 'Created: :date',
        ],
        'state' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],
    ],
    'active' => [
        'page_title' => 'Active announcements',
        'heading' => 'Active service announcements',
        'subheading' => 'Currently valid operational information available to helpdesk users.',
        'label' => 'Active announcements list',
        'actions' => [
            'back_to_dashboard' => 'Back to dashboard',
        ],
        'empty' => [
            'heading' => 'No active announcements',
            'body' => 'There are no active service announcements published right now.',
        ],
        'meta' => [
            'validity' => 'Valid',
            'from' => 'from',
            'to' => 'until',
        ],
    ],
    'edit' => [
        'page_title' => 'Edit announcement',
        'heading' => 'Edit announcement',
        'subheading' => 'Manage the content, activity, and time window of a service announcement.',
        'panel_subheading' => 'Update the content, activity, and time window of the announcement.',
        'panel_label' => 'Announcement editing',
        'actions' => [
            'back' => 'Back to announcements',
            'save' => 'Save changes',
            'cancel' => 'Cancel',
        ],
    ],
    'form' => [
        'title' => 'Title',
        'type' => 'Type',
        'body' => 'Text',
        'starts_at' => 'Active from',
        'ends_at' => 'Active until',
        'is_active' => 'Announcement is active',
    ],
    'flash' => [
        'created' => 'Announcement was created successfully.',
        'updated' => 'Announcement was updated successfully.',
        'deleted' => 'Announcement was deleted successfully.',
    ],
    'validation' => [
        'author_missing' => 'The announcement cannot be saved yet because there is no user in the database.',
    ],
    'values' => [
        'visibility' => [
            'public' => 'Public',
            'internal' => 'Internal',
            'private' => 'Private',
        ],
    ],
    'types' => [
        'info' => 'Info',
        'warning' => 'Warning',
        'outage' => 'Outage',
        'maintenance' => 'Maintenance',
    ],
];
