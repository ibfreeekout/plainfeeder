# plainfeeder

Simple RSS Feed Reader designed in PHP. Mostly a learning exercise, but hopefully a useful service too!

## Planned Features

* Basic User authentication
* Retrieve RSS feeds using best practices for HTTP calls
  * Honor caching headers and properly perform If-Modified-Since and If-None-Match calls (if Last-Modified and ETag response headers are provided)
  * If multiple users add the same feed, don't try to fetch the feed multiple times, just use what we already have
  * Honor rate limits and other status codes imposed by the feed owner
* Sane refresh times for feeds to prevent overloading the source server with multiple requests
* Plain site layout, it's mostly text-based content so it should use a text-based reader

### Technologies

I'm not an expert developer by any stretch of the imagination. I know Python fairly well and I've worked with PHP for a few years but I've never used any frameworks for anything. I don't intend to start that now either. This site is going to be built with some fairly basic technology - it doesn't need much to be honest.

* Nginx Web Server
  * Let's Encrypt will be the certificate provider
* PHP-FPM
  * Various required modules will also be installed and used as necessary
* MariaDB
  * Store user credentials
  * Store feed information

Once I have a better understanding of the overall design of these various systems, I'll be sure to include a little write up of how things are laid out and the reasoning for that. I will again preface with this - I am not a developer by trade, but I wanted a project to work on and I've been really enjoying finding RSS feeds lately, so figured this would be a fun one to start with!

## Contact Me

If you'd like to reach out to me, you can either email at the address in my Github profile or at [chris@plainfeeder.com](mailto:chris@plainfeeder.com)
