#!/bin/bash

#!/bin/bash
/usr/bin/mysql --verbose --user=root --password <<EOFMYSQL
CREATE DATABASE IF NOT EXISTS tvivodb CHARACTER SET utf8;
USE tvivodb;
SHOW TABLES;

DROP TABLE IF EXISTS uploads;
CREATE TABLE uploads (  id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY(id),
                        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        username VARCHAR(100) NOT NULL DEFAULT '',
                        password_md5 VARCHAR(100) NOT NULL DEFAULT '',
                        notweet BOOL NOT NULL DEFAULT false,
                        remember BOOL NOT NULL DEFAULT false,
                        message TEXT DEFAULT '',
                        uploadservice VARCHAR(100) NOT NULL DEFAULT '',
                        remote_addr VARCHAR(64) NOT NULL DEFAULT '',
                        local_filepath VARCHAR(512) NOT NULL DEFAULT '',
                        service_filepath VARCHAR(512) NOT NULL DEFAULT '',
                        picasa_ua VARCHAR(255) NOT NULL DEFAULT '',
                        local_swmodel VARCHAR(255) NOT NULL DEFAULT '',
                        local_upload_ok ENUM('N','Y') NOT NULL default 'N',
                        service_upload_ok ENUM('N','Y') NOT NULL default 'N',
                        local_upload_status TEXT DEFAULT '',
                        service_upload_status TEXT DEFAULT ''
                     );

GRANT ALL PRIVILEGES ON tvivodb.* TO "tvivo"@"localhost"
IDENTIFIED BY "[password]";
FLUSH PRIVILEGES;

DROP TABLE IF EXISTS downloadstat;
CREATE TABLE downloadstat (
        filename varchar(255) NOT NULL,
        stats int(11) NOT NULL,
        PRIMARY KEY  (filename)
    );

SHOW TABLES;
DESCRIBE uploads;

EOFMYSQL

