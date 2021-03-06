<?php
/**
 * 用于图片隐写的加密与解密,imagemask的php版本
 * User: Wangxh
 * Date: 2018/8/31
 * Time: 15:27
 */


    class UTF8ImageMask{
        protected $charSize = 16; //每个字符串占用位数
        protected $mixCount = 2; //每个字符所占混淆位数
        protected $lengthSize = 24; //存储加密字符串长度位数
        protected $colors = []; //包含每位rgb的数组
        protected $offset = 0; //当前的rgb的索引
        protected $width = 0; //图片宽度
        protected $height = 0; //图片高度

        /**
         * 初始化图片信息
         * ImageMask constructor.
         * @param $file string 图片路径
         */
        public function __construct($file)
        {
            $allowedExt = ['.png', '.jpg']; //定义图片类型,可以扩展,但是下面要填好相应的打开图片函数,建议使用png,默认png,并且转换后的图片输出一律为png
            if(!file_exists($file)){
                throw new Exception('file is not found!');
            }

            //检测图片类型
            $ext = strtolower(substr($file, strrpos($file,'.')));
            if(!in_array($ext,$allowedExt)){
                return '非法的图片格式';
            }
            $this->file = $file;
            $size = getimagesize($file);
            $this->width = $size[0];
            $this->height = $size[1];

            switch($ext){
                case 'jpg':
                case 'jpeg':
                    $i = imagecreatefromjpeg($file);
                    break;
                default:
                    $i = imagecreatefrompng($file);
                    break;
            }

            //将每个rgb重新构建一个数组
            for ($y=0;$y<imagesy($i);$y++) {
                for ($x=0;$x<imagesx($i);$x++) {
                    $rgb = imagecolorat($i,$x,$y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $this->colors[] = $r;
                    $this->colors[] = $g;
                    $this->colors[] = $b;
                }
            }
        }

        /**
         * 隐藏文本
         * @param $text
         */
        public function hideText($text){
            try{
                //检测文本长度
                if (($this->lengthSize + strlen($text) * $this->charSize) > ($this->width*$this->height * 3 * $this->mixCount)) {
                    throw new Exception('text is too long for the image.');
                }
            }catch(Exception $e){
                echo $e->getMessage();die;
            }

            $textLength = mb_strlen($text,'utf-8');
            $this->writeNumber($textLength,$this->lengthSize); //写入加密字符长度
            for($i=0; $i<$textLength; $i++){
                $this->writeNumber($this->unicode_encode(mb_substr($text,$i,1,'utf-8')),$this->charSize);
            }

            $image = imageCreateTrueColor($this->width, $this->height); //创建画布

//            写入像素点
            for($y = 0; $y < $this->height; $y ++){
                for($x = 0; $x < $this->width; $x ++){
                    $redPos = ($this->width * $y + $x) * 3;
                    $greenPos = ($this->width * $y + $x) * 3 + 1;
                    $bluePos = ($this->width * $y + $x) * 3 + 2;
                    $color =imagecolorallocate($image,$this->colors[$redPos],$this->colors[$greenPos], $this->colors[$bluePos]); //获取色彩
                    imagesetpixel($image, $x, $y, $color); //绘点
                }
            }

            header("Content-type: image/png");
            imagepng($image);
            imagedestroy($image);
        }

        /**
         * 解密
         * @return string 解密字符串
         */
        public function revealText(){
            $textLength = $this->readNumber($this->lengthSize); //获取加密字符串长度
            $text = [];
            //获取加密字符串数组
            for ($i = 0; $i < $textLength; $i++) {
                $code = $this->readNumber($this->charSize);
                $hexCode = '&#'.$code.";";
                $text[] = $this -> unicode_decode($hexCode);
            }
            return implode('',$text);
        }

        /**
         * 混淆位数
         */
        protected function imageColorMask(){
            if($this->mixCount < 1) $this->mixCount = 1;
            if($this->mixCount > 5) $this->mixCount = 5;
        }

        /**
         * 获取一位
         * @param $number
         * @param $location
         * @return int
         */
        protected function getBit ($number, $location) {
            return (($number >> $location) & 1);
        }

        /**
         * 将位数对应的数字转换为数字
         * @param $number int 字符代码
         * @param $location int 本地索引
         * @param $bit int(b) 比特位,二进制数字
         * @return int 处理后的二进制
         */
        protected function setBit ($number, $location, $bit) {
            return ($number & ~(1 << $location)) | ($bit << $location);
        }

        /**
         * 写入数字
         * @param $textLength int 文本长度
         * @param $size int 写入位数
         */
        protected function writeNumber($textLength, $size){
            $bits = $this->getBitsFromNumber($textLength, $size); //写入ascii码

            $pos = 0;
            $mix = 0;

            while($pos < count($bits) && $this->offset < count($this->colors)){
                $this->colors[$this->offset] = $this->setBit($this->colors[$this->offset], $mix++, $bits[$pos++]);
                while($mix < $this->mixCount && $pos < count($bits)){
                    $this->colors[$this->offset] = $this->setBit($this->colors[$this->offset], $mix++, $bits[$pos++]);
                }
                $this->offset ++;
                $mix = 0;
            }
        }

        /**
         * 获取ascii
         * @param $size
         * @return int
         */
        protected function readNumber($size){
            $pos = 0;
            $number = 0;
            $mix = 0;
            while($pos < $size && $this->offset < count($this->colors)){
                $bit = $this->getBit($this->colors[$this->offset], $mix++);
                $number = $this->setBit($number, $pos++, $bit);
                while($mix < $this->mixCount && $pos < $size){
                    $bit = $this->getBit($this->colors[$this->offset], $mix++);
                    $number = $this->setBit($number, $pos++, $bit);
                }
                $this->offset ++;
                $mix = 0;
            }
            return $number;
        }

        /**
         * 获取加密的比特
         * @param $number
         * @param $size
         * @return array
         */
        protected function getBitsFromNumber ($number, $size){
            $bits = [];
            for ($i = 0; $i < $size; $i++) {
                $bits[] = $this->getBit($number, $i);
            }
           return $bits;
        }

        /**
         * 将unicode编码转换为字符
         * $decoding 原始字符串的编码，默认utf-8
         * $prefix 编码字符串的前缀，默认"&#"
         * $postfix 编码字符串的后缀，默认";"
         */
        function unicode_decode($unistr, $encoding = 'utf-8', $prefix = '&#', $postfix = ';') {
            $arruni = explode($prefix, $unistr);
            $unistr = '';
            for ($i = 1, $len = count($arruni); $i < $len; $i++) {
                if (strlen($postfix) > 0) {
                    $arruni[$i] = substr($arruni[$i], 0, strlen($arruni[$i]) - strlen($postfix));
                }
                $temp = intval($arruni[$i]);
                $unistr .= ($temp < 256) ? chr(0) . chr($temp) : chr($temp / 256) . chr($temp % 256);
            }
            return iconv('UCS-2', $encoding, $unistr);
        }

        /**
         * @param $str
         * @param string $encoding
         * @param string $prefix
         * @param string $postfix
         * @return string
         */
        function unicode_encode($char, $encoding = 'utf-8') {
            //将字符串拆分
            $char = iconv("UTF-8", "gb2312", $char);
            $cind = 0;
            $arr_cont = array();

            for ($i = 0; $i < strlen($char); $i++) {
                if (strlen(substr($char, $cind, 1)) > 0) {
                    if (ord(substr($char, $cind, 1)) < 0xA1) { //如果为英文则取1个字节
                        array_push($arr_cont, substr($char, $cind, 1));
                        $cind++;
                    } else {
                        array_push($arr_cont, substr($char, $cind, 2));
                        $cind+=2;
                    }
                }
            }
            foreach ($arr_cont as &$row) {
                $row = iconv("gb2312", "UTF-8", $row);
            }

            $unicode = 0;

            //转换Unicode码
            foreach ($arr_cont as $key => $value) {
                $unicode.= base_convert(bin2hex(iconv('utf-8', 'UCS-4', $value)), 16, 10);
            }

            return $unicode;
        }
}