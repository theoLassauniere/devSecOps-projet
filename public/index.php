<?php

use App\ImageCreator;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

header("Content-type: image/png");


/* Edit this to your preferences */
$yourColor = [128, 128, 128];
$yourColor2 = [60, 80, 57];
$yourText = "DEVOPS";
$yourText2 = "Une superbe image";


/* Don't edit below this line */
$image = new ImageCreator($yourColor, $yourColor2, $yourText, $yourText2);
$image->createImage();
