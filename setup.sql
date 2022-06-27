-- Setup routine for SQHTML

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- Utility functions

DELIMITER $$

CREATE OR REPLACE FUNCTION sqh_escape(content LONGTEXT) RETURNS LONGTEXT DETERMINISTIC
  RETURN REPLACE(REPLACE(REPLACE(REPLACE(content, '&', '&amp;'), '<', '&lt;'), '{', '&lcub;'), '[', '&lsqb;')$$

CREATE OR REPLACE FUNCTION sqh_link(href TEXT, content LONGTEXT) RETURNS LONGTEXT DETERMINISTIC
  RETURN CONCAT('<a href="', href, '">', content, '</a>')$$

CREATE OR REPLACE FUNCTION sqh_tag (name TEXT, content LONGTEXT) RETURNS LONGTEXT DETERMINISTIC
  RETURN CONCAT('<', name, '>', content, '</', name, '>')$$

CREATE OR REPLACE FUNCTION `sqh_thead` (`from_table` VARCHAR(128)) RETURNS LONGTEXT DETERMINISTIC
BEGIN
  DECLARE temp LONGTEXT;
  SELECT CONCAT(
      '<thead><tr><th>',
      GROUP_CONCAT(column_name ORDER BY ordinal_position SEPARATOR '</th><th>'),
      '</th></tr></thead>')
    INTO temp
    FROM information_schema.columns
   WHERE table_name = from_table;
  RETURN temp;
END$$

DELIMITER ;


-- Tables

CREATE TABLE IF NOT EXISTS sqh_includes (
  name varchar(32) NOT NULL,
  content longtext NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS sqh_pages (
  path varchar(128) NOT NULL,
  content longtext NOT NULL,
  meta_type varchar(128) NOT NULL DEFAULT 'text/html',
  meta_title varchar(128) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE sqh_includes
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (name);

ALTER TABLE sqh_pages
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (path);

COMMIT;

