<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Displays results in bar chart form for a given poll
BUG: Paging feature doesn't pass form information
fix: use &page=0 when calling
*/

$rmax=getrandmax();
$Digits=0;
$pageconst=1000;
$dopage=true;
$ShowType="";
$TypeQuery="?type=";

if (array_key_exists("thickdot",$_GET))
{ $dotfile="dotline2.gif"; }
else
{ $dotfile="dotline.gif"; }

if (array_key_exists("page",$_GET))
{
 $page=1*$_GET["page"]-1;
 if ($page<0) { $page=0; $dopage=false; }
}
else
{ $page=0; }

if (array_key_exists("barmax",$_GET))
{ $BarMax=1*$_GET["barmax"]; }
else
{ $BarMax=350; }
 
if (array_key_exists("charmax",$_GET))
{ $CharMax=1*$_GET["charmax"]; }
else
{ $CharMax=40; } 

if (array_key_exists("barht",$_GET))
{ $BarHeight=1*$_GET["barht"]; }
else
{ $BarHeight=12; } 

if (array_key_exists("cellpad",$_GET))
{ $CellPad=1*$_GET["cellpad"]; }
else
{ $CellPad=1; } 

if (array_key_exists("notag",$_GET))
{ $notag=true; }
else
{ $notag=false; }

$ThisFile="dcbarwhofor.php";

require("dcconfigload.php");
require("dctallyload.php");

if ($notag)
{ for ($i=0; $i<$Cands; $i++) { $Name[$i]=strip_tags($Name[$i]); } }

$whotemp="";
if (array_key_exists("who",$_GET))
{
 $whotemp=rawurlencode($_GET['who']);
 $whotemp=str_replace("%40","@",$whotemp);
}
if ($NoRunningTally && $pwd!="" && $whotemp==$pwd) $NoRunningTally=false;
if ($Resultpwd && $pwd!="" && $whotemp!=$pwd) $NoRunningTally=true;

$Hare=false;
$OldThresh=$Thresh;

$FirstRoundContenders=1;
for ($frc=0;$frc<$Cands;$frc++)
{ if ($VoteMatrix[0][$frc]!=0) $FirstRoundContenders++; }
if ($dopage && $Rnds>0)
 $RndsPerPage=1*ceil($Rnds/ceil(($Rnds*$FirstRoundContenders)/$pageconst));
else $RndsPerPage=max(1,$Rnds);
$LastPage=1*floor($Rnds/$RndsPerPage);
if ($RndsPerPage*$LastPage==$Rnds) $LastPage--;
if ($RndsPerPage==$Rnds) $LastPage=0;
if ($RndsPerPage*$page>$Rnds) $page=$LastPage;
$RndStart=$RndsPerPage*$page;
$RndEnd=min($RndsPerPage*($page+1),$Rnds);

if (array_key_exists("nubal",$_POST))
{
 $NuBal=array();
 $NuBalTemp=explode(",",$_POST["nubal"]);
 foreach ($NuBalTemp as $j)
  if ($j>=0 && $j<$Cands) array_push($NuBal,$j);
 if (count($NuBal)>0) $ValidBallot=true;
 else $ValidBallot=false;
} else $ValidBallot=false;

if (array_key_exists("myorder",$_POST) && $ValidBallot)
{
 $MyOrder=array();
 $NuBalTemp=explode(",",$_POST["myorder"]);
 foreach ($NuBalTemp as $j)
  if ($j>=0 && $j<=$Cands) array_push($MyOrder,$j);
 if (count($MyOrder)>0) $ValidBallot=true;
 else $ValidBallot=false;
} else $ValidBallot=false;

if ($ValidBallot)
{
 for ($i=0; $i<=$Cands; $i++) $Weight[$i]=0;
 $Weight[$MyOrder[0]]=1.0;
}

if ($Seats>1)
 {
  if ($TotalVotes<10)
  { $Digits=2; }
  else
  { $Digits=1; } 
 } 

if (array_key_exists("digits",$_GET))
{ $Digits=$_GET["digits"]; }

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
 $mailto.="&amp;body=".rawurlencode($mailtobody);
 }

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

$LastElim=0;
for ($i=0; $i<$Rnds; $i++)
 if ($Elim[$i]<0) $LastElim=$i;

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
<STYLE type="text/css">	
	H6 {page-break-after: always}
