# Version 4.0.36
# Add 2 fields in usergroups_limits table for new options
ALTER TABLE `#__jdownloads_usergroups_limits` ADD `inquiry_hint` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL AFTER `notes`;
ALTER TABLE `#__jdownloads_usergroups_limits` ADD `view_gdpr_dsgvo_option` TINYINT(1) NOT NULL DEFAULT '0' AFTER `inquiry_hint`;