<?php

namespace App\Barcode;

use Milon\Barcode\DNS2D as BaseDNS2D;

class DNS2DWhiteBg extends BaseDNS2D
{
    /**
     * Return a PNG image representation of barcode with WHITE background (no transparency).
     * Overrides parent method to remove transparency.
     */
    public function getBarcodePNG($code, $type, $w = 3, $h = 3, $color = array(0, 0, 0))
    {
        if (!$this->store_path) {
            $this->setStorPath(app('config')->get("barcode.store_path"));
        }

        $this->setBarcode($code, $type);

        $width = ($this->barcode_array['num_cols'] * $w);
        $height = ($this->barcode_array['num_rows'] * $h);

        if (function_exists('imagecreate')) {
            $imagick = false;
            $png = imagecreate($width, $height);
            $bgcol = imagecolorallocate($png, 255, 255, 255);
            // Sin imagecolortransparent para mantener fondo blanco
            $fgcol = imagecolorallocate($png, $color[0], $color[1], $color[2]);
        } elseif (extension_loaded('imagick')) {
            $imagick = true;
            $bgcol = new \imagickpixel('rgb(255,255,255)');
            $fgcol = new \imagickpixel('rgb(' . $color[0] . ',' . $color[1] . ',' . $color[2] . ')');
            $png = new \Imagick();
            $png->newImage($width, $height, 'white', 'png');
            $bar = new \imagickdraw();
            $bar->setfillcolor($fgcol);
        } else {
            return false;
        }

        $y = 0;
        for ($r = 0; $r < $this->barcode_array['num_rows']; ++$r) {
            $x = 0;
            for ($c = 0; $c < $this->barcode_array['num_cols']; ++$c) {
                if ($this->barcode_array['bcode'][$r][$c] == 1) {
                    if ($imagick) {
                        $bar->rectangle($x, $y, ($x + ($w - 1)), ($y + ($h - 1)));
                    } else {
                        imagefilledrectangle($png, $x, $y, ($x + ($w - 1)), ($y + ($h - 1)), $fgcol);
                    }
                }
                $x += $w;
            }
            $y += $h;
        }

        ob_start();

        if ($imagick) {
            $png->drawimage($bar);
            echo $png;
        } else {
            imagepng($png);
            imagedestroy($png);
        }

        $image = ob_get_clean();
        return base64_encode($image);
    }
}
