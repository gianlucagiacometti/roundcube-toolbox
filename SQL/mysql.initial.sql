CREATE TABLE `toolbox_customise_domains` (
    `id` int(11) NOT NULL,
    `domain_name` varchar(255) COLLATE utf8_general_ci NOT NULL,
    `purge_trash` integer NOT NULL DEFAULT 0,
    `purge_junk` integer NOT NULL DEFAULT 0,
    `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modified_by` varchar(255) COLLATE utf8_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

ALTER TABLE `toolbox_customise_domains`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `toolbox_customise_domains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


CREATE TABLE `toolbox_customise_skins` (
    `id` int(11) NOT NULL,
    `toolbox_customise_domain_id` integer,
    `skin` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
    `customise_blankpage` boolean NOT NULL DEFAULT false,
    `blankpage_type` text COLLATE utf8_general_ci DEFAULT NULL,
    `blankpage_image` text COLLATE utf8_general_ci DEFAULT NULL,
    `blankpage_url` varchar(1024) COLLATE utf8_general_ci DEFAULT NULL,
    `blankpage_custom` text COLLATE utf8_general_ci DEFAULT NULL,
    `customise_css` boolean NOT NULL DEFAULT false,
    `additional_css` text COLLATE utf8_general_ci DEFAULT NULL,
    `customise_favicon` boolean NOT NULL DEFAULT false,
    `favicon` text COLLATE utf8_general_ci DEFAULT NULL,
    `customise_logo` boolean NOT NULL DEFAULT false,
    `customised_logo` text COLLATE utf8_general_ci DEFAULT NULL,
    `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modified_by` varchar(255) COLLATE utf8_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ROW_FORMAT=DYNAMIC;

ALTER TABLE `toolbox_customise_skins`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `toolbox_customise_skins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


CREATE OR REPLACE VIEW `toolbox_customise_skins_view` AS
  SELECT
    `toolbox_customise_domains`.`domain_name`,
    `toolbox_customise_skins`.`skin`,
    `toolbox_customise_skins`.`customise_blankpage`,
    `toolbox_customise_skins`.`blankpage_type`,
    `toolbox_customise_skins`.`blankpage_image`,
    `toolbox_customise_skins`.`blankpage_url`,
    `toolbox_customise_skins`.`blankpage_custom`,
    `toolbox_customise_skins`.`customise_css`,
    `toolbox_customise_skins`.`additional_css`,
    `toolbox_customise_skins`.`customise_logo`,
    `toolbox_customise_skins`.`customised_logo`
  FROM `toolbox_customise_skins`
    LEFT JOIN `toolbox_customise_domains` ON (`toolbox_customise_domains`.`id` = `toolbox_customise_skins`.`toolbox_customise_domain_id`);

