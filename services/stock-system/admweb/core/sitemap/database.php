<?php
$sqlArray[_DBPREFIX_ . 'sitemap'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "sitemap` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `loc` VARCHAR(500) NOT NULL COMMENT 'พาธ URL สัมพัทธ์ (เช่น /blog/post-slug)',
  `lastmod` DATE NOT NULL COMMENT 'วันที่แก้ไขล่าสุด (รูปแบบ YYYY-MM-DD)',
  `changefreq` VARCHAR(10) NOT NULL COMMENT 'ความถี่ในการเปลี่ยนแปลง (เช่น daily, weekly)',
  `priority` DECIMAL(2, 1) NOT NULL COMMENT 'ลำดับความสำคัญ (ค่า 0.0 ถึง 1.0)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),INDEX idx_loc (loc(255)) 
) 
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$sqlArray[_DBPREFIX_ . 'sitemap_custom'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "sitemap_custom` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `loc` VARCHAR(500) NOT NULL COMMENT 'พาธ URL สัมพัทธ์ (เช่น /blog/post-slug)',
  `lastmod` DATE NOT NULL COMMENT 'วันที่แก้ไขล่าสุด (รูปแบบ YYYY-MM-DD)',
  `changefreq` VARCHAR(10) NOT NULL COMMENT 'ความถี่ในการเปลี่ยนแปลง (เช่น daily, weekly)',
  `priority` DECIMAL(2, 1) NOT NULL COMMENT 'ลำดับความสำคัญ (ค่า 0.0 ถึง 1.0)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),INDEX idx_loc (loc(255)) 
) 
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
