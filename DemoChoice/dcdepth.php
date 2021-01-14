<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Displays some additional depth statistics
*/

$rmax=getrandmax();
$Digits=0;
$ShowType="";
$TypeQuery="?type=";

if (array_key_exists("barmax",$_GET))
{ $BarMax=1*$_GET["barmax"]; }
else
{ $BarMax=500; }
 
if (array_key_exists("charmax",$_GET))
{ $CharMax=1*$_GET["charmax"]; }
else
{ $CharMax=55; } 

if (array_key_exists("barht",$_GET))
{ $BarHeight=1*$_GET["barht"]; }
else
{ $BarHeight=12; } 

if (array_key_exists("cellpad",$_GET))
{ $CellPad=1*$_GET["cellpad"]; }
else
{ $CellPad=1; } 


$ThisFile="dcresults.php";

require("dcconfigload.php");
require("dctallyload.php");

$whotemp="";
if (array_key_exists("who",$_GET))
{
 $whotemp=rawurlencode($_GET['who']);
 $whotemp=str_replace("%40","@",$whotemp);
}
if ($NoRunningTally && $pwd!="" && $whotemp==$pwd) $NoRunningTally=false;
if ($Resultpwd && $pwd!="" && $whotemp!=$pwd) $NoRunningTally=true;

if ((strpos($ThisFile,"?") ? strpos($ThisFile,"?")+1 : 0)!=0)
{ $TypeQuery="&type="; } 

if (array_key_exists("type",$_GET))
{ $ShowType=$_GET["type"]; } 

if ($Seats>1)
 {
  if ($ShowType=="table")
  { $Digits=2; }
  else
  { $Digits=1; } 
 } 

if (array_key_exists("digits",$_GET))
{ $Digits=$_GET["digits"]; }

for ($i=0; $i<=$Cands; $i=$i+1)
{ $Continuing[$i]=true; } 

$MaxVote=0;
for ($RndNum=0; $RndNum<$Rnds; $RndNum++)
{
 for ($CndNum=0; $CndNum<$Cands; $CndNum++)
 {
  if ($VoteMatrix[$RndNum][$CndNum]>$MaxVote)
  { $MaxVote=$VoteMatrix[$RndNum][$CndNum]; } 
 } 
} 
if ($Thresh>$MaxVote)
{ $MaxVote=$Thresh; } 

//Sort according to first round votes (leave exhausted at end)
if ($TotalVotes>0)
{
 for ($i0=0; $i0<=$Cands; $i0++)
 { $Sort[$i0]=$i0; } 

 for ($i0=1; $i0<$Cands; $i0++)
 {
  $Sortme=$Sort[$i0];
  $i1=$i0;
  while($VoteMatrix[0][$Sort[$i1-1]]<$VoteMatrix[0][$Sortme])
  {
   $Sort[$i1]=$Sort[$i1-1];
   $i1=$i1-1;
   if ($i1<=0) {break;} 
  }
  $Sort[$i1]=$Sortme;
 } 

 $VotesCounted=0;
 $DepthMax=0;
 $DepthSum=0;
 for ($i=0; $i<$Cands; $i++)
 {
  if ($Status[$i]>0)
  { $VotesCounted+=$VoteMatrix[$Rnds-1][$i]; } 
  if ($Depth[$i]>$DepthMax)
  { $DepthMax=$Depth[$i]; }
  $DepthSum+=$Depth[$i];  
 } 

 $PluralCounted=0;
 for ($i=0; $i<$Seats; $i++)
 { $PluralCounted+=$VoteMatrix[0][$Sort[$i]]; } 
} // $TotalVotes > 0

function Dither($dithval)
{
 global $rmax;
 $dithint=intval($dithval);
 $dithfrac=$dithval-$dithint;
 if ((rand()/$rmax)<$dithfrac)
 { $dithfrac=1; }
 else
 { $dithfrac=0; } 
 return $dithint+$dithfrac;
} 

