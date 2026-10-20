@echo off
title Athlytic - site no ar
rem Publica o site Athlytic na internet atraves de um tunel da Cloudflare.
rem So a pasta do projeto fica visivel (o phpMyAdmin e o resto do XAMPP nao).
rem Para desligar: fechar esta janela (ou Ctrl+C).

cd /d "%~dp0.."
set "PASTA=%~dp0"
set "PHP=C:\xampp\php\php.exe"

if not exist "%PASTA%cloudflared.exe" (
  echo A descarregar o cloudflared ^(so da primeira vez^)...
  curl -L -o "%PASTA%cloudflared.exe" https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe
)

tasklist /fi "imagename eq mysqld.exe" | find /i "mysqld.exe" >nul
if errorlevel 1 (
  echo A ligar o MySQL do XAMPP...
  start "MySQL Athlytic" /min "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
)

echo A ligar o servidor do site na porta 8081...
start "Servidor Athlytic" /min "%PHP%" -S 127.0.0.1:8081 -t . publicar\router.php

echo.
echo O link do site aparece em baixo, a terminar em .trycloudflare.com
echo Deixa esta janela aberta enquanto quiseres o site no ar.
echo.
"%PASTA%cloudflared.exe" tunnel --no-autoupdate --url http://127.0.0.1:8081 --logfile "%PASTA%tunel.log"

taskkill /fi "WINDOWTITLE eq Servidor Athlytic*" /f >nul 2>&1
