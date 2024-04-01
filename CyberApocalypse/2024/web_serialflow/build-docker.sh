#!/bin/sh
docker rm -f web_serialflow
docker build -t web_serialflow .
docker run --name=web_serialflow --rm -p1337:1337 -p11211:11211 -it web_serialflow