<?php
/**
 * QRcode.php
 *
 * Created by ivanets
 */

namespace PHPQRCode;

class QRPrettyRender
{
    const EYE_SIZE = 7;
    const LOGO_PATH = 'logo_squarq_191x191.png';

    const TOP_LEFT_EYE = 0;
    const BOTTOM_LEFT_EYE = 1;
    const TOP_RIGHT_EYE = 2;
    const QR_POINT = 11;
    const EYE_CENTER = 3;



    private $qr_data = [];
    private $im = null;

    private $width = 0;
    private $height = 0;

    private $eyesDefs = [
        self::TOP_LEFT_EYE =>     [[0,0],[0,0]],
        self::BOTTOM_LEFT_EYE =>  [[0,0],[0,0]],
        self::TOP_RIGHT_EYE =>    [[0,0],[0,0]]
    ];

    private $pixelRatio = 10;


    private $eyeCentersCoords = [];


    function __construct($qr_data, $ratio = 10)
    {
        $this->qr_data = $qr_data;
        $this->pixelRatio = max($this->pixelRatio, $ratio);
        

        $this->width = count($this->qr_data);
        if(isset($this->qr_data[0])){
            $this->height = strlen($this->qr_data[0]);
        }

        $this->im = imagecreate($this->width * $this->pixelRatio, $this->height * $this->pixelRatio);

        $this->white = imagecolorallocate($this->im,0xFF,0xFF,0xFF);
        $this->black = imagecolorallocate($this->im,0x17,0x17,0x17);
        $this->red = imagecolorallocate($this->im,0xB1,0x00,0x0F);

        imagefilledrectangle($this->im, 0, 0, $this->width * $this->pixelRatio, $this->height * $this->pixelRatio, $this->white);

        //EYES: =>>
        //top left
        $this->eyesDefs[self::TOP_LEFT_EYE] = [[0, self::EYE_SIZE - 1],[0, self::EYE_SIZE - 1]];
        //bottom left
        $this->eyesDefs[self::BOTTOM_LEFT_EYE] = [[$this->height - self::EYE_SIZE, $this->height - 1],[0, self::EYE_SIZE - 1]];
        //top right
        $this->eyesDefs[self::TOP_RIGHT_EYE] = [[0, self::EYE_SIZE - 1],[$this->width - self::EYE_SIZE, $this->width - 1 ]];

    }



    private function isEye($x, $y){

        foreach ($this->eyesDefs as $position => $eye) {
            $eyeX = $eye[0];
            $eyeY = $eye[1];
            
            if(!isset($this->eyeCentersCoords[$position])){
                $this->eyeCentersCoords[$position] = [];
            }

            if( $x >= $eyeX[0] && $x <= $eyeX[1] ) {
                if( $y >= $eyeY[0] && $y <= $eyeY[1] ) {
                    //now it is eye
                    if( $x >= $eyeX[0]+2 && $x <= $eyeX[1]-2 ) {
                        if( $y >= $eyeY[0]+2 && $y <= $eyeY[1]-2 ) {
                            //it is an eye center
                            $this->eyeCentersCoords[$position][] = [$x, $y];
                            return self::EYE_CENTER;
                        }
                    }

                    //isEye border
                    return $position;
                } else {
                    continue;
                }
            } else {
                continue;
            }
        }
        return self::QR_POINT;
    }



    private function renderEye($x, $y) {
        imagefilledrectangle(
            $this->im,
            $x * $this->pixelRatio,
            $y * $this->pixelRatio,
            $x * $this->pixelRatio + $this->pixelRatio,
            $y * $this->pixelRatio + $this->pixelRatio,
            $this->black
        );
    }

    private function renderPoint($x, $y) {
        imagefilledellipse(
            $this->im,
            $x * $this->pixelRatio + $this->pixelRatio / 2,
            $y * $this->pixelRatio + $this->pixelRatio / 2,
            $this->pixelRatio,
            $this->pixelRatio, 
            $this->red
        );
    }

    private function renderEmpty($x, $y) {
        imagefilledrectangle(
            $this->im,
            $x * $this->pixelRatio,
            $y * $this->pixelRatio,
            $x * $this->pixelRatio + $this->pixelRatio,
            $y * $this->pixelRatio + $this->pixelRatio,
            $this->white
        );
    }



    private function renderCenters() {
        foreach ($this->eyeCentersCoords as $position => $coords) {
            switch ( $position ) {
                case self::TOP_LEFT_EYE:
                case self::TOP_RIGHT_EYE:
                case self::BOTTOM_LEFT_EYE:
                    $from = $coords[0];
                    $to = $coords[8];
                    imagefilledellipse(
                        $this->im,
                        ( $from[0] + 0.5 ) * $this->pixelRatio + $this->pixelRatio,
                        ( $from[1] + 0.5 ) * $this->pixelRatio + $this->pixelRatio,
                        ( $to[0] - $from[0] ) * ( $this->pixelRatio * 1.5 ),
                        ( $to[1] - $from[1] ) * ( $this->pixelRatio * 1.5 ),
                        $this->black
                    );
                break;
            }
        }
    }

    private function renderLogo(){
        $logo = imagecreatefrompng('https://waiterok.com/img/'.self::LOGO_PATH);
        
        $halfSize = $this->width*$this->pixelRatio/10;

        imagecopyresampled(
            $this->im,
            $logo,
            round(($this->width * $this->pixelRatio)/2-$halfSize),
            round(($this->height * $this->pixelRatio)/2-$halfSize),
            0,
            0,
            $halfSize*2,
            $halfSize*2,
            191,
            191
        );
        imagedestroy($logo);
    }

    public function render($filename = false){

        for ($line_number=0; $line_number < $this->height; $line_number++) { 
            
            $line = $this->qr_data[$line_number];
            for ($column_number=0; $column_number < $this->width; $column_number++) { 
                $point = $line[$column_number];

                //IS HAS DATA
                if($point){
                    switch ( $this->isEye($line_number, $column_number ) ) {
                        case self::TOP_LEFT_EYE:
                        case self::TOP_RIGHT_EYE:
                        case self::BOTTOM_LEFT_EYE:
                            $this->renderEye($line_number, $column_number);
                            break;
                        case self::EYE_CENTER:
                            $this->renderEmpty($line_number, $column_number);
                            break;
                        case self::QR_POINT:
                            $this->renderPoint($line_number, $column_number);
                            break;
                        default:
                            $this->renderEmpty($line_number, $column_number);
                            break;
                    }
                } else {
                    $this->renderEmpty($line_number, $column_number);
                }
            }
        }
        $this->renderCenters();
        $this->renderLogo();

        if(!$filename) {
            header('Content-Type: image/png');
            imagepng($this->im);
        } else {
            imagepng($this->im, $filename);
        }


    }    

}