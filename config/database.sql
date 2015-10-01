CREATE TABLE `policies` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `ts_id` bigint(12) NOT NULL UNIQUE,
  `name` varchar(255) NOT NULL UNIQUE,
  `charging_duration` varchar(255),
  `day_duration` varchar(255),
  `room_based` boolean,
  `day_overlap` boolean,
  `capacity` varchar(255),
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `service_types` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `ts_id` bigint(12) NOT NULL,
  `name` varchar(255) NOT NULL UNIQUE,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `regions` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `ts_id` bigint(12) NOT NULL,
  `name` varchar(255) NOT NULL UNIQUE,
  `parent_id` bigint(12) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `currencies` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `code` varchar(12) NOT NULL UNIQUE,
  `name` varchar(25) NOT NULL UNIQUE,
  `symbol` varchar(255) NOT NULL UNIQUE,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `suppliers` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `ts_id` bigint(12) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text(1000) DEFAULT NULL,
  `region_id` bigint(12) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_1` (`name`,`region_id`),
  FOREIGN KEY (`region_id`) REFERENCES regions(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `services` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `ts_id` bigint(12) NOT NULL,
  `short_name` varchar(25) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text(1000) DEFAULT NULL,
  `service_type_id` bigint(12) NOT NULL,
  `region_id` bigint(12) NOT NULL,
  `supplier_id` bigint(12) NOT NULL,
  `currency_id` bigint(12) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`service_type_id`) REFERENCES service_types(`id`),
  FOREIGN KEY (`region_id`) REFERENCES regions(`id`),
  FOREIGN KEY (`supplier_id`) REFERENCES suppliers(`id`),
  FOREIGN KEY (`currency_id`) REFERENCES currencies(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `exchange_rates` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `from_currency` varchar(12) NOT NULL,
  `to_currency` varchar(12) NOT NULL,
  `rate` float(12) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `occupancies` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL UNIQUE,
  `max_adults` integer(12) NOT NULL,
  `max_children` integer(12) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `meals` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL UNIQUE,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `service_extras` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `ts_id` bigint(12) NOT NULL,
  `service_id` bigint(12) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mandatory` boolean DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`service_id`) REFERENCES services(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `service_options` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `ts_id` bigint(12) NOT NULL,
  `service_id` bigint(12) NOT NULL,
  `occupancy_id` bigint(12) NOT NULL,
  `name` varchar(255) NOT NULL,
  `service_extra_id` bigint(12) NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`service_id`) REFERENCES services(`id`),
  FOREIGN KEY (`service_extra_id`) REFERENCES service_extras(`id`),
  FOREIGN KEY (`occupancy_id`) REFERENCES occupancies(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `meal_options` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `service_option_id` bigint(12) NOT NULL,
  `meal_id` bigint(12) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`service_option_id`) REFERENCES service_options(`id`),
  FOREIGN KEY (`meal_id`) REFERENCES meals(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `contracts` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `ts_id` bigint(12) NOT NULL,
  `service_id` bigint(12) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`service_id`),
  FOREIGN KEY (`service_id`) REFERENCES services(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `contract_periods` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `ts_id` bigint(12) NOT NULL,
  `contract_id` bigint(12) NOT NULL,
  `name` varchar(255) NOT NULL,
  `start` date NOT NULL,
  `end` date NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`contract_id`) REFERENCES contracts(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `seasons` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `ts_id` bigint(12) NOT NULL,
  `contract_period_id` bigint(12) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`contract_period_id`),
  FOREIGN KEY (`contract_period_id`) REFERENCES contract_periods(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `season_periods` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `season_id` bigint(12) NOT NULL,
  `start` date NOT NULL,
  `end` date NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`season_id`) REFERENCES seasons(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `prices` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `priceable_id` bigint(12) NOT NULL,
  `priceable_type` varchar(255) NOT NULL,
  `season_period_id` bigint(12) NOT NULL,
  `service_id` bigint(12) NOT NULL,
  `buy_price` DECIMAL(10, 2) NOT NULL,
  `sell_price` DECIMAL(10, 2) NOT NULL,
  `has_details` boolean DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `price` (`service_id`,`priceable_id`,`priceable_type`, `season_period_id`, `buy_price`, `sell_price`),
  FOREIGN KEY (`season_period_id`) REFERENCES season_periods(`id`),
  FOREIGN KEY (`service_id`) REFERENCES services(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;