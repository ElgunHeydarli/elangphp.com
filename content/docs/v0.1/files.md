# Private files, S3, and images

Elang stores file metadata in SQL and object bytes behind Flysystem. Local files live under private storage; S3 objects remain private. User filenames never become filesystem paths. Every download checks current identity and ownership or current team membership.

## Prepare and select a disk

```bash
php elang platform:setup
```

```dotenv
FILES_DISK=local
```

Local storage defaults to `storage/files` with private file/directory permissions. Expose only the application's `public/` directory. Do not create a public symlink to private storage. For S3:

```dotenv
FILES_DISK=s3
S3_BUCKET=your-private-bucket
S3_REGION=eu-central-1
# Prefer a workload role. Set S3_KEY and S3_SECRET only when required.
```

The AWS SDK uses its normal credential provider chain when a key is not explicitly configured. Grant access only to the application's bucket/prefix. Block public access and enable an appropriate encryption/versioning/lifecycle policy. S3-compatible development services can use `S3_ENDPOINT` and `S3_PATH_STYLE=true`. Custom production endpoints must use HTTPS. Loopback and the supplied development `seaweed` host may use HTTP outside production.

## Upload and list

```php
$files = $app->container->get(Elang\Storage\Files::class);
$record = $files->put($user, 'report.txt', 'Report contents');
$personal = $files->list($user);
$shared = $files->put($user, 'team-report.txt', $contents, $teamId);
```

The default maximum is 10 MiB; set `files.max_bytes` in application configuration for a different bounded limit. Empty uploads are rejected. Names accept at most 180 characters, reject control characters, and replace path separators. Objects use generated IDs under owner/team prefixes. Metadata includes byte count, MIME type, SHA-256, creator, and optional team ID. Disk names/object keys are not included in the public metadata result.

For HTTP uploads, first validate Symfony's `UploadedFile::isValid()` and upload size, then read at most your configured limit. Set PHP `upload_max_filesize`, `post_max_size`, and reverse-proxy limits consistently. The SaaS example includes this wiring. The string-based service API holds bytes in memory; use a carefully reviewed streaming upload design for very large objects.

A team upload requires `write`; team listing/downloading requires `read`. Personal files are accessible only to their owner. Listing returns the latest 100 metadata rows; add application-specific pagination for a large library.

## Safe image processing

```php
$image = $files->put($user, 'avatar.png', $uploadedBytes, null, true);
```

Image mode requires PHP GD. Elang decodes and re-encodes JPEG, PNG, or WebP, stripping ancillary metadata and appended content. SVG and unsupported formats are rejected. Limits are 4096 pixels per side and 12 megapixels, in addition to the byte limit. The output MIME type and extension come from the encoder. Allocate enough PHP memory for the decoded raster; compressed byte size alone does not bound image memory.

Orientation metadata is stripped; applications needing automatic EXIF rotation should add and test that policy explicitly. Antivirus scanning, content moderation, quotas, deduplication, thumbnail pipelines, and encrypted document processing are application extensions, not implied by image re-encoding.

## Downloads and temporary URLs

```php
$download = $files->download($user, $record['id']);
try {
    // Stream $download['stream'] in an authenticated response.
} finally {
    fclose($download['stream']);
}
$url = $files->temporaryUrl($user, $record['id'], 300);
```

Temporary URLs last 1–3600 seconds and bind the file ID, expiry, and requesting user with an application-key HMAC. They target `/files/{id}/download`; your application must register that route. The SaaS starter includes it. Verify the signature and then call `download()` under the signed-in user. A valid signature does not bypass revoked membership. The URL cannot be used by another account. These are authenticated application URLs, not public AWS presigned URLs.

Always send downloads as attachments, set `X-Content-Type-Options: nosniff`, and use private/no-store caching. Do not render arbitrary uploaded HTML inline on your main origin. Delete with `$files->delete($user, $id)`; team deletion requires write permission.

## Failure handling

SQL and object stores do not share a transaction. If metadata insertion fails after a write, Elang attempts to remove the object. A database rollback outside the service, or a cleanup failure, can leave an orphan. Deletion can fail between object removal and metadata deletion. For business-critical workflows, stage uploads with an explicit pending state, finalize after commit, and reconcile orphan/missing objects. Do not describe cross-system operations as atomic.

Next: [teams](/docs/v0.1/tenancy) and [deployment](/docs/v0.1/deployment).
