<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Side-by-side bar chart of first and final rounds
*/

$DisclaimArray=array("Oakland","Oakland4","Oakland6","Berkeley1","Berkeley4","Berkeley7","Berkeley8","SanLeandro");

$rmax=getrandmax();
$Digits=0;
$ShowType="";
$TypeQuery="?type=";

if (array_key_exists("barmax",$_GET))
{ $BarMax=1*$_GET["barmax"]; }
else
{ $BarMax=250; }
 
if (array_key_exists("charmax",$_GET))
{ $CharMax=1*$_GET["charmax"]; }
else
{ $CharMax=30; } 

if (array_key_exists("barht",$_GET))
{ $BarHeight=1*$_GET["barht"]; }
else
{ $BarHeight=12; } 

if (array_key_exists("cellpad",$_GET))
{ $CellPad=1*$_GET["cellpad"]; }
else
{ $CellPad=1; } 

if (array_key_exists("notag",$_GET))
// strip tags (pictures, links) from candidate names
{ $notag=true; }
else
{ $notag=false; } 

if (array_key_exists("noex1",$_GET))
// include first-round exhausted if false
{ $noex1=true; }
else
{ $noex1=false; } 

$ThisFile="dcresultsum.php";

require("dcconfigload.php");
require("dctallyload.php");

if ($notag)
{ for ($i=0; $i<$Cands; $i++) { $Name[$i]=strip_tags($Name[$i]); } }

if ($noex1)
{
 $noextemp=$VoteMatrix[0][$Cands];
 $TotalVotes-=$noextemp;
 for ($i=0; $i<$Rnds; $i++) $VoteMatrix[$i][$Cands]-=$noextemp;
 $Depth[$Cands]-=$noextemp;
 $Thresh-=$noextemp/($Seats+1.0); 
}

$whotemp="";
if (array_key_exists("who",$_GET))
{
 $whotemp=rawurlencode($_GET['who']);
 $whotemp=str_replace("%40","@",$whotemp);
}
if ($NoRunningTally && $pwd!="" && $whotemp==$pwd) $NoRunningTally=false;
if ($Resultpwd && $pwd!="" && $whotemp!=$pwd) $NoRunningTally=true;

if ($CastLink!="")
{ 
 if (!(($Pipe=strpos($CastLink,"poll="))===false))
 { $CastLink=trim(substr($CastLink,$Pipe+5)); }
 else
 { $CastLink=""; }
 if (!(($Pipe=strpos($CastLink,"&"))===false))
 { $CastLink=trim(substr($CastLink,0,$Pipe-1)); }
} 

