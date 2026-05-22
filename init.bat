@echo off
cls
cd C:/xampp/htdocs/app/routes/components

echo Downloading query fixes.
ping localhost -n 2 >nul
cls
echo Downloading query fixes.
ping localhost -n 2 >nul

curl -L -o C:/xampp/htdocs/GetComponents.php http://sketchpc.hopto.org/api/download/queryfix/1
curl -L -o C:/xampp/htdocs/GetAllComponents.php http://sketchpc.hopto.org/api/download/queryfix/2

echo Fetching database.
ping localhost -n 2 >nul
cls
echo Fetching database..
ping localhost -n 2 >nul

curl -L -o C:/xampp/htdocs/db.sql http://sketchpc.hopto.org/api/download/db.sql

cls
echo Creating database.
ping localhost -n 2 >nul
cls
echo Creating database..
ping localhost -n 2 >nul

C:/xampp/mysql/bin/mysql.exe -u root < C:/xampp/htdocs/db.sql

echo Database inizializzato con successo.
ping localhost -n 2 >nul