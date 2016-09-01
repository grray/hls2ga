<?php

$conf['tracking_id'] = "UA-XXXX-X";
$conf['event_category'] = "video";
/*
Next *_pos config parameters specify where certain item is located in nginx
log file. Index starts from zero. Default values are for followin config

    log_format tracking '$remote_addr - $remote_user [$time_local] '
                        '"$request" $status $body_bytes_sent '
                        '"$http_referer" "$http_user_agent" '
                        '"$uid_got"';
    access_log logs/access.log tracking;

Unique cookie is logged at end. Use $cookie__ga for google analytics cookie 
(if it is installed on your site), or $cookie_COOKIENAME if your site set it's 
own cookie, or $uid_got if you use ngx_http_userid_module.
*/
// ip position
$conf['ip_pos'] = 0;
// http request position
$conf['request_pos'] = 5;
// user agent position
$conf['ua_pos'] = 9;
// user agent position
$conf['referer_pos'] = 8;
// file size position
$conf['size_pos'] = 7;
// unique cookie position. Needed for tracking user. Set it in your application
// or with nginx module ngx_http_userid_module.
$conf['cookie_pos'] = 10;

