# LAN Discovery Check

1) On each device, run the bind command once:
   php artisan app:bind-identity

2) Start the queue worker on each device:
   php artisan queue:work

3) Ensure each device has a self record in LAN locations:
   - Check the `locations` table for `is_self = 1` and the bound IP.

4) Trigger discovery:
   - Use the Sync button in the UI (or dispatch AnnouncePresence).
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
