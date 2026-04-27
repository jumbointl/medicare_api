# `v_doctors` view — manual SQL update

The view `v_doctors` is database-managed (its DDL is not stored in this repo).
After running the migration `2026_04_26_120000_add_auto_reschedule_flags_to_doctors_table`,
the four new columns must be added to the view's `SELECT` so the API can read them.

## How to apply

1. Capture the current DDL:
   ```sql
   SHOW CREATE VIEW v_doctors\G
   ```

2. In the captured `SELECT` list, add these four columns from the `doctors` table
   (typically aliased `d.`):
   ```sql
   d.auto_rescheduled_allowed,
   d.video_auto_rescheduled_allowed,
   d.auto_rescheduled_allowed_before_minutes,
   d.video_auto_rescheduled_allowed_before_minutes,
   ```

3. Re-create the view:
   ```sql
   CREATE OR REPLACE VIEW v_doctors AS
   SELECT
       -- ... existing columns ...
       d.auto_rescheduled_allowed,
       d.video_auto_rescheduled_allowed,
       d.auto_rescheduled_allowed_before_minutes,
       d.video_auto_rescheduled_allowed_before_minutes
       -- ... rest ...
   FROM doctors d
   -- ... existing joins ...
   ;
   ```

## Verify

```sql
SELECT auto_rescheduled_allowed,
       video_auto_rescheduled_allowed,
       auto_rescheduled_allowed_before_minutes,
       video_auto_rescheduled_allowed_before_minutes
FROM v_doctors LIMIT 1;
```
