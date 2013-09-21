CREATE TABLE  `places` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `name` VARCHAR( 30 ) NOT NULL ,
        `state` BOOL NOT NULL DEFAULT '0',
        `changed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_swedish_ci;

ALTER TABLE  `places` ADD UNIQUE (
        `name`
        );
