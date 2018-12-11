#imagemask的PHP版本,支持utf8的加解密,和js可以互通(原版的仅支持ascii编码,解析中文会乱码)
* 有个小问题,php默认背景黑色,原来没有颜色或白色的地方会显示为白色
##解密
```php
    $src = './mask.png'; //定义图片路径
    include "ImageMask.php"; //引入imagemask类
    $image = new ImageMask($src); //实例化该类
    $text = $image ->revealText(); //解密即可返回加密字符串
    var_dump($text); //查看字符串
```
    
##加密
```php
   $src = './icon.png'; //原始图片路径
   include "ImageMask.php"; //引入imagemask类
   $image = new ImageMask($src); //实例化对象
   echo  $image ->hideText('这是一段中文666'); //返回图片
```
    