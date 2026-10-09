@ECHO off
REM Run this file from the project root. PHP must be on PATH; Node.js and the npm dependencies are required for the original services.
where php >nul 2>nul
if errorlevel 1 (
  echo PHP CLI was not found. Add your XAMPP php folder to PATH or run this from a configured shell.
  pause
  exit /b 1
)
where node >nul 2>nul
if errorlevel 1 (
  echo Node.js was not found. Install Node.js and run npm install in server and server_lobby first.
  pause
  exit /b 1
)
start "Travian game service" cmd /k php server2/server.php
start "Travian game API" cmd /k node server/app2
start "Travian lobby API" cmd /k node server_lobby/app
