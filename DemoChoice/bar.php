<?php 
/* Dynamic Bar Chart */
/* (C) 2001 Dave Robinson */
/* See DemoChoice-Readme.txt for license info */
/* Requires version 2 of the GD library in directory given by */
/* extension_dir variable in PHP config file. */
/* If this is undefined and you can't change it, put the library in the script folder. */
/* color=rrggbb&height=12&width=120 */

// include GD2 library
 if (!in_array("gd",get_loaded_extensions())) {
 if (ini_get("extension_dir")===false)
 {
  if (array_key_exists("SCRIPT_FILENAME",$_SERVER))
  { $Path=str_replace("\\\\","/",$_SERVER["SCRIPT_FILENAME"]); }
  else
  { $Path=str_replace("\\\\","/",$_SERVER["PATH_TRANSLATED"]); }
  if (!(strrpos($Path,"/")===false))
  { $Path=substr($Path,0,strrpos($Path,"/")+1); }
  dl($Path."php_gd2.dll");
 }
 else { dl("php_gd2.dll"); }
 }
 
/* read querystring */
if (array_key_exists("color",$_GET))
{
 $value=$_GET["color"];
 $red=hexdec(substr($value,0,2));
 $grn=hexdec(substr($value,2,2));
 $blu=hexdec(substr($value,4,2));
}
else
{ $red=0; $grn=128; $blu=255; }

if (array_key_exists("height",$_GET))
{ $ht=1*$_GET["height"]; }
else
{ $ht=12; }

if (array_key_exists("width",$_GET))
{ $wd=1*$_GET["width"]; }
else
{ $wd=600; }

Header( "Content-type: image/png");

/* create image */
$image = imagecreate($wd,$ht); 

/* create colors */
$color1 = ImageColorAllocate($image,$red,$grn,$blu);

/*  create white background*/
ImageFilledRectangle($image,0,0,$wd,$ht,$color1);

/*  render image */
ImagePNG($image);

/*clean up memory */
ImageDestroy($image); 
?>
