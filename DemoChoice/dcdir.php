<?php /*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for licensing information
This uses the "ls" unix command, so it is platform-dependent.
However, it can be used on Windows machines that have cygwin
or another unix emulator installed.
*/

$ThisFile="dcdir.php";
$DoHide=true;

if (array_key_exists("start",$_GET))
{ $start=max(0,1*$_GET["start"]); }
else
{ $start=0; }
if (array_key_exists("max",$_GET))
{ $max=max(1,1*$_GET["max"]); }
else
{ $max=30; }

if (array_key_exists("sort1",$_GET))
{ $sort1=$_GET["sort1"]; }   
else
{ $sort1="t"; }
if (array_key_exists("sort2",$_GET))
{ $sort2=$_GET["sort2"]; }   
else
{ $sort2="b"; }

switch ($sort1)
{
 case "t":
  $sort="t";
  switch ($sort2)
  {
   case "b": $sortfile="_ballots"; $sortype="Most active"; break;
   case "c": $sortfile="_config"; $sortype="Newest"; break;
   default: $sortfile="_ballots";
  }
  break;
 case "tr":
  $sort="tr";
  switch ($sort2)
  {
   case "b": $sortfile="_ballots"; $sortype="Least active"; break;
   case "c": $sortfile="_config"; $sortype="Oldest"; break;
   default: $sortfile="_ballots";
  }
  break;
 default: $sort="t"; $sortfile="_ballots"; $sortype="Most active";
}
$sortquery="sort1=".$sort."&sort2=".substr($sortfile,1,1);

if (array_key_exists("dir",$_GET))
{ $Dirname=$_GET["dir"]; } else $Dirname="";

$StrIn=substr($Dirname,0,40);
$LabelOut="";
$Slashcount=0;
$badlabel=false;
for ($i=1; $i<=strlen($StrIn); $i++)
{
 $Slashcount++;
 $jok=false;
 $j=ord(substr($StrIn,$i-1,1));
 if (($j>=48) && ($j<=57)) { $jok=true; } // numbers
 if (($j>=65) && ($j<=90)) { $jok=true; } // upper case letters
 if (($j>=97) && ($j<=122)) { $jok=true; } // lower case letters
 if ($j==46 && $Slashcount>1) { $Slashcount=0; $jok=true; } 
 if ($jok) $LabelOut.=chr($j); else $badlabel=true;
} 
if ($badlabel || strlen($StrIn)==0) $LabelOut="";
$Dirname=$LabelOut;
$Dirfile=str_replace(".","/",$Dirname);

if (array_key_exists("SCRIPT_FILENAME",$_SERVER))
{ $FilePath=str_replace("\\\\","/",$_SERVER["SCRIPT_FILENAME"]); }
else
{ $FilePath=str_replace("\\\\","/",$_SERVER["PATH_TRANSLATED"]); }

if (!(strrpos($FilePath,"/")===false))
{ $FilePath=substr($FilePath,0,strrpos($FilePath,"/")+1)."data/"; }

if (!empty($Dirfile) && file_exists($FilePath.$Dirfile))
 $FilePath.=$Dirfile."/";

exec("ls -m".$sort." ".chr(34).$FilePath.chr(34),$dir);
$dir1=implode(",",$dir);
$dir=explode(",",$dir1);

$dir1=array();

foreach ($dir as $d)
{
 $divider=strpos($d,$sortfile);
 if (!($divider===false)) array_push($dir1,trim(substr($d,0,$divider)));
}

$numofpolls=count($dir1);
$max=min($max,$numofpolls);
$start=min($start,$numofpolls-$max);
$max1=min($max,$numofpolls-$start);
 ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>DemoChoice Polls</title>
</head>
<body bgcolor=white text=black style="font-family: arial;">
<center>
<table width="100%" border=0 cellspacing=0 cellpadding=0>
<tr><td>
<table border=0 cellspacing=0 cellpadding=4>
<tr><td><img src="img/dclogo40.gif" alt=""></td>
<td><font size=5 face="Helvetica">DemoChoice Poll Directory</font></td>
</tr></table><p>
Polls <?=$start+1 ?> to <?=$start+$max1 ?> of <?=$numofpolls ?>
 (Only public polls are shown.) 
