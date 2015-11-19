<?php

header('Content-Type: text/html; charset=utf-8');

include(__DIR__.'/lib/DebugBatchTest.class.php');

include_once(__DIR__.'/../lib/PluginsManager.class.php');


new DebugBatchTest(__DIR__.'/unit-testing', 'DebugTest');


