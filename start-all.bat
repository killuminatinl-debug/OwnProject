@echo off
setlocal
cd /d "%~dp0"
echo Starting Travian Kingdom services from: %CD%

where node >nul 2>nul
if errorlevel 1 (
  echo ERROR: Node.js is not installed or not on PATH. Install Node.js LTS, then reopen this window.
  pause
  exit /b 1
)

set "PHP_EXE=%~dp0..\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php.exe"
if not exist "%PHP_EXE%" (
  where php >nul 2>nul
  if errorlevel 1 (
    echo ERROR: PHP was not found. Expected XAMPP at the parent folder or PHP on PATH.
    pause
    exit /b 1
  )
)

if not exist "server-config.json" (
  echo ERROR: server-config.json is missing. Run install.php first.
  pause
  exit /b 1
)
if not exist "server\app2.js" (
  echo ERROR: server\app2.js is missing.
  pause
  exit /b 1
)
if not exist "server_lobby\app.js" (
  echo ERROR: server_lobby\app.js is missing.
  pause
  exit /b 1
)
if not exist "server2\server.php" (
  echo ERROR: server2\server.php is missing.
  pause
  exit /b 1
)

echo Starting game socket service on port 8081...
start "Travian game socket" cmd /k "cd /d "%~dp0server" ^&^& node app2.js"
echo Starting lobby socket service on port 8082...
start "Travian lobby socket" cmd /k "cd /d "%~dp0server_lobby" ^&^& node app.js"
echo Starting PHP automatic game loop...
start "Travian automatic game loop" cmd /k "cd /d "%~dp0" ^&^& "%PHP_EXE%" server2/server.php"
echo.
echo Services were launched. Keep all three service windows open while testing.
echo If a window reports a database error, check install.php credentials and server-config.json.
