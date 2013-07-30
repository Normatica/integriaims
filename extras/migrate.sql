-- ---------------------------------------------------
-- Feb 2013
-- ---------------------------------------------------

-- ---------------------------------------------------------------------
-- Table `tincident_stats`
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tincident_stats` (
`id_incident` bigint(20) unsigned NOT NULL auto_increment,
  `minutes` bigint(10) unsigned NOT NULL default 0,
  `metric` enum ('user_time', 'status_time', 'group_time', 'total_time', 'total_w_third') NOT NULL,
  `id_user` varchar(60) NOT NULL default '',
  `status` tinyint NOT NULL default 0,
  `id_group` mediumint(8) NOT NULL default 0,
PRIMARY KEY (`id_incident`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------
-- Table `tincidencia`
-- ---------------------------------------------------
ALTER TABLE `tincidencia` DROP COLUMN `origen`;
ALTER TABLE `tincidencia` ADD COLUMN `last_stat_check` datetime NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `tincidencia` ADD COLUMN `closed_by` varchar(60) NOT NULL default '';

-- ---------------------------------------------------
-- Table `tincident_track`
-- ---------------------------------------------------
ALTER TABLE `tincident_track` CHANGE COLUMN `id_aditional` `id_aditional` varchar(60) NOT NULL DEFAULT '0';


-- ---------------------------------------------------
-- Table `tincident_origin`
-- ---------------------------------------------------
DROP TABLE `tincident_origin`;

-- ---------------------------------------------------------------------
-- Table `tincident_status`
-- ---------------------------------------------------------------------
UPDATE `tincident_status` SET name='Pending on a third person'
WHERE name='Resolved';

-- ---------------------------------------------------
-- Table `tincident_type`
-- ---------------------------------------------------
ALTER TABLE `tincident_type` ADD COLUMN `id_group` int(10) NOT NULL default '0';

-- ---------------------------------------------------
-- Table `tincident_type_field`
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `tincident_type_field` ( 
  `id` mediumint(8) unsigned NOT NULL auto_increment, 
  `id_incident_type` mediumint(8) unsigned NOT NULL, 
  `label` varchar(100) NOT NULL default '', 
  `type` enum ('textarea', 'text', 'combo') default 'text',
  `combo_value` text default NULL,
  PRIMARY KEY  (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------
-- Table `tincident_field_data`
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `tincident_field_data` ( 
  `id` bigint(20) unsigned NOT NULL auto_increment, 
  `id_incident` bigint(20) unsigned NOT NULL,
  `id_incident_field` mediumint(0) unsigned NOT NULL,
  `data` text default NULL,
  PRIMARY KEY  (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ---------------------------------------------------
-- Table `tusuario`
-- ---------------------------------------------------
ALTER TABLE `tusuario` ADD COLUMN `num_employee` varchar(125) NOT NULL default '';
ALTER TABLE `tusuario` ADD COLUMN `enable_login` tinyint(1) NOT NULL default '1';

ALTER TABLE `tinventory` ADD COLUMN `id_object_type` mediumint(8) unsigned;
ALTER TABLE `tinventory` ADD COLUMN `owner` varchar(60);
ALTER TABLE `tinventory` CHANGE COLUMN `name` `name` TEXT default '';
ALTER TABLE `tinventory` ADD COLUMN `public` TINYINT(1) unsigned DEFAULT 1;
ALTER TABLE `tinventory` CHANGE COLUMN `description` `description` TEXT default NULL;
ALTER TABLE `tinventory` DROP COLUMN `serial_number`;
ALTER TABLE `tinventory` DROP COLUMN `part_number`;
ALTER TABLE `tinventory` DROP COLUMN `comments`;
ALTER TABLE `tinventory` DROP COLUMN `confirmed`;
ALTER TABLE `tinventory` DROP COLUMN `cost`;
ALTER TABLE `tinventory` DROP COLUMN `ip_address`;
ALTER TABLE `tinventory` DROP COLUMN `id_product`;
ALTER TABLE `tinventory` DROP COLUMN `id_sla`;
ALTER TABLE `tinventory` DROP COLUMN `id_building`;
ALTER TABLE `tinventory` DROP COLUMN `generic_1`;
ALTER TABLE `tinventory` DROP COLUMN `generic_2`;
ALTER TABLE `tinventory` DROP COLUMN `generic_3`;
ALTER TABLE `tinventory` DROP COLUMN `generic_4`;
ALTER TABLE `tinventory` DROP COLUMN `generic_5`;
ALTER TABLE `tinventory` DROP COLUMN `generic_6`;
ALTER TABLE `tinventory` DROP COLUMN `generic_7`;
ALTER TABLE `tinventory` DROP COLUMN `generic_8`;

CREATE TABLE `tinventory_relationship` (
   `id_object_src` mediumint(8) unsigned NOT NULL,
   `id_object_dst`  mediumint(8) unsigned NOT NULL,
   KEY `tinvrsx_1` (`id_object_src`),
   KEY `tinvrsx_2` (`id_object_dst`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tobject_type` ( 
  `id` mediumint(8) unsigned NOT NULL auto_increment, 
  `name` varchar(100) NOT NULL default '', 
  `description` text NULL default NULL,
  `icon` text null default null,
  PRIMARY KEY  (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tobject_type_field` ( 
  `id` mediumint(8) unsigned NOT NULL auto_increment, 
  `id_object_type` mediumint(8) unsigned, 
  `label` varchar(100) NOT NULL default '', 
  `type` enum ('numeric', 'text', 'combo', 'external' ) default 'text',
  `combo_value` text default NULL,
  `external_table_name` text default null,
  `external_reference_field` text default null,
  `unique` int(1) default 0,
  `inherit` int(1) default 0,
  PRIMARY KEY  (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tobject_field_data` ( 
  `id` bigint(20) unsigned NOT NULL auto_increment, 
  `id_inventory` bigint(20) unsigned NOT NULL,
  `id_object_type_field` mediumint(8) unsigned NOT NULL,
  `data` text default NULL,
  PRIMARY KEY  (`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Sancho 29 Marzo 2013


CREATE TABLE `tlead` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `id_company` mediumint(8) unsigned NOT NULL,
  `id_language` varchar(6) default NULL,
  `id_category` mediumint(8) unsigned default NULL,
  `owner` varchar(60) default NULL,
  `fullname` varchar(150) DEFAULT NULL,
  `email` tinytext  default NULL,
  `phone` tinytext  default NULL,
  `mobile` tinytext  default NULL,
  `position` tinytext  default NULL,
  `company` tinytext  default NULL,
  `country` tinytext  default NULL,
  `description` mediumtext DEFAULT NULL,
  `creation` datetime NOT NULL default '0000-00-00 00:00:00',  
  `modification` datetime NOT NULL default '0000-00-00 00:00:00',  
  `progress` mediumint(5) NULL default 0,
  `estimated_sale` mediumint NULL default 0,
  PRIMARY KEY  (`id`),
  KEY `id_company_idx` (`id_company`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tlead_activity` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `id_lead` mediumint(8) unsigned NOT NULL,
  `written_by` mediumtext DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `creation` datetime NOT NULL default '0000-00-00 00:00:00',  
  PRIMARY KEY  (`id`),
  KEY `id_lead_idx` (`id_lead`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tlead_history` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `id_lead` mediumint(8) unsigned NOT NULL,
  `id_user` mediumtext DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',  
  PRIMARY KEY  (`id`),
  KEY `id_lead_idx` (`id_lead`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tcrm_template` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` mediumtext DEFAULT NULL,
  `subject` varchar(250) DEFAULT NULL,
  `id_language` varchar(6) default NULL,
  `id_company` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `two_category` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` text default NULL,
  `icon` text default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE tpending_mail ADD `from` text DEFAULT NULL;

UPDATE tconfig set `value`= '4.0' WHERE `token` = "db_scheme_version";
UPDATE tconfig set `value`= '130330' WHERE `token` = "db_scheme_build";

ALTER TABLE tattachment ADD `id_lead` bigint(20) NOT NULL default '0';
ALTER TABLE tattachment ADD `timestamp` date NOT NULL default '0000-00-00';
ALTER TABLE tattachment ADD `id_company` bigint(20) NOT NULL default '0';
ALTER TABLE tattachment ADD `id_todo` bigint(20) NOT NULL default '0';
ALTER TABLE ttodo ADD `start_date` datetime NOT NULL default '2000-01-01 00:00:00';
ALTER TABLE ttodo ADD `end_date` datetime NOT NULL default '2000-01-01 00:00:00';
ALTER TABLE ttodo ADD `validation_date` datetime NOT NULL default '2000-01-01 00:00:00';
ALTER TABLE ttodo ADD `need_external_validation` tinyint unsigned NOT NULL DEFAULT 0;
ALTER TABLE ttodo ADD `id_wo_category` int(10) default NULL;

ALTER TABLE tcompany ADD `country` tinytext NULL default NULL;
ALTER TABLE tcompany ADD `website` tinytext NULL default NULL;
ALTER TABLE tcompany ADD `id_parent` mediumint(8) unsigned default NULL;
ALTER TABLE tcompany ADD `manager` varchar(150) NOT NULL default '';

CREATE TABLE `ttranslate_string` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lang` tinytext NOT NULL,
  `string` text NOT NULL,
  `translation` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE tprofile ADD `cr` tinyint(1) NOT NULL default '0';
ALTER TABLE tprofile ADD `cw` tinyint(1) NOT NULL default '0';
ALTER TABLE tprofile ADD `cm` tinyint(1) NOT NULL default '0';

ALTER TABLE tprofile ADD `ffr` tinyint(1) NOT NULL default '0';
ALTER TABLE tprofile ADD `ffw` tinyint(1) NOT NULL default '0';
ALTER TABLE tprofile ADD `ffm` tinyint(1) NOT NULL default '0';

ALTER TABLE tdownload ADD `public` int(2) unsigned NOT NULL default 0;
ALTER TABLE tdownload ADD `external_id` text NOT NULL default '';


ALTER TABLE tattachment MODIFY `description` text default '';
ALTER TABLE tdownload_category ADD   `icon` varchar(100) NOT NULL default '';
ALTER TABLE tdownload ADD `id_user` varchar(60) NOT NULL;
 
ALTER TABLE tinvoice ADD status enum ('pending', 'paid', 'canceled') default 'pending';
ALTER TABLE tinvoice ADD `tax` float(11,2) NOT NULL DEFAULT '0.0';

CREATE TABLE `tholidays` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `day` DATETIME  NOT NULL DEFAULT 0,
   PRIMARY KEY  (`id`),
   UNIQUE (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tinventory_track` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `id_inventory` bigint(20) unsigned NOT NULL default '0',
  `id_aditional` varchar(60) NOT NULL DEFAULT '0',
  `state` int(10) unsigned NOT NULL default '0',
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `id_user` varchar(60) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `tinventorytask_idx_1` (`id_inventory`),
  FOREIGN KEY (`id_inventory`) REFERENCES tinventory(`id`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* 20/05/2013 */
ALTER TABLE tinventory ADD `show_list` TINYINT(1) unsigned DEFAULT 1;

ALTER TABLE tobject_type_field ADD `show_list` TINYINT(1) unsigned DEFAULT 1;
/* 21/05/2013 */
INSERT INTO tobject_type (name, description, icon) VALUES 
('Pandora agents', 'Imported agents from Pandora FMS', 'pandora.png');

/* 21/05/2013 */
INSERT INTO tobject_type_field (id, id_object_type, label, type) VALUES 
(1,1,'OS','text'),
(2,1,'IP Address','text'),
(3,1,'URL Address','text'),
(4,1,'ID Agent','text');

/*  30/05/2013 */
CREATE TABLE `tkb_product_group` (
  `id_product` mediumint(8) unsigned NOT NULL,
  `id_group` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (id_product, id_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*  05/06/2013 */
UPDATE tprofile SET `cr` = 1;
UPDATE tprofile SET `cw` = 1 WHERE `id` IN (1, 3);
UPDATE tprofile SET `cm` = 1 WHERE `id` IN (1, 3);
UPDATE tprofile SET `frr` = 1;
UPDATE tprofile SET `frw` = 1 WHERE `id` IN (1, 3);
UPDATE tprofile SET `frm` = 1 WHERE `id` IN (1, 3);

/* 25/06/2013 */
ALTER TABLE tcompany ADD `last_update` datetime NOT NULL default '0000-00-00 00:00:00';

/* 27/06/2013 */
ALTER TABLE tprofile ADD `cn` tinyint(1) NOT NULL default '0';

UPDATE tprofile SET `cn` = 1;

ALTER TABLE `tcompany` DROP COLUMN `id_grupo`;

/* 09/07/2013 */
ALTER TABLE tinvoice MODIFY `bill_id` int(10) unsigned NOT NULL;
ALTER TABLE tinvoice MODIFY `invoice_create_date` date NOT NULL DEFAULT '0000-00-00';
ALTER TABLE tinvoice MODIFY `invoice_payment_date` date DEFAULT NULL;
ALTER TABLE tinvoice DROP COLUMN `ammount`;
ALTER TABLE tinvoice ADD COLUMN `concept1` mediumtext DEFAULT NULL;
ALTER TABLE tinvoice ADD COLUMN `concept2` mediumtext DEFAULT NULL;
ALTER TABLE tinvoice ADD COLUMN `concept3` mediumtext DEFAULT NULL;
ALTER TABLE tinvoice ADD COLUMN `concept4` mediumtext DEFAULT NULL;
ALTER TABLE tinvoice ADD COLUMN `concept5` mediumtext DEFAULT NULL;
ALTER TABLE tinvoice ADD COLUMN `amount1` float(11,2) NOT NULL DEFAULT 0.00;
ALTER TABLE tinvoice ADD COLUMN `amount2` float(11,2) NOT NULL DEFAULT 0.00;
ALTER TABLE tinvoice ADD COLUMN `amount3` float(11,2) NOT NULL DEFAULT 0.00;
ALTER TABLE tinvoice ADD COLUMN `amount4` float(11,2) NOT NULL DEFAULT 0.00;
ALTER TABLE tinvoice ADD COLUMN `amount5` float(11,2) NOT NULL DEFAULT 0.00;

/* 10/07/2013 */
ALTER TABLE tinvoice ADD COLUMN `currency` VARCHAR(3) NOT NULL DEFAULT 'EUR';


/* 12/07/2013 */
ALTER TABLE `tgrupo` ADD `autocreate_user` int(5) unsigned NOT NULL default 0;
ALTER TABLE `tgrupo` ADD `grant_access` int(5) unsigned NOT NULL default 0;
ALTER TABLE `tgrupo` ADD `send_welcome` int(5) unsigned NOT NULL default 0;
ALTER TABLE `tgrupo` ADD `default_company` mediumint(8) unsigned NOT NULL default 0;
ALTER TABLE `tgrupo` ADD `email_queue` text;
ALTER TABLE `tgrupo` ADD `welcome_email` text;
ALTER TABLE `tgrupo` ADD `default_profile` int(10) unsigned NOT NULL default 0;

/* 15/07/2013 */
ALTER TABLE `tgrupo` ADD `nivel` tinyint(1) NOT NULL default '0';
ALTER TABLE `tgrupo` ADD `simple_mode` tinyint(2) unsigned NOT NULL DEFAULT 0;
ALTER TABLE `tgrupo` ADD `id_incident_type` mediumint(8) unsigned NULL;

/* 16/07/2013 */
CREATE TABLE tsessions_php (
	`id_session` CHAR(32) NOT NULL,
	`last_active` INTEGER NOT NULL,
	`data` TEXT,
	PRIMARY KEY (`id_session`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*16/07/2013*/
ALTER TABLE `tpending_mail`ADD `cc` text DEFAULT NULL;

/* 18/07/2013 */
ALTER TABLE `ttodo` ADD `email_notify` tinyint unsigned NOT NULL DEFAULT 0;

/* 24/07/2013 */
ALTER TABLE `tusuario` ADD `location` tinytext NOT NULL DEFAULT '';
ALTER TABLE `tagenda` ADD `title` varchar(255) NOT NULL DEFAULT 'N/A';
ALTER TABLE `tagenda` ADD `description` mediumtext NOT NULL DEFAULT '';
ALTER TABLE `tagenda` DROP `id_group`;
ALTER TABLE `tagenda` DROP `content`;

/* 24/07/2013 */
ALTER TABLE `tinventory` ADD COLUMN `last_update` date NOT NULL default '0000-00-00';

/* 30/07/2013 */
ALTER TABLE `ttask` DROP `id_group`;
