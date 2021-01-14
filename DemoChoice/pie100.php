<?php 
/* Dynamic Pie Chart */
/* (C) 2002 Dave Robinson */
/* See DemoChoice-Readme.txt for licensing info */
/* Requires version 2 of the GD library in directory given by */
/* extension_dir variable in PHP config file. */
/* If this is undefined and you can't change it, put the library in the script folder. */
/* Pass wedges as querystring parameters of form an=rrggbb123 */
/* where an is a1, a2, ...; rr, gg, bb are hexadecimal color values; and 123 is an integer from 0-360 */

/* read querystring */
$slices=0;
foreach ($_GET as $value)
{
 $red[$slices]=hexdec(substr($value,0,2));
 $grn[$slices]=hexdec(substr($value,2,2));
 $blu[$slices]=hexdec(substr($value,4,2));
 $ang[$slices]=1*substr($value,6);
 $slices++;
}

if ($slices==0)
{
 $red[$slices]=0;
 $grn[$slices]=0;
 $blu[$slices]=0;
 $ang[$slices]=120;
 $slices++;
}

Header( "Content-type: image/png");

/* create image */
$image = imagecreate(100,100); 

/* create colors */
for ($i=0; $i<$slices; $i++)
{ $color[$i] = ImageColorAllocate($image,$red[$i],$grn[$i],$blu[$i]); }

$bkgd = ImageColorAllocate($image,255,255,255); 
$border = ImageColorAllocate($image,100,100,100);


/*  create background*/
ImageFilledRectangle($image,0,0,100,100,$bkgd);

/*  draw pie wedges  */
$apos=0;
for ($i=0; $i<$slices; $i++)
{
 if (($ang[$i]<=0) || ($apos>=360)) continue;
 $oldpos=$apos;
 $apos+=$ang[$i];
 if (($apos>360) || ($i==$slices-1)) $apos=360;
 ImageFilledArc($image,50,50,98,98,$oldpos+270,$apos+270,$color[$i],0);
}
 
/*  draw circle  */
ImageFilledArc($image,50,50,98,98,0,360,$border,IMG_ARC_NOFILL);

/*  render image */
ImagePNG($image);

/*clean up memory */
ImageDestroy($image); 
?>
