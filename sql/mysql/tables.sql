CREATE TABLE IF NOT EXISTS `#__hikashop_billplz` (
   `id` bigint NOT NULL AUTO_INCREMENT,
   `bill_slug` varchar(25) NOT NULL,
   `order_id` bigint NOT NULL,
   `amount_sens` int(10) NOT NULL,

  PRIMARY KEY (`id`),
  KEY `billplz_bill` (`bill_slug`,`order_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;