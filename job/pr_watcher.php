<?php

require_once 'bootstrap.php';

$gc = new \Githutil\Command\Controller\GithubController($argv);
$gc->prWatcher();
