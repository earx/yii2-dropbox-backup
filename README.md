Yii2-dropbox-backup
===================
Yii2 console command for making site backups and upload it to your dropbox account

Installation
---

Add to composer.json in your project
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/earx/yii2-dropbox-backup"
        }
    ]
}
```
Run
```code
php composer.phar require "earx/dropbox-backup"
```
or


Add to composer.json in your project
```json
{
	"require": {
  		"earx/dropbox-backup": "*"
	}
}
```
then run command
```code
php composer.phar update
```

# Configurations
---

[Create new dropbox application](https://www.dropbox.com/developers/apps)