</STYLE>
</head>
<body bgcolor=white text=black style="font-family: arial;">
&nbsp;<br>
<?php 
if (($TotalVotes>0) && !$NoRunningTally)
{
$ZeroSkip=0;
$PrevRnd=$RndStart;
$Thresh=$OldThresh;
$wonsofar=0;
$CandsLeft=0;
for ($i2=0; $i2<$Cands; $i2++)
 if (round($VoteMatrix[$RndStart][$i2],6)>0) $CandsLeft++;

for ($RndNum=$RndStart; $RndNum<$RndEnd; $RndNum++)
{
?><a name=Round<?php echo $RndNum+1; ?>>&nbsp;</a><?php
if ($RndNum==$Rnds-1) { echo "<a name=final>&nbsp;</a>"; }
if ($Elim[$RndNum]<0)
{
 if ($VoteMatrix[$RndNum][abs($Elim[$RndNum])-1]==0)
 { $SkipZero=true; }
 else
 { $SkipZero=false; } 
}
else
{ $SkipZero=false; } 

if ($wonsofar<$Seats && $Elim[$RndNum]>0) $wonsofar++;
if ($Elim[$RndNum]==0 || ($CandsLeft<=$Seats && $RndNum>$LastElim))
{
 $wonsofar=$Seats;
 if ($Seats>1) { $Thresh=$TotalVotes/$Seats; $NoEx=1; }
}

$ThreshSize=Dither($Thresh*(1-$NoEx*$VoteMatrix[$RndNum][$Cands]/$TotalVotes)*$BarMax/$MaxVote);
$CThreshSize=Dither($Thresh*(1-$NoEx*$VoteMatrix[$RndNum][$Cands]/$TotalVotes)*$CharMax/$MaxVote);

if ($SkipZero || ($Elim[$RndNum]==0 && $RndNum<$Rnds-1))
{ $ZeroSkip++; }
else
{
PrintTitle();
?>
<p>
<a href="<?php if($page>0) echo $ThisFile."&amp;page=1"; ?>#Round1">First Round</a> |
<?php
if ($RndNum<$Rnds-1) { ?><a href="<?php if($RndNum+1>=$RndEnd) echo $ThisFile."&amp;page=".($page+2); ?>#Round<?php echo $RndNum+2; ?>"><b>Next Round</b></a> | <?php } 
?><a href="<?php if($page<$LastPage) echo $ThisFile."&amp;page=".($LastPage+1); ?>#Round<?=$Rnds; ?>">Final Round</a> | <?php
if ($RndNum>0) { ?><a href="<?php if($RndNum-1<$RndStart) echo $ThisFile."&amp;page=".$page; ?>#Round<?=$PrevRnd; ?>">Previous Round</a> | <?php } 
?>
<a href="<?php if($page<$LastPage) echo $ThisFile."&amp;page=".($LastPage+1); ?>#depth">Ballot Depth</a> |
<a href="<?php echo $InfoFile; ?>">How it works</a> |
<a href="<?php echo $HomeFile; ?>">Main Page</a>
<hr>
<table border=0 cellspacing=0 width="100%" cellpadding=<?php echo $CellPad; ?>><tr><td>
<table border=0 cellspacing=0 cellpadding=<?php echo $CellPad; ?>>
 <tr>
  <td width="25%"><b>Round <?php echo $RndNum+1-$ZeroSkip; ?></b></td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>
   <table border=0 cellspacing=0 cellpadding=0>
    <tr>
     <td><?php
      $CharStr="";
      for ($ichar=1; $ichar<=$CThreshSize; $ichar++)
      { $CharStr.="-"; } 
     ?><img border=0 height=<?php echo $BarHeight; ?> width=<?php echo max(1,$ThreshSize-10); ?>
        src=shim.gif alt="<?php echo $CharStr; ?>"></td>
     <td><?php echo round(100.0*$Thresh*(1-$NoEx*$VoteMatrix[$RndNum][$Cands]/$TotalVotes)/($TotalVotes-$NoEx*$VoteMatrix[$RndNum][$Cands]),1); ?>%</td>
    </tr>
   </table>
  </td>
 </tr>
<?php
for ($CndNum=0; $CndNum<=$Cands; $CndNum++)
{
if ($Continuing[$Sort[$CndNum]] && ($VoteMatrix[$RndNum][$Sort[$CndNum]]>0))
{
 $BarSize=Dither($VoteMatrix[$RndNum][$Sort[$CndNum]]*$BarMax/$MaxVote);
 $CharSize=Dither($VoteMatrix[$RndNum][$Sort[$CndNum]]*$CharMax/$MaxVote);
?>
 <tr>
  <td><?php echo $Name[$Sort[$CndNum]]; ?>&nbsp;</td>
  <td align=right><?php echo number_format($VoteMatrix[$RndNum][$Sort[$CndNum]],$Digits)."&nbsp;"; ?></td>
  <td align=right><?php
 if (!($NoEx && $Sort[$CndNum]==$Cands))
 {
 echo "(".number_format($VoteMatrix[$RndNum][$Sort[$CndNum]]*100.0/($TotalVotes-$NoEx*$VoteMatrix[$RndNum][$Cands]),1)."%)&nbsp;";
 }
 else { echo "&nbsp;"; }
 ?></td>
  <td>
   <table border=0 cellspacing=0 cellpadding=0>
   <tr>
    <td bgcolor="<?=$ColorStr[$Sort[$CndNum]] ?>">
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
{ ?><img border=0 height=<?php echo $BarHeight; ?> src=<?=$dotfile; ?> alt=":"><?php }
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
<td bgcolor="<?=$ColorStr[$Sort[$CndNum]] ?>">
<?php 
if ($CndNum<$Cands)
{ ?><img border=0 height=<?php echo $BarHeight; ?> src=<?=$dotfile; ?> alt=":"><?php }
else
{ ?><img border=0 width=1 height=<?php echo $BarHeight; ?> src=shim.gif alt=" "><?php }
?></td><td bgcolor="<?=$ColorStr[$Sort[$CndNum]] ?>"><?php 
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
if ($RndNum<$Rnds-1)
{
//Do XferMatrix
?>
<tr>
<td><b>Results</b></td>
<td colspan=2>&nbsp;</td>
<?php
if ($VoteMatrix[$RndNum+1][abs($Elim[$RndNum])-1]!=$VoteMatrix[$RndNum][abs($Elim[$RndNum])-1])
{ ?><td><b>Redistributed Votes</b> (to these colors)</td><?php }
else
{ ?><td>&nbsp;</td><?php }
?>
</tr>
<tr>
<td><?php echo $Name[abs($Elim[$RndNum])-1]; ?></td>
<?php 
if ($Elim[$RndNum]<0) { $Continuing[abs($Elim[$RndNum])-1]=false; } 
if ($Status[abs($Elim[$RndNum])-1]<1)
{ echo "<td colspan=2>Defeated</td>"; }
else
{
 if (($CandsLeft==$Seats && $RndNum>$LastElim) && $RndNum>0 &&
      $VoteMatrix[$RndNum][abs($Elim[$RndNum])-1]>($TotalVotes-$VoteMatrix[$RndNum][$Cands])/$Seats)
  echo "<td colspan=2>Equalized</td>";
 else echo "<td colspan=2>Elected</td>";
} 
?>
<td>
 <table border=0 cellspacing=0 cellpadding=0>
  <tr><?php 
for ($i0=0; $i0<=$Cands; $i0++)
{
 if ($XferMatrix[$RndNum+1][$i0]>0)
 {
  $BarSize=Dither($XferMatrix[$RndNum+1][$i0]*$BarMax/$MaxVote);
  $CharSize=Dither($XferMatrix[$RndNum+1][$i0]*$CharMax/$MaxVote);
  $CharStr="";
  for ($ichar=1; $ichar<=$CharSize; $ichar++) { $CharStr.=chr(65+$i0); } 
?>
<td bgcolor="<?php echo $ColorStr[$i0]; ?>"><img border=0 height=<?php echo $BarHeight; ?>
 width=<?php echo $BarSize; ?> src=shim.gif alt="<?php echo $CharStr; ?>"></td>
<?php 
 } 
} 
?>
   </tr>
  </table>
 </td>
</tr>
<?php 
}
else
{
//if final round
?>
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
</table></td>
<?php if ($ValidBallot) { ?>
<td>
In this round, your ballot counts for:<p>
<table border cellspacing=0 cellpadding=3>
<?php 
  foreach ($NuBal as $i0)
  {
   echo "<tr><td>";
   if ($Weight[$i0]<0.000001) echo "&nbsp;";
   else if ($Weight[$i0]>0.99999) echo "X";
   else echo round(100*$Weight[$i0],1)."%";
   echo "</td><td>".$Name[$i0]."</td></tr>";
  } 
  if ($Weight[$Cands]>0.000001)
  {
   echo "<tr><td>"; 
   if ($Weight[$Cands]>0.99999) echo "X";
   else echo round(100*$Weight[$Cands],1)."%";
   echo "</td><td>".$Name[$Cands]."</td></tr>";
  }
  echo "</table><p>";
?>
</td>
<?php
  if ($RndNum<$Rnds-1 && $MyOrder[$RndNum+1]!=$MyOrder[$RndNum])
/*
      && !($Elim[$RndNum]>0 && $XferFromRnd[$RndNum]>0 &&
           in_array($MyOrder[$RndNum],array_slice($MyOrder,0,$XferFromRnd[$RndNum]-1))))
*/
   // second condition avoids transfer of surplus if not recent
   // last condition prevents transfer when starting to equalize
  {
   $Weight[$MyOrder[$RndNum+1]]+=$Weight[abs($Elim[$RndNum])-1]*$Surplus[$RndNum];
   $Weight[abs($Elim[$RndNum])-1]*=(1-$Surplus[$RndNum]);
  }
 } // if validballot
?>
</tr></table> 
<?php
//------------------------Chart captions
?>
<hr>
<b>Note:</b> If this doesn't make sense, try reading the
<a href="<?php echo $InfoFile; ?>">How it works</a> page
or <a href="<?=$FeedbackFile ?>">ask a question</a>.<br> 

The dotted line represents 
<?php if ($CandsLeft==$Seats && $RndNum>$LastElim)
   echo "an elected member's equal share of the votes ";
  else echo "the number of votes that guarantees victory "; ?>
(<?php echo round(100.0*$Thresh/$TotalVotes,1)."%"; if ($NoEx) { echo " of counted votes"; } ?>).<p>
<?php 
if ($RndNum==0)
{ ?>In the first round, the first choices on each ballot are tallied.<br><?php } 
if ($Elim[$RndNum]==0 || ($CandsLeft==$Seats && $RndNum>$LastElim)) 
{
?>
At this point, the number of remaining candidates equals the number of remaining seats,
so the remaining candidates are declared elected.<br>
<?php 
 if ($RndNum<$Rnds-1)
 {
  echo "<br>Transfers are made from each winner until they all have an equal share of the votes.<br>";
 }
}
else
{
if (is_array($Ties) && ($Ties[$RndNum]!=0))
{
 echo "A tie was resolved by ";
 if ($Ties[$RndNum]>0)
 { echo "comparing votes in previous rounds.<br>"; }
 else
 { echo "choosing a candidate at random.<br>"; }
}
if ($Elim[$RndNum]<0)
{
 echo "No ";
 if ($wonsofar>0) echo "new "
?>candidate has the number of votes needed to guarantee victory, so the last-place candidate (<?php
 echo trim(strip_tags($Name[abs($Elim[$RndNum])-1]));
 ?>) is eliminated.  Ballots for that candidate are counted toward their next highest ranking.
<?php 
} 
if ($Elim[$RndNum]>0)
{
 echo strip_tags($Name[$Elim[$RndNum]-1]);
 ?> has enough votes to guarantee victory and is declared a winner.<?php 
if (($Seats>1) && ($RndNum<$Rnds-1))
{
?>
  To ensure that everyone's vote counts equally, votes that exceed that threshold are counted toward their next highest ranking, if possible. This is actually done by counting a fraction of the ballots most recently  counted for the winning candidate.
<?php 
} //Seats > 1
echo "<br>";

} //someone won
} //Elim isn't empty

if (($RndNum<$Rnds-1))
{
//---Show new colors
$NoNewColors=true;
for ($i=0; $i<=$Cands; $i++)
{
 if (($VoteMatrix[$RndNum][$i]==0) && ($XferMatrix[$RndNum+1][$i]>0))
 {
  if ($NoNewColors)
  { echo "<table border=0><tr><td>New colors: </td>"; }
  else
  { echo "</tr><tr><td>&nbsp;</td>"; }
  ?>
  <td>
   <table border=0 cellspacing=0 cellpadding=0>
    <tr> 
     <td bgcolor="<?php echo $ColorStr[$i]; ?>"><img border=0 height=<?php echo $BarHeight; ?>
     width=<?php echo $BarHeight; ?> src=shim.gif alt="<?php echo chr(65+$Cands); ?>"></td>
    </tr>
   </table>
  </td>
  <?php
  echo "<td> ".$Name[$i]."</td>";
  $NoNewColors=false;
 } 
} 

if (!$NoNewColors)
{ echo "</tr></table>"; } 
?><p><a href="<?php if($RndNum+1>=$RndEnd) echo $ThisFile."&amp;page=".($page+2); ?>#Round<?php echo ($RndNum+2); ?>">
<img border=0 src="btnrta.gif"
style="vertical-align: top;"
onmousedown="this.src='btnrtb.gif';"
onmouseup="this.src='btnrta.gif';"
onmouseout="this.src='btnrta.gif';"
alt="next round" title="next round"
>
Next Round</a><?php 
}
else
{
//In last round
?>In the end, <?php
echo round(100*($VotesCounted/$TotalVotes),1); 
?>% of all cast ballots counted toward a winner. <?php
if ($Rnds>1)
{
 ?>This compares to <?=round(100*($PluralCounted/$TotalVotes),1) ?>% 
if only the <a href="#Round1">first-round</a> votes were used.
<?php
}
if ($Seats>1)
{
?>
You should be able to see that the winners have a more equal mandate in the final round than in 
the <a href="#Round1">first round</a>.<p>
Note that even the &quot;highest first-round votes&quot; method is more democratic than most
 methods used in US public elections: the &quot;vote for <?php echo $Seats; ?>&quot; method, which
 allows the largest block of voters to dominate, and the district method, where choices are restricted
 to the one or two viable candidates within geographical boundaries drawn by the politicians in office.
<?php 
} //Seats > 1
?><p>
The
<a href="#depth">Ballot Depth</a> section shows how much lower rankings contributed to the tally.<p>
<?php

 if (!$Invite && !$Expired)
 { ?>
<p>
<img src="img/dcmailseal30.gif" alt="->">
<a href="mailto:<?=$mailto; ?>">Invite</a> your friends to vote in this poll!<br>
(This link sets up a message in your local email program that you can send.)<p>
<?php } ?>

<?php 
} //whether in last round
?>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><h6>&nbsp;</h6><?php
$PrevRnd=$RndNum+1;
} //zero skip
if ($Elim[$RndNum]<0) $CandsLeft--;
} //round loop

if ($page==$LastPage) { 

echo "<a name=depth>&nbsp;</a>";

PrintTitle();
?>
<p>
<a href="<?php if($page>0) echo $ThisFile."&amp;page=1"; ?>#Round1">First Round</a> |
<a href="#Round<?php echo $Rnds; ?>">Final Round</a> |
Ballot Depth |
<a href="<?php echo $InfoFile; ?>">How it works</a> |
<a href="<?php echo $HomeFile; ?>">Main Page</a>
<hr>
<b>Ballot Depth:</b> This shows how much the lower rankings on ballots contributed to the winning
candidate<?php if ($Seats>1) { echo "s"; } ?>.<p>
<table border=0 cellspacing=0 cellpadding=<?php echo $CellPad; ?>>
 <tr>
  <td width="5%">Rank</td>
  <td colspan=2>Fraction of votes for winner<?php if ($Seats>1) { echo "s"; } ?></td>
 </tr> 
<?php
$SkipCt=0;
if ($DepthMax==0) {$DepthMax=1;}
for ($i0=0; $i0<($Cands-$Seats-$ExclCt); $i0++)
{
 $BarSize=Dither($Depth[$i0]*$BarMax/$DepthMax);
 $CharSize=Dither($Depth[$i0]*$CharMax/$DepthMax);
 $CharStr="";
 for ($ichar=1; $ichar<=$CharSize; $ichar++) { $CharStr.=chr(88); } 
 if (($Depth[$i0]/$DepthMax)>=0.0005)
 {
 echo "<tr><td>".($i0+1);
 $Suffixnum=($i0+1)%10;
 if ((($i0+1)>10) && (($i0+1)<14)) { $Suffixnum=0; } 
 if ($Suffixnum==1) {echo "st";} 
 if ($Suffixnum==2) {echo "nd";} 
 if ($Suffixnum==3) {echo "rd";} 
 if (($Suffixnum>3) || ($Suffixnum==0)) {echo "th";} 
?>&nbsp;</td><td width="5%" align=right><?php
 echo number_format($Depth[$i0]*100.0/$DepthSum,1)."%&nbsp;</td><td>";
 if ($Depth[$i0]>0)
 {
?>
<table border=0 cellspacing=0 cellpadding=0>
 <tr>
  <td bgcolor="<?php echo $ColorStr[$i0]; ?>"><img border=0 height=<?php echo $BarHeight; ?> 
   width=<?php echo $BarSize; ?> src=shim.gif alt="<?php echo $CharStr; ?>"></td>
 </tr>
</table>
<?php 
 }
 else
 { echo "&nbsp;"; } 
 echo "</td></tr>"; 
}
} 
?>
</table>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
&nbsp;
<?php 
} // show depth (on last page)
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
?><p><center><a href="<?php echo $HomeFile; ?>">Main Page</a>

<?php if (!$Invite && !$Expired) { ?>
<p> 
<img src="img/dcmailseal30.gif" alt="->">
<a href="mailto:<?=$mailto; ?>">Invite</a> your friends to vote in this poll!<br>
(This link sets up a message in your local email program that you can send.)<p>
<?php } ?>

</center>
<?php } ?>
<p>
</body>
</html>
