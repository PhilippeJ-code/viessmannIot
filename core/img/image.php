<?php
header("Content-type: image/svg+xml");

$offsetGauche = 34;
$offsetDroit = 15;

$width = (isset($_GET['width']) ? $_GET['width'] : 240);
$height = (isset($_GET['height']) ? $_GET['height'] : 40);

$prog = (isset($_GET['prog']) ? $_GET['prog'] : '');

$title = (isset($_GET['title']) ? $_GET['title'] : ' ');

$n = intval(($width - $offsetGauche - $offsetDroit) / 24);
$taille = $n * 24;

$font= "Segoe UI";
$fontSize = "12";
 
$blanc = "#ffffff";
$orange = "#f7a35c";
$rouge = "#f45b5b";
$bleuclair = "#7cb6ec";
$gris = "#808080";
$grisClair = "#C0C0C0";
$noir = "#000000";

$strY = 12;
$titleY = 35;

function SvgTxt($x, $y, $str, $size, $color, $fontname, $anchor)
{
    echo <<<HEREDOC
	<text x="{$x}" y="{$y}"  
		fill="{$color}"
		stroke="none" 
		font-family="{$fontname}"
		font-size="{$size}" 
		text-anchor="{$anchor}">
		{$str}
	</text>
HEREDOC;
}

function SvgLine($x1, $y1, $x2, $y2, $color)
{
    echo <<<HEREDOC
	<line x1="{$x1}" y1="{$y1}"  
		x2="{$x2}" y2="{$y2}" 
		stroke="{$color}" fill="none" />
HEREDOC;
}

function SvgRect($x1, $y1, $x2, $y2, $color)
{
    $w=$x2-$x1;
    $h=$y2-$y1;
    echo <<<HEREDOC
	<rect x="{$x1}" y="{$y1}"  
		width="{$w}" 
		height="{$h}" 
		fill="{$color}" 
		stroke="none" />
HEREDOC;
}

// debut
echo <<<HEREDOC
<?xml version="1.0"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN"
"http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd">
<svg xmlns="http://www.w3.org/2000/svg"  width="{$width}" height="{$height}">
HEREDOC;

for ($i=0; $i<=24; $i++) {
    $x = $i*$n+$offsetGauche;
    if (($i % 6) == 0) {
        SvgLine($x, 15, $x, 27, $gris);
        if ($i < 12) {
            $str = '0' . $i . ':00';
        } else {
            $str = $i . ':00';
        }
        SvgTxt($x, $strY, $str, $fontSize, $gris, $font, "middle");
    } else {
        SvgLine($x, 20, $x, 27, $grisClair);
    }
}

SvgTxt($titleX, $titleY, $title, $fontSize, $noir, $font, "start");
SvgRect($offsetGauche, 28, $taille + $offsetGauche, 38, $bleuclair);

if ($prog !== '') {
    $progs = explode(",", $prog);
    $n = count($progs);
    if (($n % 3) == 0) {
        for ($i=0; $i<$n; $i+=3) {
            $mode = $progs[$i];
            $start = $progs[$i+1];
            $end = $progs[$i+2];
            $nombres = explode(":", $start);
            $debut = $nombres[0] * 60 + $nombres[1];
            $debut = $offsetGauche + $taille * $debut / 1440;
            $nombres = explode(":", $end);
            $fin = $nombres[0] * 60 + $nombres[1];
            $fin = $offsetGauche + $taille * $fin / 1440;
            if ($mode === 'n') {
                SvgRect($debut, 28, $fin, 38, $orange);
            } else {
                SvgRect($debut, 28, $fin, 38, $rouge);
            }
        }
    }
}

echo "</svg>";
