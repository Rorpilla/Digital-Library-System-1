<?php
require __DIR__ . '/init.php';
session_unset();
session_destroy();
redirect('index.php');
