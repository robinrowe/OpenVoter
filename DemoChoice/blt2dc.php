<?php /*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
Converts Electoral Reform Society BLT format to DemoChoice file format
See DemoChoice-Readme.txt for licensing information
This uses the "ls" unix command, so it is platform-dependent.
However, it can be used on Windows machines that have cygwin
or another unix emulator installed.
*/

$ThisFile="blt2dc.php";
$cfile="irsatally";
$DoEqualize=true;
$cr=chr(13).chr(10);

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

exec("ls -m"." ".chr(34).$FilePath.chr(34),$dir);
$dir1=implode(",",$dir);
$dir=explode(",",$dir1);

$dir1=array();

foreach ($dir as $d)
{
 $divider=strpos($d,".BLT");
 if (!($divider===false)) array_push($dir1,trim(substr($d,0,$divider)));
}

$numofpolls=count($dir1);
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
<table border=0 cellspacing=0 cellpadding=3>
<tr><td><font size=2 face="helvetica">Current Polls</font></td>
<td align=center colspan=1><font size=2 face="helvetica">Results</font></td>

<?php 

$HideCount=0;
for ($i=0; $i<$numofpolls; $i++)
{
 $Pollname=$dir1[$i];
?>
<tr>
<td align=left>
<?php
 echo $Pollname;
 if ((empty($Pollname)) or (!file_exists($FilePath.$Pollname.".BLT")))
{ $Pollname="DC"; continue; }

$Cands=0;
$Seats=1;
$Opened=false;
if (file_exists($FilePath.$Pollname.".BLT"))
{
 $Opened=!(($fp=fopen($FilePath.$Pollname.".BLT","r"))===false);
}
$Opened2=false;
if ($Opened)
{
 $Opened2=!(($fp2=fopen($FilePath.$Pollname."_ballots.txt","w+"))===false);
}

$WeirdLines=0;
$Excludes=array();

// read candidates and seats

$Line="";
while ($Opened && !feof($fp) && trim($Line)=="" && $WeirdLines<50)
{
 $Line=trim(fgets($fp,1000));
 if (trim($Line)=="") $WeirdLines++;
}

if (!(($Pipe=strpos($Line," "))===false))
{
 $Cands=1*trim(substr($Line,0,$Pipe));
 $Seats=1*trim(substr($Line,$Pipe));
}
if ($Cands<=0 || $Seats <=0 || $Cands<$Seats)
{
 echo "<H1>WEIRD 1ST LINE: ".$Pollname."</H1>".$Line;
 continue;
}
// read excluded candidates, or first ballot
$ExclCt=0;
$ExclStr="";
$Line="";
while ($Opened && !feof($fp) && trim($Line)=="" && $WeirdLines<50)
{
 $Line=trim(fgets($fp,1000));
 if (trim($Line)=="") $WeirdLines++;
}

if (!(($Pipe=strpos($Line,"-"))===false))
{
 echo "<h2>Found an exclusion</h2>";
 $Excludes=explode("-",$Line);
 $ExclCt=count($Excludes)-1;

 for ($j=1; $j<=$ExclCt; $j++)
 {
  if ($j>1) $ExclStr.=",";
  $ExclStr.=(1*trim($Excludes[$j])-1);
  echo "Exclude ".(1*trim($Excludes[$j])-1)." ";
 }
 echo $ExclStr." ".$ExclCt." ";

 $Line="";
 while ($Opened && !feof($fp) && trim($Line)=="" && $WeirdLines<50)
 {
  $Line=trim(fgets($fp,1000));
  if (trim($Line)=="") $WeirdLines++;
 }
}

do
{
 if ($Line=="0") break;
 $Ballot=explode(" ",$Line);
 $Bout="";
 $foundone=false;
 for ($j=1; $j<count($Ballot)-1; $j++)
 {
  if (trim($Ballot[$j])=="") continue;
  if ($foundone) $Bout.=",";
  $foundone=true;
  $Bout.=$Ballot[$j]-1;
 }
 $Bout.=$cr;

 for ($j=0; $j<$Ballot[0]; $j++)
  if ($Opened2) fputs($fp2,$Bout);

 $Line="";
 while ($Opened && !feof($fp) && trim($Line)=="" && $WeirdLines<50)
 {
  $Line=trim(fgets($fp,1000));
  if (trim($Line)=="") $WeirdLines++;
 }

} while($Opened && !feof($fp));

if ($Opened2) fclose($fp2);

// read candidate names
$CandNames=array();
for ($j=0; $j<$Cands; $j++)
{
 if ($Opened && !feof($fp))
 {

  $Line="";
  while ($Opened && !feof($fp) && trim($Line)=="" && $WeirdLines<50)
  {
   $Line=trim(fgets($fp,1000));
   if (trim($Line)=="") $WeirdLines++;
  }

  $NewNames=explode(chr(34),$Line);
  foreach ($NewNames as $a)
   if (trim($a)!="") array_push($CandNames,$a);
 }
 else if (count($CandNames)<$Cands)
 {
  echo "<H2>".$Pollname." does not have enough candidates.</h2>";
  array_push($CandNames,$j);
 }
}

// read title

 $Title="";
 while ($Opened && !feof($fp) && $WeirdLines<50)
 {
  $Line="";
  while ($Opened && !feof($fp) && trim($Line)=="" && $WeirdLines<50)
  {
   $Line=trim(fgets($fp,1000));
   if (trim($Line)=="") $WeirdLines++;
  }

  $Title.=substr(trim($Line),1,strlen($Line)-2)." ";
 }

if ($Opened) fclose($fp);

$Opened2=false;
if ($Opened)
{
 $Opened2=!(($fp2=fopen($FilePath.$Pollname."_config.txt","w+"))===false);
}

if ($Opened2)
{
 fputs($fp2,"<TITLE> ".$Title.$cr);
 fputs($fp2,"<SEATS> ".$Seats.$cr);
 fputs($fp2,"<EXPIRE> 0".$cr);
 for ($j=0; $j<$Cands; $j++)
  fputs($fp2,"<CANDIDATE> ".$CandNames[$j].$cr);
 if ($ExclCt>0) fputs($fp2,"<EXCLUDE> ".$ExclStr.$cr);
}
if ($Opened2) fclose($fp2);

if (!($Cands==0))
{
 $CCall=$cfile." -f ".$Pollname." -c ".$Cands." -s ".$Seats;
 if ($ExclCt>0) $CCall.=" -x ".$ExclStr;
 if ($DoPile) $CCall.=" -p ";
 if (!$DoEqualize) $CCall.=" -e ";
 unset($Cout);
 exec($CCall,$Cout);
}
?>
</td>
<td align=center><a href="irsaresults.php?poll=<?php echo $Pollname; ?>#Round1">
<img border=0 src="img/barchart.gif" alt="Bars"></a></td>
</tr>
<?php 
} // for each poll
?>

</table>
<hr width=600>
</td>
</tr></table>
</center>
</body>
</html>
