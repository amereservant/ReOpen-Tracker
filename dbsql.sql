/** SQLite SQL **/
DROP TABLE IF EXISTS peers;
CREATE TABLE peers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  info_hash BINARY(20) NOT NULL,
  ip INTEGER(11) NOT NULL,
  port INTEGER(5) NOT NULL,
  peer_id BINARY(20) NOT NULL,
  uploaded INTEGER(20) NOT NULL default 0,
  downloaded INTEGER(20) NOT NULL default 0,
  remaining INTEGER(20) NOT NULL default 0,
  update_time INTEGER(14) NOT NULL,
  expire_time INTEGER(14) NOT NULL
);

/** MySQL SQL **/
DROP TABLE IF EXISTS peers;
CREATE TABLE `peers` (
    `id` INT(20) PRIMARY KEY AUTO_INCREMENT NOT NULL,    
    `info_hash` BINARY(20) NOT NULL, 
    `ip` INT(11) NOT NULL, `port` INT(5) NOT NULL, 
    `peer_id` BINARY(20) NOT NULL, 
    `uploaded` INT(20) NOT NULL DEFAULT 0, 
    `downloaded` INT(20) NOT NULL DEFAULT 0, 
    `remaining` INT(20) NOT NULL DEFAULT 0, 
    `update_time` INT NOT NULL, 
    `expire_time` INT NOT NULL 
);
