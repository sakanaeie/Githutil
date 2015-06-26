<?php

require_once dirname(__FILE__) . '/../app/bootstrap.php';

$gc = new \Githutil\Command\Controller\GithubController($argv);
$gc->prList();
