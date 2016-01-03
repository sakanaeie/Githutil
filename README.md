# Githutil

## 概要

* 特定リポジトリのPRを取得し、その内容をメールで転送します
* 外製チャットボットにメール送信 or APIを利用し、チャットに転写させることを目的として設計されています
  * slackのIncomingWebHooksを利用することもできます

## 環境

* php 5.6.8
* composer 1.0.0-alpha10

## 準備

* config/\*.sample.phpを十分に編集する
	* github.sample.phpのGITHUB_ACCESS_TOKENは、githubの"Settings"->"Personal access token"->"Generate new token"で生成し設定する
* composer install
* 下記crontabを適宜編集し設定する

```crontab
*/2	0-2,10-23	*	*	*	php <PATH>/Githutil/job/pr_watcher.php <OWNER> <REPO>
0	10,14,18	*	*	1-5	php <PATH>/Githutil/job/pr_list.php <OWNER> <REPO>
0	18	*	*	1-5	php <PATH>/Githutil/job/not_pr_list.php <OWNER> <REPO>
```

## 各ジョブについて

* not_pr_list.php
  * PRしてないブランチ一覧を取得し、メール送信します
  * ブランチ消し忘れ検知を目的とします
* pr_list.php
  * PR一覧を取得し、メール送信します
  * 各PRに、メンバーのレビュー状況も合わせて記載します
  * 各PRが、誰のレビュー待ちないのか把握することを目的とします
* pr_watcher.php
  * merge/closeを検知し、メール送信します
  * PRコメントを取得し、メール送信します
  * PRコメント内の特殊な記法を解釈し、記載内容を変化させます
    * ```:+1:``` を付けると、先頭の数字がインクリメントされ、PRページのURLが記載されます
    * ```:0:``` を付けると、先頭の数字が0になります
    * ```:bow:``` を付けると、PRページのURLが記載されます
    * ```:memo:``` を付けると、メール送信されません (自己レビュー時のメモや、レビュー者向け補足の記述用)