srand(1);
for ($i0=0; $i0<=$Cands; $i0++)
{
 $ColorStr[$i0]="#";
 for ($i1=1; $i1<=3; $i1++)
 { $ColorStr[$i0]=$ColorStr[$i0].dechex(intval((rand()/$rmax)*16))."0"; } 
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
if (($TotalVotes>0) && !$NoRunningTally)
{
echo "<a name=depth>&nbsp;</a>";
PrintTitle();
?>
<p>
<!--
<a href="<?php echo $ThisFile.$TypeQuery."table"; ?>">Table form</a> |
Chart form:
<a href="#Round1">First Round</a> |
<a href="#Round<?php echo $Rnds; ?>">Final Round</a> |
Ballot Depth |
<a href="<?php echo $InfoFile; ?>">How it works</a> |
<a href="<?php echo $HomeFile; ?>">Main Page</a>
-->
<hr>
<b>Cumulative Ballot Depth:</b> This shows the number of ballots that 
listed a certain 
rank (overall bar length) as well as the number of ballots cast for
<?php if ($Seats==1) { echo "the"; } else { echo "a"; } ?>
 winner that consulted that rank during the tally (colored segment).  
This reflects how important it is to list a certain number of rankings:
of the number of ballots listing that ranking, the given percentage of them
used the ranking to help elect a winner.
<p>
 <table border=0 cellspacing=0 cellpadding=<?php echo $CellPad; ?>>
 <tr>
  <td width="5%">Rank</td>
  <td colspan=3>
Winning ballots using that ranking / Ballots having that ranking 
</td>
 </tr> 
<?php
$SkipCt=0;
if ($DepthMax==0) {$DepthMax=1;}

$RankInt[0]=$TotalVotes;
$DepthInt[0]=$DepthSum;
for ($i0=1; $i0<($Cands-$ExclCt); $i0++)
{
 $RankInt[$i0]=$RankInt[$i0-1]-$RankCt[$i0-1];
 $DepthInt[$i0]=$DepthInt[$i0-1]-$Depth[$i0-1];
}

$RankMax=max($TotalVotes,1);

for ($i0=0; $i0<($Cands-$ExclCt); $i0++)
{
 $DBarSize=Dither($DepthInt[$i0]*$BarMax/$RankMax);
 $DCharSize=Dither($DepthInt[$i0]*$CharMax/$RankMax);
 $RBarSize=Dither($RankInt[$i0]*$BarMax/$RankMax-$DBarSize);
 $RCharSize=Dither($RankInt[$i0]*$CharMax/$RankMax-$DCharSize);
 $DCharStr="";
 for ($ichar=1; $ichar<=$DCharSize; $ichar++) { $DCharStr.=chr(88); } 
 $RCharStr="";
 for ($ichar=1; $ichar<=$RCharSize; $ichar++) { $RCharStr.=chr(79); } 
 if (($Depth[$i0]/$RankMax)>=0.005 || ($RankCt[$i0]/$RankMax)>0.005)
 {
 echo "<tr><td>".($i0+1);
 $Suffixnum=($i0+1)%10;
 if ((($i0+1)>10) && (($i0+1)<14)) { $Suffixnum=0; } 
 if ($Suffixnum==1) {echo "st";} 
 if ($Suffixnum==2) {echo "nd";} 
 if ($Suffixnum==3) {echo "rd";} 
 if (($Suffixnum>3) || ($Suffixnum==0)) {echo "th";} 
 echo "&nbsp;</td><td width=".chr(34)."20%".chr(34)." align=right>";
 echo $DepthInt[$i0]." / ".$RankInt[$i0]."</td><td>&nbsp;(";
 echo max(0,number_format($DepthInt[$i0]*100.0/$RankInt[$i0],1));
 echo "%)&nbsp;</td><td>";
?>
<table border=0 cellspacing=0 cellpadding=0>
 <tr>
<?php if ($Depth[$i0]>0) { ?>
  <td bgcolor="<?php echo $ColorStr[$i0]; ?>">
  <img border=0 height=<?php echo $BarHeight; ?> 
  width=<?php echo $DBarSize; ?> src=shim.gif alt="<?php echo $DCharStr; ?>"></td>
<?php
  }
  if ($RBarSize>0) {
?>
  <td bgcolor=999999>
  <img border=0 height=<?php echo $BarHeight; ?> 
  width=<?php echo $RBarSize; ?> src=shim.gif alt="<?php echo $RCharStr; ?>"></td>
<?php
 }
 else
 ?>
 </tr>
</table>
<?php 
 echo "</td></tr>"; 
}
} 
?>
</table>
<br><br><br>
&nbsp;
<?php 
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
