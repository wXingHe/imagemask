#imagemask的PHP版本
##暂未实现加密功能,只实现了解密功能
    $src = './mask.png'; //定义图片路径
    include "ImageMask.php"; //引入imagemask类
    $image = new ImageMask($src); //实例化该类
    $text = $image ->revealText(); //解密即可返回加密字符串
    var_dump($text); //查看字符串