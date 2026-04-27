-- ============================================================
-- Fix the typo "befor_minutes" → "before_minutes" in v_doctors
-- The underlying user_clinics columns are spelled correctly
-- (auto_rescheduled_allowed_before_minutes); only the view alias had the typo.
-- Run once on each environment; safe to re-run (CREATE OR REPLACE).
-- ============================================================

CREATE OR REPLACE
  ALGORITHM = UNDEFINED
  DEFINER = `semedicare`@`%`
  SQL SECURITY DEFINER
VIEW `v_doctors` AS
SELECT
  `d`.`id`                                              AS `doctor_id`,
  `d`.`user_id`                                         AS `user_id`,
  `uc`.`clinic_id`                                      AS `clinic_id`,
  `c`.`title`                                           AS `clinic_title`,
  `u`.`id`                                              AS `user_ref_id`,
  `u`.`f_name`                                          AS `f_name`,
  `u`.`l_name`                                          AS `l_name`,
  CONCAT(
    TRIM(COALESCE(`u`.`f_name`, '')),
    ' ',
    TRIM(COALESCE(`u`.`l_name`, ''))
  )                                                     AS `doctor_name`,
  `u`.`email`                                           AS `email`,
  `u`.`phone`                                           AS `phone`,
  `u`.`image`                                           AS `image`,
  `u`.`gender`                                          AS `gender`,
  `u`.`dob`                                             AS `dob`,
  `u`.`address`                                         AS `address`,
  `u`.`city`                                            AS `city`,
  `u`.`state`                                           AS `state`,
  `u`.`postal_code`                                     AS `postal_code`,
  `d`.`department`                                      AS `department`,
  `dep`.`title`                                         AS `department_title`,
  `d`.`specialization`                                  AS `specialization`,
  `d`.`ex_year`                                         AS `ex_year`,
  `d`.`video_appointment`                               AS `video_appointment`,
  `d`.`video_provider`                                  AS `video_provider`,
  `d`.`clinic_appointment`                              AS `clinic_appointment`,
  `d`.`emergency_appointment`                           AS `emergency_appointment`,
  `d`.`opd_fee`                                         AS `opd_fee`,
  `d`.`video_fee`                                       AS `video_fee`,
  `d`.`emg_fee`                                         AS `emg_fee`,
  `uc`.`is_active`                                      AS `is_active`,
  `uc`.`is_default`                                     AS `is_default`,
  `uc`.`active`                                         AS `active`,
  `uc`.`stop_booking`                                   AS `stop_booking`,
  `uc`.`auto_rescheduled_allowed`                       AS `auto_rescheduled_allowed`,
  `uc`.`video_auto_rescheduled_allowed`                 AS `video_auto_rescheduled_allowed`,
  `uc`.`auto_rescheduled_allowed_before_minutes`        AS `auto_rescheduled_allowed_before_minutes`,
  `uc`.`video_auto_rescheduled_allowed_before_minutes`  AS `video_auto_rescheduled_allowed_before_minutes`,
  `d`.`created_at`                                      AS `created_at`,
  `d`.`updated_at`                                      AS `updated_at`
FROM `doctors` `d`
  JOIN `users` `u`         ON `u`.`id`     = `d`.`user_id`
  JOIN `user_clinics` `uc` ON `uc`.`user_id` = `d`.`user_id`
  JOIN `clinics` `c`       ON `c`.`id`     = `uc`.`clinic_id`
  LEFT JOIN `department` `dep` ON `dep`.`id` = `d`.`department`
WHERE COALESCE(`u`.`is_deleted`, 0) = 0;

-- Verify after running:
--   SHOW CREATE VIEW v_doctors\G
--   SELECT auto_rescheduled_allowed_before_minutes,
--          video_auto_rescheduled_allowed_before_minutes
--     FROM v_doctors LIMIT 1;
