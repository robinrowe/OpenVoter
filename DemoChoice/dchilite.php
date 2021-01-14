<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Prints links to all polls in HiliteArray, using first title lines
*/

if (array_key_exists("SCRIPT_FILENAME",$_SERVER))
{ $FilePath=str_replace("\\\\","/",$_SERVER["SCRIPT_FILENAME"]); }
else
{ $FilePath=str_replace("\\\\","/",$_SERVER["PATH_TRANSLATED"]); }

if (!(strrpos($FilePath,"/")===false))
{ $FilePath=substr($FilePath,0,strrpos($FilePath,"/")+1)."data/"; }

$DoHilite=false;
foreach ($HiliteArray as $f2)
{
 $f1=str_replace(".","/",$f2)."_config.txt";
 if (!(file_exists($FilePath.$f1)===false))
 {
  $Pollname=$f2;
  $Pollname2=str_replace(".","/",$f2);
  $FoundTitle=false;
  $FoundHilite=true;
  $HideIt=$DoHilite;
  $fp=fopen($FilePath.$Pollname2."_config.txt","r");
  $iters=0;
  while(!(feof($fp) || $FoundTitle || $HideIt || $iters>15))
  {
   $iters++;
   $Line=fgets($fp,1000);
   if ($DoHilite && (!(($Pipe=strpos($Line,"<HILITE>"))===false)))
   { $HideIt=false; }

   if (!(($Pipe=strpos($Line,"<INVISIBLE>"))===false))
   { $HideIt=true; }

   if ((!$HideIt) && (!(($Pipe=strpos($Line,"<TITLE>"))===false)))
   {
    $FoundTitle=true;
?>
<tr>
<td><a href="dcballot.php?poll=<?php     echo $Pollname; ?>">
<?php     echo substr($Line,$Pipe+8-1); ?></a></td>
<td align=center><a href="dcballot.php?poll=<?php echo $Pollname; ?>">
<img border=0 src="img/123.gif" alt="Vote"></a></td>
<td align=center><a href="dcresultsum.php?poll=<?php echo $Pollname; ?>#Round1">
<img border=0 src="img/barchart.gif" alt="Bars"></a></td>
<td align=center><a href="dcpies.php?poll=<?php echo $Pollname; ?>#Round1">
<img border=0 src="img/piechart.gif" alt=" "></a></td>
</tr>
<?php 
   } // FoundTitle
  } // while not found 
 fclose($fp);
 } // if a poll
} // while more dir entries exist
?>
