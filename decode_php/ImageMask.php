<?php

    class ImageMask{
        protected $charSize = 16; //每个字符串占用位数
        protected $mixCount = 2; //每个字符所占混淆位数
        protected $lengthSize = 24; //存储加密字符串长度位数
        protected $colors = []; //包含每位rgb的数组
        protected $offset = 0; //当前的rgb的索引

        /**
         * 初始化图片信息
         * ImageMask constructor.
         * @param $file string 图片路径
         */
        public function __construct($file)
        {
            $this->file = $file;
            $size = getimagesize($file);
            $this->width = $size[0];
            $this->height = $size[1];
        }

        /**
         * 解密
         * @return string 解密字符串
         */
        public function revealText(){
            $i = imagecreatefrompng($this->file);
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
            $textLength = $this->readNumber($this->lengthSize); //获取加密字符串长度
            $text = [];
            //获取加密字符串数组
            for ($i = 0; $i < $textLength; $i++) {
                $code = $this->readNumber($this->charSize);
                $text[] = (chr($code));
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
         * @param $number
         * @param $location
         * @param $bit
         * @return int
         */
        protected function setBit ($number, $location, $bit) {
            return ($number & ~(1 << $location)) | ($bit << $location);
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
}