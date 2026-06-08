# Browser-Mode Installer (Built-In PHP Server)

This guide describes how to ship an EXE installer that sets up the app and serves it on a LAN IP/port using the built-in PHP server.

## Goals

- Install the app from an EXE.
- Start a local web server at `http://<LAN-IP>:8020`.
- Keep the server and queue worker running via NSSM.

## Installer Inputs

- Install directory (default: `C:\\Users\\Raihan\\Favorites\\LaravelPos`).
- PHP binary path (use bundled PHP if you ship it, or a system PHP path).
- Web port (default: `8020`).

## Files to Install

- App bundle (Laravel codebase).
- PHP runtime (recommended to bundle).
- NSSM binary (`nssm.exe`).

## Installer Steps (High Level)

1) Copy the app files to the install directory.
2) Copy `nssm.exe` into the install directory (or a fixed tools folder).
3) Register two Windows services:
   - `LaravelPosWeb`
   - `LaravelPosQueueWorker`
4) Before starting the queue worker, register this node with its LAN address:
   ```
   php artisan app:register-self --ip=<LAN-IP> --port={PORT}
   ```
5) Open the browser to `http://<LAN-IP>:8020/setup` after install.

## NSSM Service: Web Server

**Command**

- Application: `{PHP_PATH}`
- Startup directory: `{APP_PATH}`
- Arguments:
  ```
  -S 0.0.0.0:{PORT} -t public server.php
  ```

**Set Auto Start**

```
nssm set LaravelPosWeb DisplayName "Laravel POS Web Server"
nssm set LaravelPosWeb Start SERVICE_AUTO_START
nssm start LaravelPosWeb
```

## NSSM Service: Queue Worker

**Command**

- Application: `cmd.exe`
- Startup directory: `{APP_PATH}`
- Arguments:
  ```
  /C ""{PHP_PATH}" artisan app:register-self --ip=<LAN-IP> --port={PORT} && "{PHP_PATH}" artisan queue:work --sleep=1 --tries=3 --timeout=60"
  ```

**Set Auto Start**

```
nssm set LaravelPosQueueWorker DisplayName "Laravel POS Queue Worker"
nssm set LaravelPosQueueWorker Start SERVICE_AUTO_START
nssm start LaravelPosQueueWorker
```

## Firewall

Allow inbound TCP on the chosen port (default `8020`).

## First-Time Setup

- Visit `http://<LAN-IP>:8020/setup` on the server machine.
- Complete the wizard (app name, node name, default user).

## How Users Access It

- Same machine: `http://127.0.0.1:8020`
- Other devices on LAN: `http://<LAN-IP>:8020`

## Uninstall Steps

```
nssm stop LaravelPosWeb
nssm remove LaravelPosWeb confirm
nssm stop LaravelPosQueueWorker
nssm remove LaravelPosQueueWorker confirm
```
