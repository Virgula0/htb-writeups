# Category: Web Challenges

Challenge is very easy.
Since redirection is allowed 


```py
            su = safeurl.SafeURL()
            opt = safeurl.Options()
            opt.enableFollowLocation().setFollowLocationLimit(0)
            su.setOptions(opt)
            su.execute(url)
```

we can bypass an url which executes the following rederict:


```php
<?php
header("Location: http://127.0.0.1:1337/secret");
?>
```

The problem here is that internally, the following default blacklist/whitelist is defined:


```py
        self._follow_location = False
        self._follow_location_limit = 0
        self._send_credentials = False
        self._pin_dns = False
        self._lists = {
                "whitelist": {
                    "ip": [],
                    "port": ["80", "443", "8080"],
                    "domain": [],
                    "scheme": ["http", "https"]},
                "blacklist": {
                    "ip": ["0.0.0.0/8", "10.0.0.0/8", "100.64.0.0/10", "127.0.0.0/8", "169.254.0.0/16",
                        "172.16.0.0/12", "192.0.0.0/29", "192.0.2.0/24", "192.88.99.0/24", "192.168.0.0/16",
                        "198.18.0.0/15", "198.51.100.0/24", "203.0.113.0/24", "224.0.0.0/4", "240.0.0.0/4"],
                    "port": [],
                    "domain": [],
                    "scheme": []}
```

So only `80,443 and 8080` ports can be passed to the url. 
The `"10.0.0.0/8"` is banned so i cannot insert my ip `10.10.16.75`. but using a urll shortner which allows to insert 127.0.0.1 as redirect url or using a webhook which allows to insert an header location in response leads to solving the challenge.


This url shortner allowed me to do the trick:


```
https://cutt.ly/
```

which redirects to:


```
http://127.0.0.1:1337/secret
```

Anyway the library used is most probably vulnerbale to some dns rebindings since it does not provide dns pinning proper checks.