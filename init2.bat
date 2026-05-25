@echo off
cls
cd C:/xampp/htdocs/

echo Fetching database.
ping localhost -n 2 >nul
cls
echo Fetching database..
ping localhost -n 2 >nul

cd C:/xampp/htdocs/
curl -L -o C:/xampp/htdocs/db.sql http://sketchpc.hopto.org/api/download/db.sql

echo Creating database.
ping localhost -n 2 >nul
cls
echo Creating database..
ping localhost -n 2 >nul

C:/xampp/mysql/bin/mysql.exe -u root < C:/xampp/htdocs/db.sql

echo Database inizializzato con successo.
ping localhost -n 2 >nul