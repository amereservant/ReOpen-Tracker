====================
 ReOpenTracker
 Version 1.0.0
====================

To run ReOpenTracker, you need two things: a web server that supports PHP5, and a MySQL database OR SQLite PDO driver.

PHP should be set for magic_quotes_gpc off.

Copy and execute the correct SQL in dbsql.sql on your database. 
This will create the table required by ReOpenTracker, which is named 'peers' by default.
You may rename the table if desired.

Edit config.inc.php, filling in the appropriate information for your database setup.

That's all there is to it. Create a torrent using announce.php on your server as the tracker announce URL, and away you go.
