<?php

require_once dirname(__FILE__) . '/../app/bootstrap.php';

$gc = new \Githutil\Controller\GithubController();
$gc->index();
