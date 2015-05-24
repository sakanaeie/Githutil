# githutil

## 概要

* 特定リポジトリのPRを取得し、その内容をメールで転送します

## 環境

* php 5.6.8
* composer 1.0.0-alpha10

## 準備

* config/\*.sample.phpを十分に編集する
	* github.sample.phpのGITHUB_ACCESS_TOKENは、githubの"Settings"->"Personal access token"->"Generate new token"で生成し設定する
* composer install
* 下記crontabを適宜編集し設定する

```crontab
*	*	*	*	*	php <PATH>/Githutil/job/pr_watcher.php
```