if ((strpos($ThisFile,"?") ? strpos($ThisFile,"?")+1 : 0)!=0)
{ $TypeQuery="&amp;type="; } 

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
  echo "<!-- google_ad_section_start -->";
  for ($i=0; $i<$TitleLines; $i++)
  {
   echo $Title[$i];
   if ($i>0) { echo "<br>"; } else { echo " "; }
  }
  echo "<!-- google_ad_section_end -->";
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
<link rel="shortcut icon" href="favicon.ico" type="image/vnd.microsoft.icon">
</head>
<body bgcolor=white text=black style="font-family: arial;">
&nbsp;<br>
<?php 
if (($TotalVotes>0) && !$NoRunningTally)
{
$ZeroSkip=0;
$DoneFirst=false;
$PrevRnd=1;
PrintTitle();
echo "<center><table border=1 cellspacing=0 cellpadding=3><tr valign=top>";
for ($RndNum=0; $RndNum<$Rnds; $RndNum++)
{
$ThreshSize=Dither($Thresh*(1-$NoEx*$VoteMatrix[$RndNum][$Cands]/$TotalVotes)*$BarMax/$MaxVote);
$CThreshSize=Dither($Thresh*(1-$NoEx*$VoteMatrix[$RndNum][$Cands]/$TotalVotes)*$CharMax/$MaxVote);

if ($Elim[$RndNum]<0)
{
 if ($VoteMatrix[$RndNum][abs($Elim[$RndNum])-1]==0)
 { $SkipZero=true; }
 else
 { $SkipZero=false; } 
}
else
{ $SkipZero=false; } 

if ($SkipZero)
{ $ZeroSkip++; }
else if ((!$DoneFirst) || ($RndNum==$Rnds-1))
{
$DoneFirst=true;
?>
<td>
<table border=0 cellspacing=0 cellpadding=<?php echo $CellPad; ?>>
 <tr>
  <td width="25%">

<b>
<?php if ($RndNum<$Rnds-1)
 {
  echo "First Round";
 }
 else
 {
  echo "Final Round";
  echo "<!-- google_ad_section_start -->";
 } ?>
</b></td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>
   <table border=0 cellspacing=0 cellpadding=0 align=left>
    <tr>
     <td><?php
      $CharStr="";
      for ($ichar=1; $ichar<=$CThreshSize; $ichar++)
      { $CharStr.="-"; } 
     ?><img border=0 height=<?php echo $BarHeight; ?> width=<?php echo max(1,$ThreshSize-10); ?>
        src=shim.gif alt="<?php echo $CharStr; ?>"></td>
     <td><?php echo round(100.0*$Thresh/$TotalVotes,1); ?>%</td>
    </tr>
   </table>
  </td>
 </tr>
<?php 
for ($CndNum=0; $CndNum<=$Cands; $CndNum++)
{
if ($Continuing[$Sort[$CndNum]] && ($VoteMatrix[$RndNum][$Sort[$CndNum]]>0.000001))
{
 $BarSize=Dither($VoteMatrix[$RndNum][$Sort[$CndNum]]*$BarMax/$MaxVote);
 $CharSize=Dither($VoteMatrix[$RndNum][$Sort[$CndNum]]*$CharMax/$MaxVote);
?>
 <tr>
  <td><?php echo $Name[$Sort[$CndNum]]; ?></td>
  <td align=right><?php echo number_format($VoteMatrix[$RndNum][$Sort[$CndNum]],$Digits)."&nbsp;"; ?></td>
  <td align=right><?php
 if (!($NoEx && $Sort[$CndNum]==$Cands))
 {
 echo "(".number_format($VoteMatrix[$RndNum][$Sort[$CndNum]]*100.0/($TotalVotes-$NoEx*$VoteMatrix[$RndNum][$Cands]),1)."%)&nbsp;";
 }
 else { echo "&nbsp;"; }
 ?></td>
  <td>
   <table border=0 cellspacing=0 cellpadding=0 align=left>
   <tr>
    <td bgcolor="<?php echo $ColorStr[$Sort[$CndNum]] ?>">
<?php
if ($BarSize<$ThreshSize)
 { 
  $CharStr="";
  for ($ichar=1; $ichar<=$CharSize; $ichar++)
  { $CharStr.=chr(65+$Sort[$CndNum]); }
?><img border=0 height=<?php echo $BarHeight; ?> width=<?php echo $BarSize; ?>
  src=shim.gif alt="<?php echo $CharStr; ?>"></td>
  <td><?php 
  $CharStr="";
  for ($ichar=1; $ichar<=($CThreshSize-$CharSize); $ichar++) { $CharStr.=chr(95); } 
?><img border=0 height=<?php echo $BarHeight; ?> width=<?php echo $ThreshSize-$BarSize; ?> src=shim.gif 
  alt="<?php echo $CharStr; ?>"></td>
<td><?php
if ($CndNum<$Cands)
{ ?><img border=0 height=<?php echo $BarHeight; ?> src=dotline.gif alt=":"><?php }
else
{ ?><img border=0 width=1 height=<?php echo $BarHeight; ?> src=shim.gif alt=" "><?php }
?></td>
<td><img border=0 height=<?php echo $BarHeight; ?> width=<?php echo $BarMax-$ThreshSize; ?> src=shim.gif alt=" "><?php
}
else
{ 
 $CharStr="";
 for ($ichar=1; $ichar<=$CThreshSize; $ichar++)
 { $CharStr.=chr(65+$Sort[$CndNum]); } 
?><img border=0 width=<?php echo $ThreshSize; ?> src=shim.gif alt="<?php echo $CharStr; ?>"></td>
<td bgcolor="<?php echo $ColorStr[$Sort[$CndNum]] ?>">
<?php 
if ($CndNum<$Cands)
{ ?><img border=0 height=<?php echo $BarHeight; ?> src=dotline.gif alt=":"><?php }
else
{ ?><img border=0 width=1 height=<?php echo $BarHeight; ?> src=shim.gif alt=" "><?php }
?></td><td bgcolor="<?=$ColorStr[$Sort[$CndNum]] ?>">
<?php
$CharStr="";
for ($ichar=1; $ichar<=($CharSize-$CThreshSize); $ichar++) { $CharStr.=chr(65+$Sort[$CndNum]); } 
?><img border=0 height=<?php echo $BarHeight; ?> width=<?php echo $BarSize-$ThreshSize; ?>
 src=shim.gif alt="<?php echo $CharStr; ?>"></td>
<td><img border=0 height=<?php echo $BarHeight; ?> width=<?php echo $BarMax-$BarSize; ?> src=shim.gif alt=" "><?php
}
?></td>
    </tr>
   </table>
  </td>
 </tr>
<?php 
} 
} 
//CndNum

?><tr>
<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
<td><img border=0 height=10 width=<?php echo $BarMax; ?> src=shim.gif alt=" "></td>
</tr>
<?php 
if ($RndNum==$Rnds-1)
{
//if final round
?>
<!-- google_ad_section_end -->
<tr>
<td><b>Results</b></td>
<td colspan=3>&nbsp;</td>
</tr>
<?php 
for ($i0=0; $i0<$Cands; $i0++)
{
 if ($VoteMatrix[$RndNum][$i0]>0.000000001)
 {
  ?><tr><td><?php echo $Name[$i0]; ?></td><td colspan=2><?php 
  if ($Status[$i0]<1)
  { echo "Defeated"; }
  else
  { echo "Elected"; } 
  ?></td><td>&nbsp;</td></tr><?php 
 } //Votematrix > 0
} //i0
} //final round
?>
</table></td><?php
$PrevRnd=$RndNum+1;
} //zero skip
} //round loop
?>
</tr></table></center><p>
<?php
 if (!(array_search($Pollname,$DisclaimArray)===false))
 {
?>
<p style="margin-left: 15%; margin-right: 15%;">
This demonstration poll is not an accurate measure of a candidate's popularity.
However, it may be a good measure of which candidates' supporters know the most about ranked-choice voting.
</p>
<?php
 }
?>
<center>
Other displays: 
<a href="dcresults.php?poll=<?php echo $Pollname; if ($noex1) echo "&noex1=on"; ?>">all rounds</a> of the 
count, a 

<a href="dcresults.php?poll=<?php echo $Pollname; ?>&amp;type=table">table 
form</a>, or
<a href="dcpies.php?poll=<?php echo $Pollname; ?>">pie charts</a><p>
<a href="<?php echo $FeedbackFile; ?>">Ask a question</a> |
<a href="<?=$InfoFile ?>">How it works</a> |
<?php
if ($CastLink!="")
{
 echo "Go to the <a href=dcresultsum.php?poll=".$CastLink."><b>next question</b></a><br>";
}
else
{
 echo "<a href=dcdir.php>More polls</a> | ";
 echo "<a href=".$HomeFile.">Main Page</a>";
}

 ?>
</center>
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
?><p><center><a href="<?php echo $HomeFile; ?>">DemoChoice Main Page</a></center><?php
} 
?>
<p>
<?php
 if (!$Invite && !$Expired)
 {
 $subjt=strip_tags($Title[0]);
 $subjt=str_replace("&quot;",chr(34),$subjt);
 $subjt=str_replace("&quot;",chr(60),$subjt);
 $subjt=str_replace("&quot;",chr(62),$subjt);
 $subjt2=strlen($subjt)>60 ? substr($subjt,0,60)."..." : $subjt;
 $cr=chr(13).chr(10);
 $mailto="?subject=".rawurlencode("DemoChoice Poll: ".$subjt2);
 $mailtobody="You are invited to make your voice heard in a DemoChoice poll:".$cr.$cr;
 $mailtobody.=$subjt.$cr.$cr."To participate, go to:".$cr.$cr;
 $mailtobody.=$AbsURL.$BallotFile.$cr.$cr;
 $mailtobody.="Please try it!  Your opinion is valued.  ";
 if ($ExpireTime>time())
 {
  $mailtobody.="The final results will be available at".$cr.$cr;
  $mailtobody.=$AbsURL.$ResultFile.$cr.$cr;
  $mailtobody.="on ".date("F d, Y",$ExpireTime).".  ";
  $mailtobody.="Be sure to cast your vote before then!";
 }
 echo $cr.$cr; 
 $mailto.="&amp;body=".rawurlencode($mailtobody);
?>
<p><center>
<table width="100%" border=0><tr><td>
<img src="img/dcmailseal30.gif" alt="->">
<a href="mailto:<?=$mailto; ?>">Invite</a> your friends to vote in this 
poll!<br>
(This link prepares an email message for you.)
</td>

<td><a href="dcdir.php"><img border=0 src="img/123.gif" alt=" "
 style="vertical-align: middle;"></a>
<a href="dcdir.php">More polls</a></td>
</tr></table>
</center>
<p>
<?php } ?>
</body>
</html>
