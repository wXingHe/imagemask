<?php
//加密演示
//    $src = './icon.png';
//    include "ImageMask.php";
//    $image = new ImageMask($src);
//    echo  $image ->hideText('这是一段中文555');


//解密演示
     $src = './mask.png';
    include "ImageMask.php";
    $image = new ImageMask($src);
    $text = $image->revealText();
    var_dump($text);