<?php if ($start>0) { ?>
<a href="<?=$ThisFile."?".$sortquery."&start=".max(0,$start-$max)."&max=".$max; 
?>">
Previous</a>
<?php } if ($start+$max1<$numofpolls) { ?>
<a href="<?=$ThisFile."?".$sortquery."&start=".min($numofpolls,$start+$max)."&max=".$max; ?>">
Next</a>
<?php } ?>
<br><br>
<table border=0 cellspacing=0 cellpadding=3>
<tr><td><font size=2 face="helvetica">Current Polls</font></td>
<td align=center><font size=2 face="helvetica">Vote</font></td>
<td align=center colspan=2><font size=2 face="helvetica">Results</font></td>

<?php 

$HideCount=0;
for ($i=$start; $i<$start+$max1; $i++)
{
 $Pollname=$dir1[$i];
 $FoundTitle=false;
 $HideIt=false;
 $fp=fopen($FilePath.$Pollname."_config.txt","r");
 $iters=0;
 while(!(feof($fp) || $FoundTitle || $HideIt || $iters>10))
 {
  $iters++;
  $Line=fgets($fp,1000);
  if ($DoHide && (!(($Pipe=strpos($Line,"<HIDE>"))===false)))
  { $HideIt=true; }
   if (!(($Pipe=strpos($Line,"<INVISIBLE>"))===false))
  { $HideIt=true; }
   if ((!$HideIt) && (!(($Pipe=strpos($Line,"<TITLE>"))===false)))
  {
   $FoundTitle=true;
?>
<tr>
<td align=left><a href="dcballot.php?poll=<?php echo $Pollname; ?>">
<?php echo substr($Line,$Pipe+7); ?></a></td>
<td align=center><a href="dcballot.php?poll=<?php echo $Pollname; ?>">
<img border=0 src="img/123.gif" alt="Vote"></a></td>
<td align=center><a href="dcresultsum.php?poll=<?php echo $Pollname; ?>#Round1">
<img border=0 src="img/barchart.gif" alt="Bars"></a></td>
<td align=center><a href="dcpies.php?poll=<?php echo $Pollname; ?>#Round1">
<img border=0 src="img/piechart.gif" alt=" "></a></td>
</tr>
<?php 
  } // FoundTitle
 } // while title not found 
 fclose($fp);
 if ($HideIt) $HideCount++;
} // for each poll
?>

</table>
<?php echo $HideCount." of these ".$max1." polls are private.<br>".$sortype; ?>
 polls are listed first.
<a href="<?=$ThisFile."?sort1=t&sort2=b"; ?>">Most active</a> |
<a href="<?=$ThisFile."?sort1=tr&sort2=b"; ?>">Least active</a> |
<a href="<?=$ThisFile."?sort1=t&sort2=c"; ?>">Newest</a> |
<a href="<?=$ThisFile."?sort1=tr&sort2=c"; ?>">Oldest</a> <br>
<br>
<?php if ($start>0) { ?>
<a href="<?=$ThisFile."?".$sortquery."&start=".max(0,$start-$max)."&max=".$max; ?>">
Previous</a>
<?php } if ($start+$max1<$numofpolls) { ?>
<a href="<?=$ThisFile."?".$sortquery."&start=".min($numofpolls,$start+$max)."&max=".$max; ?>">
Next</a>
<?php } ?>
<hr width=600>
DemoChoice &copy;2001
<a href="http://www.laweekly.com/news/what-democracy-votes-like-2135837">Dave Robinson</a>
</td>
<td><img border=0 src="shim.gif" alt="&nbsp;" width=20></td>
<td style="vertical-align: top;">
</td>
</tr></table>
</center>
</body>
</html>
