# LAN Discovery Check

1) On each device, run the bind command once:
   php artisan app:bind-identity

2) Start the queue worker on each device:
   php artisan queue:work

3) Ensure each device has a self record in LAN locations:
   - Check the `locations` table for `is_self = 1` and the bound IP.

4) Trigger discovery:
   - Use the Sync button in the top bar (manual trigger).
   - Verify other devices appear in `locations` with `last_seen_at` updated.

5) Sanity check:
   - Each target device should have a LAN `locations` row
     whose `name` matches the destination phppos location name,
     and a valid `ip` + recent `last_seen_at`.

## Transfer Sync Check

1) Create or save a Transfer Out on the source device.

2) Verify a queue entry exists:
   - `transfer_queue` should have a new row with
     `item_type = transfer_out` and `status = pending`.

3) Let the queue worker run:
   - `status` should move to `delivered` or `failed`.
   - If `failed`, check the `error` column.

4) On the destination device, verify:
   - A new Transfer In exists in `phppos_transfers`.
   - A Receiving record exists with `source = transfer`
     and `reference_id = transfer_out_id`.

5) If no send happens:
   - Confirm a LAN `locations` row exists for the destination
     with `name` matching phppos location name.
   - Confirm `last_seen_at` is recent and `ip` is correct.

## Sync & Notifications (Manual)

- **Sync button (top bar):** manually triggers LAN discovery and queue send attempts.
- **Refresh notifications (top bar bell):** manually fetches recent transfer queue status.
- There is **no automatic polling** for notifications.

## NSSM Queue Worker (Configured by Installer)

During installation, configure NSSM to run the queue worker automatically.

Inputs the installer should collect/use:
- PHP path (e.g., C:\\path\\to\\php.exe)
- App install path (e.g., C:\\Program Files\\LaravelPos)

Installer actions:
1) Install or bundle NSSM (e.g., C:\\Program Files\\LaravelPos\\nssm.exe).
2) Create the service:
   nssm install LaravelPosQueueWorker

   Application:
   - Path: {PHP_PATH}
   - Startup directory: {APP_PATH}
   - Arguments: artisan queue:work --sleep=1 --tries=1 --timeout=60

3) Set service display name and autostart:
   nssm set LaravelPosQueueWorker DisplayName "Laravel POS Queue Worker"
   nssm set LaravelPosQueueWorker Start SERVICE_AUTO_START

4) Start the service:
   nssm start LaravelPosQueueWorker

5) Optional uninstall steps:
   nssm stop LaravelPosQueueWorker
   nssm remove LaravelPosQueueWorker confirm
