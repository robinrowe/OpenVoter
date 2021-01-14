<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Displays how many exhausted ballots had how many valid rankings.
*/

$rmax=getrandmax();
$Digits=0;
$ShowType="";
$TypeQuery="?type=";

$ThisFile="exhstats.php";

require("dcconfigload.php");
require("dctallyload.php");

for ($i=0; $i<$Cands; $i=$i+1)
{
 // This array stores the number of exhausted ballots as a function of the number of valid candidates ranked.
 $Exh[$i] = 0;
 $FalseArray[$i] = false;

 if ($VoteMatrix[$Rnds-1][$i]>0)
  $Continuing[$i]=true;
 else
  $Continuing[$i]=false;
} 

$linect=0;
$Line=array();
if (file_exists($FilePath."/".$Pollfile."_ballots.txt"))
{
 $fp=fopen($FilePath."/".$Pollfile."_ballots.txt","r");
 while(!(feof($fp)))
 {
  $l1=trim(fgets($fp,1000));
  if (strlen($l1)>0)
  {
   array_push($Line,$l1);
   $linect++;
  }
 }
 fclose($fp);
}

for ($i=0; $i<$linect; $i=$i+1)
{
 $losersranked = 0;
 $winnersranked = 0;
 $linexp = explode(",",$Line[$i]);
 $linelen=count($linexp);
 $alreadyranked = $FalseArray;
 for ($j=0; $j<$linelen; $j++)
 {
  $x=trim($linexp[$j]);
  if ($x<0 || $x>=$Cands || $Excl[$x]) continue;
  if ($alreadyranked[$x]) continue;
  $alreadyranked[$x] = true;
  if ($Continuing[$x])
   $winnersranked++;
  else
   $losersranked++;
 }
 if ($winnersranked==0) $Exh[$losersranked]++;
}

function PrintTitle()
{
 global $TitleLines, $Title, $Seats, $TotalVotes;
 echo "<center><table cellspacing=4><tr><td align=center><b>DemoChoice Web Poll";
 if ($TitleLines>0)
 {
  echo ": ";
  for ($i=0; $i<$TitleLines; $i++)
  {
   echo $Title[$i];
   if ($i>0) { echo "<br>"; } else { echo " "; }
  }
 }
 echo "</b></td></tr><tr><td align=center>";
 echo $Seats." candidate";
 if ($Seats>1) { echo "s"; } 
 echo " will be elected with ".number_format($TotalVotes,0)." ballot";
 if ($TotalVotes!=1) { echo "s"; }
 echo " cast.</td></tr></table></center>";
}
//--------------------------------------------Start of output
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>DemoChoice Results</title>
</head>
<body bgcolor=white text=black style="font-family: arial;">
&nbsp;<br>
<?php 
if ($TotalVotes>0)
{
echo "<a name=depth>&nbsp;</a>";
PrintTitle();
?>
<hr>
<p>
Here is a breakdown of how many exhausted ballots ranked how many valid candidates.
If a ballot ranked an excluded candidate, the ranking is considered invalid.
</p>
<table border=1 cellspacing=0 cellpadding=2>
<tr><td>Valid candidates ranked</td><td>Exhausted ballots</td></tr>
<?php 
for ($i=0; $i<$Cands;$i++) echo "<tr><td>".$i."</td><td>".$Exh[$i]."</td></tr>";
echo "</table>";
}
else
{
//no votes
PrintTitle();
if ($NoRunningTally)
{
  echo "<p><hr><p><center>Results are not available until polling is over on ";
  echo date("F d, Y",$ExpireTime).".</center>";
}
else
{ echo "<p><hr><p><center>No votes have been cast yet.</center>"; }
?><p><center><a href="<?php echo $HomeFile; ?>">Main Page</a></center><?php
} 
?>
<p>
</body>
</html>
