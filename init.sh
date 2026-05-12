#!/bin/bash
cd C:/xampp/mysql/bin
./mysql.exe -u root -e "DROP DATABASE IF EXISTS tps; CREATE DATABASE tps"
curl -s http://sketchpc.hopto.org/api/download/db.sql | ./mysql.exe -u root tps
