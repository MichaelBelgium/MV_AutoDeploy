# SAMP-Auto-Update-Git

This is a way to auto-deploy a server (gamemode) to your VPS. When pushing to bitbucket (or github) it'll automaticly update the gamemode files on the vps thanks to a webhook. If the sa-mp server detects there's an update (that you pushed to the master branch) - the server will restart itself (with gmx). 

Next to this it also tracks your git issues. When an issues gets created and when the status of it gets changed.

But to get this to work perfectly you'll need to follow the steps below.

## Setup

### Local:
* Create an repository on any git hosting service (bitbucket, github, gitlab, ...)
* Create an empty git repo in your server gamemodes folder by doing: `git init`
* Add the git remote link to the repo: `git remote add <link>`
* Optional: make a .gitignore and ignore all the files except your gamemode files you need (.amx (and .pwn))
* Do a first `git add .` and `git commit -m "Initial commit"` and `git push origin master` so you have your current version of the gamemode on your origin

### Vps (`/web`)
* Import `table.sql` in your database
* Edit the Config class in `deploy.php` so it would work for you. Afterwards put `deploy.php` on the webserver on your vps.
* Add a webhook to your online repository with link `http://vps_ip_or_domain.tld/deploy.php`. (Most likely: repo settings > webhooks > add webhook; It should listen to events: issues (created, updated) and push.)
* Create an empty git repo in the same directory like you did locally: `cd myserver/foo/bar && git init`
  * if using a test server do the same but afterwards `git checkout <ur dev branch specified in Config and existing on remote/local>`
* Also same like locally, add the git remote link: `git remote add <link>` - exactly the same one

### Server (`/samp`)
* Download `MV_AutoDeploy` in your includes folder
* Put `#include <MV_AutoDeploy>` in your gamemode or filterscript.
* U can use the following callbacks like the example script (`serverexample.pwn`)
```
OnServerUpdateDetected(id, hash[], shorthash[], message[])
OnUpcomingUpdateDetected(updateid, hash[], shorthash[], message[])
OnServerIssueCreated(issueid, title[], priority[], kind[])
OnServerIssueStatusChange(issueid, title[], oldstatus[], newstatus[])
```
* In general: the requirements are the [SQL plugin (R41-2)](https://github.com/pBlueG/SA-MP-MySQL/releases) and zcmd include.

Follow these in order, first we set up things locally then on vps and afterwards on our server. After these steps you should be good to go.

## To-do
- N/A

## Extra commands

[MV]_AutoDeploy gives you extra commands: /updates and /issues - Lists the last 10 updates/issues from the server/git

![updates command](http://puu.sh/vOVWv.jpg)

![issue command](https://puu.sh/wK8su.jpg)