# Attachments

The helpdesk supports attachments on tickets and public comments. Attachments are not embedded inline into the text body; they are stored as separate files linked to the ticket or comment.

## Storage Model

Attachments are stored through Laravel Storage on a non-public disk/path. They are not served from a direct public URL.

Important fields stored for each attachment include:

- ticket
- optional ticket comment
- uploader user
- original file name
- storage path
- MIME type
- file size
- timestamps

## Protected Preview and Download

Preview and download requests go through Laravel controller routes. Before the file is returned, the application verifies that the current user is allowed to view the related ticket.

This is required for internal and private tickets. A direct storage URL must not be exposed.

## UI Upload Queue

The UI supports selecting attachments in multiple steps before submitting a form. Newly selected files are added to the existing queue and can be removed before submission.

This is a browser convenience feature only. Server-side validation remains authoritative.

## Image Lightbox Gallery

Image attachments are shown as small thumbnails. Clicking a thumbnail opens a simple protected lightbox gallery using the controller preview URL.

The gallery can navigate between visible image attachments on the ticket detail page. Non-image attachments remain normal download links.

## Configuration

```env
HELPDESK_ATTACHMENT_MAX_SIZE_MB=20
HELPDESK_ATTACHMENT_MAX_FILES=10
HELPDESK_ATTACHMENT_DISK=local
HELPDESK_ATTACHMENT_PATH=ticket-attachments
```

`HELPDESK_ATTACHMENT_DISK=local` uses Laravel's private local disk by default.

## Align Application, PHP, and Web Server Limits

The application limit is not enough by itself. PHP and the web server must allow the same or larger request size.

Example PHP settings:

```ini
upload_max_filesize = 20M
post_max_size = 25M
max_file_uploads = 10
```

Example Nginx setting:

```nginx
client_max_body_size 25m;
```

If PHP or Nginx limits are lower than the application limit, uploads can fail before Laravel validation runs.

## Allowed and Blocked File Types

Allowed extensions and MIME types are configured in `config/helpdesk.php` and can be adjusted through environment variables.

Do not allow executable file types such as:

- `exe`
- `bat`
- `cmd`
- `ps1`
- `msi`
- `sh`

Keep the allow-list conservative and add only file types your organization needs.

## Backups

Back up the non-public storage path that contains attachments. A database backup without attachment storage is incomplete because ticket records would reference files that no longer exist.
