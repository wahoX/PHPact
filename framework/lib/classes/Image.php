<?php
/**
 * Created by PhpStorm.
 * User: s.jutzi
 * Date: 11.01.19
 * Time: 11:07
 */

class Image
{
    private function setTransparency(&$img)
    {
        imagesavealpha($img, true);
        $trans_color = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $trans_color);
    }

    /**
     * Werte für $resize:
     *
     * false: keine Änderung der Größe, nur Neuberechnung und Speichern des Bildes.
     * "width": Nur die Breite ist interessant (maximale Breite)
     * "height": Nur die Höhe ist interessant (maximale Höhe)
     * "both": maximale Kantenlänge (egal ob Breite oder Höhe)
     *
     * @param $image String Pfad zum Bild
     * @param $finalname String Dateiname, unter dem das Bild gespeichert werden soll (inkl Pfad)
     * @param $type String Dateityp. Aktuell gehen jpg, jpeg oder png. Alles andere wird zu jpg
     * @param $resize String Art der Größenänderung des Bildes
     * @param $max Integer maximale Größe in Pixeln
     *
     * @return Array Infos zum gespeicherten Bild: Breite, Höhe. Wenn Fehler auftritt, dann return false
     **/
    public function saveImage($image, $finalname, $type="jpg", $resize=false, $max = 200)
    {
        ini_set('memory_limit', '128M');
        $size = getimagesize($image);
        if (!$size) return false;

        switch ($size["mime"])
        {
            case "image/x-png" :
            case "image/png" :
                $avatar = imagecreatefrompng($image);
                break;
            case "image/pjpeg":
            case "image/jpeg":
            case "image/jpg":
                $avatar = imagecreatefromjpeg($image);
                break;
            case "image/gif" :
                $avatar = imagecreatefromgif($image);
                break;
            default :
                $this->app->addError("Das Bild hat ein ungültiges Format.");
                return false;
        }

        $w = $thumbwidth = $size[0];
        $h = $thumbheight = $size[1];

        if ($resize == "width" && $w > $max) {
            $w = $max;
            $h = $thumbheight * $max / $thumbwidth;
        }
        if ($resize == "height" && $h > $max) {
            $h = $max;
            $w = $thumbwidth * $max / $thumbheight;
        }
        if ($resize == "both" && ($w > $max || $h > $max)) {
            if ($thumbwidth > $thumbheight) {
                $w = $max;
                $h = $thumbheight * $max / $thumbwidth;
            } else {
                $h = $max;
                $w = $thumbwidth * $max / $thumbheight;
            }
        }
        $thumbwidth = intval($w);
        $thumbheight = intval($h);

        $bg = imagecreatetruecolor ($thumbwidth , $thumbheight);
        if ($type == "png") {
            $this->setTransparency($bg);
        } else {
            $white = imagecolorallocate($bg, 255, 255, 255);
            imagefilledrectangle($bg, 0, 0, $thumbwidth, $thumbheight, $white);
        }

        imagecopyresampled($bg, $avatar, 0, 0, 0, 0, $thumbwidth, $thumbheight, $size[0], $size[1]);
        switch ($type) {
            case "jpg" :
            case "jpeg" :
                imagejpeg($bg, $finalname.".".$type, 90);
                break;
            case "png" :
                $this->setTransparency($bg);
                imagepng($bg, $finalname.".png");
                break;
            default :
                imagejpeg($bg, $finalname.".jpg", 90);
                break;
        }

        return array("width" => $thumbwidth, "height" => $thumbheight);
    }

}