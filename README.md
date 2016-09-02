# hls2ga
This tool collects HLS viewer stats from Nginx access logs and sends it to Google Analytics.
## Usage
Clone it somewhere
```
git clone https://github.com/grray/hls2ga.git
```
Copy config.sample.php into config.php, adjust settings in config.php (there is comments there). Run it like this
```
php hls2ga.php /path/to/your/nginx/access.log
```
It should be pointed to log file, which Nginx is writing right now. It process access log in realtime, so it should be running all the time. Stats will be reported as event, watch them in "Realtime - Events" or "Behavior - Events" in Google Analytics.
