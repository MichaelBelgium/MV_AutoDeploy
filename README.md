# SAMP-Auto-Update-Git

This is a way to auto-deploy a server (gamemode) to your VPS. When pushing to bitbucket (or github) it'll automaticly update the gamemode files on the vps thanks to a webhook.
But to get this to work perfectly you'll need to follow the steps below.

## Setup

### Local:
* Create an repository on bitbucket (private repos are free there) or on github
* Create an empty git repo in your server gamemodes folder by doing: `git init`
* Add the git remote link to the repo: `git remote add <link>`
* Make a .gitignore and ignore all the files except your gamemode you need (.amx (and .pwn)): you can see the .gitignore I use above (in this github repo).
* Do a first `git add .` and `git commit -m "Initial commit"` and `git push origin master` so you have your current version of the gamemode on your origin (aka bitbucket/github)

### Vps
* Put deploy.php on your webserver on the vps
* Add a webhook to the github/bitbucket page: repo settings > webhooks > add webhook; title free to choose, url should be `http://vps_ip_or_domain.tld/deploy.php`
* Create an empty git repo in your server gamemode directory: `cd /home/samp/myserver/gamemodes && git init`
* Same like locally, add the git remote link: `git remote add <link>` - yes the same one

Follow these in order, first we set up things locally then on vps. After these steps you should be good to go.