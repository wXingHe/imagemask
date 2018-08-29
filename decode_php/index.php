<?php
    $src = './mask.png';
    include "ImageMask.php";
    $image = new ImageMask($src);
    $text = $image ->revealText();
    var_dump($text);