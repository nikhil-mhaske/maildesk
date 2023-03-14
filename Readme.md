Add Following code to disable wp-cron feature in wp-config.php file
    
    define('DISABLE_WP_CRON', true) ;

It will disable the wp-cron and for using system cron follow this process:

    1. Create crontab file with "crontab -u {user} -e"
    2. If not getting specific editor then "EDITOR=nano crontab -u {user} -e" will work.
    3. In crontab file add Following code,
        0 0 * * * wget -q -O - http://yourdomain.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
    It will schedule the cron event every midnight.


    