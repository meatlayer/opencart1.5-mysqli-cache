# Opencart1.5 mysqliz driver + cache
The mysqliz class for opencart 1.5, which will allow you to work on php 5.6 - php 7.4 using mysqli, and also has built-in caching.

OpenCart 1.5.x compatible!

1. Copy mysqliz.php file into the /system/library/database
2. Change /config.php file only:

  define('DB_DRIVER', 'mysqliz');
  define('DB_CACHED_EXPIRE', 300);
  
