# Sizuka

Aliyun OSS Static Content Services 

## Introduction

One goddess previously worked in NetEase who is currently working on the system design, recently raised a requirement:

1. Designer can upload files and directories, such as sketch works or demo HTML sites;
1. Those had been permitted could view the contents as if tho FTP, and open any files as if tho HTTP.

To resolve this, I use Aliyun OSS as content storage, as required I have to set the bucket as private.
So I have to provide a service to check user identity and give content to them.
Sizuka is designed for this purpose.

## Deploy

1. Clone this repo, or download project.
1. Run `./composer.phar install` in the project directory.
1. Create a configuration file `config/config.php`. Sample is given as `config/config.sample.php`.
1. Make the cache directory and the log directory writable.
1. Configure the server.

For Nginx, add this in server block.

```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

For Apache, add the rewrite rule in vhost settings or `.htaccess` file under the root of this project.

```apacheconfig
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

## Usage

* Content providers could upload their works at the management page of Aliyun OSS, or use any tool app.
* Content readers could open the deployed Sizuka Site, configure the token, and refresh the object list and view any item.

## Special Pages

### Traditional Version

As Vue.js based page might not be rendered correctly in traditional devices/environments (out-dated iOS, etc.), I made one use pure JavaScript.

### Audio Player

For MP3 audio files, server might process for Range header and respond code 206 and range of bytes. Read code for details.
That might make audio seekable.

## About

This project is published under the License GNU GPLv3.

Copyright 2018 Sinri Edogawa

## Make a Donation!

You can use it free but it is a good thing to make a donation. 

BitCoin:

> 18wCjV8mnepDpLzASKdW7CGo6U8F9rPuV4

Alipay:

![alipay](http://www.everstray.com/resources/img/AlipayUkanokan258.png)

