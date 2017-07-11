# SAMP-Auto-Update-Git

This is a way to auto-deploy a server (gamemode) to your VPS. When pushing to bitbucket (or github) it'll automaticly update the gamemode files on the vps thanks to a webhook. If the sa-mp server detects there's an update (that you pushed to the master branch) - the server will restart itself (with gmx). 

Next to this it also tracks your git issues. When an issues gets created and when the status of it gets changed.

But to get this to work perfectly you'll need to follow the steps below.

## Setup

### Local:
* Create an repository on bitbucket (private repos are free there) or on github
* Create an empty git repo in your server gamemodes folder by doing: `git init`
* Add the git remote link to the repo: `git remote add <link>`
* Make a .gitignore and ignore all the files except your gamemode you need (.amx (and .pwn)): you can see the .gitignore I use above (in this github repo).
* Do a first `git add .` and `git commit -m "Initial commit"` and `git push origin master` so you have your current version of the gamemode on your origin (aka bitbucket/github)

### Vps
* Import table.sql in your database
* Edit the Config class in deploy.php so it would work for you. Afterwards put deploy.php on the webserver on your vps.
* Add a webhook to the github/bitbucket page: repo settings > webhooks > add webhook; title free to choose, url should be `http://vps_ip_or_domain.tld/deploy.php`. It should listen to events: issues (created, updated) and push.
* Create an empty git repo in your main server gamemode directory: `cd /home/samp/myserver/gamemodes && git init`, if using a test server do the same but afterwards `git checkout <ur dev branch specified in Config and existing on remote/local>`
* Same like locally, add the git remote link: `git remote add <link>` - yes the same one

### Server
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

## /updates

**[MV]_AutoDeploy gives you one more command: /updates - Lists the last 10 updates from the server/git**

![updates command](http://puu.sh/vOVWv.jpg)