<?php
//解密
    $src = './mask.png';
    include "ImageMask.php";
    $image = new ImageMask($src);
    $text = $image ->revealText();
    var_dump($text);

//加密
//    $src = './icon.png';
//    include "ImageMask.php";
//    $image = new ImageMask($src);
//    echo  $image ->hideText('bbbbbbbbb'); //返回图片