# PHP Plane Counter

This is a small collection of scripts that listens to a streaming 
[dump1090](https://github.com/MalcolmRobb/dump1090) server and can display a
graph of the count of each aircraft type that sent a message.

[MergePHP](https://www.mergephp.com/) needed lightning talks and I thought this
would be an interesting small project that would fit in the time allotment.

This guide assumes:
* You have a Redis server [installed](https://redis.io/docs/latest/operate/oss_and_stack/install/archive/install-redis/)
  on localhost (or anywhere if you change the hostname in the script)
* You have the [PHPRedis extension](https://github.com/phpredis/phpredis)
  installed
* A server running dump1090 is installed and running.
  [adsb.im](https://adsb.im/) is the suggested method for installing a new
  server

## Installing

1. Clone the repo and install dependencies:
```bash
git clone https://github.com/cookieguru/php-plane-counter
cd php-plane-counter
composer install
```
2. Edit line 28 of `read_stream.php` with the correct address to the dump1090
   server.  Edit the Redis connection string on line 24 as necessary
3. Edit line 6 of `seed_database.php` with the correct connection string for the
   database
4. Edit lines 4 and 5 of `pie_chart.php` with the correct credentials to Redis
   and the database

## Running

1. Start the stream reader and send it to the background
```bash
nohup php read_stream.php &
```
2. Import aircraft types by running `seed_database.php` via command line or
   browser
3. After messages are received view `pie_chart.php` in a browser
