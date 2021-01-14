<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Presents poll results by round as pie charts
*/

$rmax=getrandmax();
$Digits=0;
$pageconst=1000;
$dopage=false;
// The paging feature doesn't work if there are more zero-vote candidates than rounds on a page.
// Invoke manually using the query string if this feature is desired.

$ThisFile="dcpies.php";
$BarMax=360;
$BarHeight=12;

if (array_key_exists("page",$_GET))
{
 $page=1*$_GET["page"]-1;
 $dopage=true;
 if ($page<0) { $page=0; $dopage=false; }
}
else
{ $page=0; }

if (array_key_exists("notag",$_GET))
{ $notag=true; }
else
{ $notag=false; }

if (array_key_exists("noex1",$_GET))
// include first-round exhausted if false
{ $noex1=true; }
else
{ $noex1=false; } 

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

if ($Seats>1) $NoEx=0; // NoEx just hides exhausted in dcpies

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

if ($CastLink!="")
{
 if (!(($Pipe=strpos($CastLink,"poll="))===false))
 { $CastLink=trim(substr($CastLink,$Pipe+5)); }
 else
 { $CastLink=""; }
 if (!(($Pipe=strpos($CastLink,"&"))===false))
 { $CastLink=trim(substr($CastLink,0,$Pipe-1)); }
}

if ($Seats>1) { $Digits=1; } 
if (array_key_exists("digits",$_GET))
{ $Digits=$_GET["digits"]; }

if (array_key_exists("cellpad",$_GET))
{ $CellPad=1*$_GET["cellpad"]; }
else
{ $CellPad=1; } 

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
 
for ($i=0; $i<=$Cands; $i++)
{ $Continuing[$i]=true; } 

$MaxVote=0;
for ($RndNum=0; $RndNum<$Rnds; $RndNum++)
{
 for ($CndNum=0; $CndNum<=$Cands; $CndNum++)
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
   $i1--;
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
}

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
 $wonsofar=0;
 $CandsLeft=0;
 for ($i2=0; $i2<$Cands; $i2++)
  if (round($VoteMatrix[$RndStart][$i2],6)>0) $CandsLeft++;

 for ($RndNum=$RndStart; $RndNum<$RndEnd; $RndNum++)
 {
  ?><a name=Round<?php echo $RndNum+1 ?>>&nbsp;</a><?php
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
 if ($Elim[$RndNum]==0 || ($CandsLeft==$Seats && $RndNum>$LastElim)) $wonsofar=$Seats;
 
 if ($SkipZero || ($Elim[$RndNum]==0 && $RndNum<$Rnds-1))
 { $ZeroSkip++; }
 else
 {
  PrintTitle();
  ?><p><a href="<?php if($page>0) echo $ThisFile."&amp;page=1"; ?>#Round1">First Round</a> | <?php 
  if ($RndNum<$Rnds-1)
  { ?><a href="<?php if($RndNum+1>=$RndEnd) echo $ThisFile."&amp;page=".($page+2); ?>#Round<?php echo $RndNum+2; ?>">Next Round</a> | <?php } 
  ?><a href="<?php if($page<$LastPage) echo $ThisFile."&amp;page=".($LastPage+1); ?>#Round<?php echo $Rnds; ?>">Final Round</a> | <?php 
  if ($RndNum>0)
  {?><a href="<?php if($RndNum-1<$RndStart) echo $ThisFile."&amp;page=".$page; ?>#Round<?php echo $PrevRnd; ?>">Previous Round</a> | <?php 
 } 
?>
<a href="<?php if($page<$LastPage) echo $ThisFile."&amp;page=".($LastPage+1); ?>#depth">Ballot Depth</a> |
<a href="<?php echo $InfoFile; ?>">How it works</a> |
<a href="<?php echo $HomeFile; ?>">Main Page</a>
<hr>
<table border=0 cellspacing=0 cellpadding=<?php echo $CellPad; ?>><tr><td>
<table border=0 cellspacing=0 cellpadding=<?php echo $CellPad; ?>>
<tr>
<td colspan=2><b>Round <?php echo $RndNum+1-$ZeroSkip; ?> Legend</b></td>
<td colspan=3>&nbsp;</td>
</tr>
<?php 
 $qs="?b=0000000";
 for ($CndNum=0; $CndNum<=$Cands; $CndNum++)
 {
  if (($NoEx>0) && ($CndNum==$Cands)) continue;
  if ($Continuing[$Sort[$CndNum]] && ($VoteMatrix[$RndNum][$Sort[$CndNum]]>0.0000001))
  {
   $denom=$TotalVotes-$NoEx*$VoteMatrix[$RndNum][$Cands];
   $BarSize=Dither($VoteMatrix[$RndNum][$Sort[$CndNum]]*$BarMax/$denom);
   $qs.="&amp;a".$CndNum."=".substr($ColorStr[$Sort[$CndNum]],1).$BarSize;
?>
<tr>
 <td><?php echo $Name[$Sort[$CndNum]]; ?>&nbsp;</td>
 <td>
  <table border=0 cellspacing=0 cellpadding=0>
   <tr>
    <td bgcolor="<?php echo $ColorStr[$Sort[$CndNum]]; ?>"><img border=0 alt="&nbsp;" 
     height=<?php echo $BarHeight; ?> width=<?php echo $BarHeight; ?> src=shim.gif></td>
   </tr>
  </table>
 </td>
<td align=right>&nbsp;<?php echo number_format($VoteMatrix[$RndNum][$Sort[$CndNum]],$Digits); ?>&nbsp;</td>
<td align=right><?php 
if (!($NoEx && $Sort[$CndNum]==$Cands))
 {
echo "(".number_format($VoteMatrix[$RndNum][$Sort[$CndNum]]*100.0/($TotalVotes-$VoteMatrix[$RndNum][$Cands]),1)."%)&nbsp;";
}
 else { echo "&nbsp;"; }
?></td> 
<?php
   if (($RndNum==$Rnds-1) && ($CndNum<$Cands))
   {
    if ($Status[$Sort[$CndNum]]<1)
    { echo "<td>Defeated</td>"; }
    else
    { echo "<td>Elected</td>"; } 
   }
   else
   {
    if ($Sort[$CndNum]==abs($Elim[$RndNum])-1)
    {
     if ($Elim[$RndNum]<1)
     { echo "<td>Defeated</td>"; }
     else
     {
      if (($CandsLeft==$Seats && $RndNum>$LastElim) && $RndNum>0 &&
          $VoteMatrix[$RndNum][abs($Elim[$RndNum])-1]>($TotalVotes-$VoteMatrix[$RndNum][$Cands])/$Seats)
       echo "<td colspan=2>Equalized</td>";
      else echo "<td colspan=2>Elected</td>";
     }
    }
    else { echo "<td>&nbsp;</td>"; }
   }
   ?></tr><?php 
  }
 } //CndNum
?>
</table>
</td>
<td><img src=shim.gif alt="&nbsp;" width=40></td>
<td><img src="<?php echo "pie.php".$qs ?>"
 alt="pies need GD2 extension"></td></tr></table> 
<?php
//final round
//------------------------Chart captions
?>
<hr>
<b>Note:</b> If this doesn't make sense, try reading the
<a href="<?php echo $InfoFile; ?>">How it works</a> page,
or <a href="<?=$FeedbackFile ?>">ask a question</a>.<p>

<?php 
if ($RndNum==0)
{ echo "In the first round, the first choices on each ballot are tallied.<br>"; } 
if ($Elim[$RndNum]==0 || ($CandsLeft==$Seats && $RndNum>$LastElim))
{
 ?>At this point, the number of remaining candidates equals the number of remaining seats, so the remaining candidates are declared elected.<br><?php
 if ($RndNum<$Rnds-1)
 {
  echo "<br>Transfers are made from each winner until they all have an equal share of the votes.<br>";
 }
}
else
{
 if ($Elim[$RndNum]<0)
 {
  ?>No candidate has the number of votes needed to guarantee victory 
(<?php echo round(100.0*$Thresh/$TotalVotes,1); ?>%), so the last-place candidate (<?php 
echo trim(strip_tags($Name[abs($Elim[$RndNum])-1]));
 ?>) is eliminated.  Ballots for that candidate are counted toward their next highest ranking.<?php 
 } 
 if ($Elim[$RndNum]>0)
 {
  echo strip_tags($Name[$Elim[$RndNum]-1]); ?> has enough votes to guarantee victory
 (<?php echo round(100.0*$Thresh/$TotalVotes,1); ?>%) and is declared a winner.<?php 
  if (($Seats>1) && ($RndNum<$Rnds-1))
  {
   ?>To ensure that everyone's vote counts equally, votes that exceed that threshold are counted toward their next highest ranking (this is actually done by counting a fraction of ballots cast for the winning candidate).<?php 
  } //Seats > 1
  ?><br><?php 
 } //someone won
} //Elim isn't empty

if (($RndNum<$Rnds-1))
{ ?><p>Go to the <a href="<?php if($RndNum+1>=$RndEnd) echo $ThisFile."&amp;page=".($page+2); ?>#Round<?php echo ($RndNum+2); ?>">next round</a>.<?php }
else
{
//In last round
?>
In the end, <?php echo round(100*($VotesCounted/$TotalVotes),1); ?>%
 of all cast ballots counted toward a winner.
<?php
if ($Rnds>1)
{
?>
 This compares to <?php echo round(100*($PluralCounted/$TotalVotes),1); ?>%
if only the <a href="#Round1">first-round</a> votes were used.
<?php 
} 
//Rnds > 1

if ($Seats>1)
{
?>
You should be able to see that the winners have a more equal mandate in the final round than in the first round.<p>
Note that even the &quot;highest first-round votes&quot; method is more democratic than most methods used in US public elections: the &quot;vote for <?php echo $Seats; ?>&quot; method, which allows the largest block of voters to dominate, and the district method, where choices are restricted to the one or two viable candidates within geographical boundaries drawn by the politicians in office.
<?php 
} 
//Seats > 1
?><p>
Also, view the
<a href="#depth">Ballot Depth</a> info to see how much lower rankings contributed to the tally.<p>

<?php
if ($CastLink!="")
{
 echo "Go to the <a href=dcpies.php?poll=".$CastLink.">next question</a><br>";           
}

 if (!$Invite && !$Expired && $CastLink=="") { ?>
<table width="100%" border=0><tr><td>
<img src="img/dcmailseal30.gif" alt="->">
<a href="mailto:<?=$mailto; ?>">Invite</a> your friends to vote in this
poll!<br>
(This link prepares an email message for you.)
</td>

<td><a href="dcdir.php"><img border=0 src="img/123.gif" alt=" "
 style="vertical-align: middle;"></a>
<a href="dcdir.php">More polls</a></td>
</tr>
</table>
<?php } // show internal ads
} // whether in last round
echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><h6>&nbsp;</h6>";
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
<a href="#Round<?php echo $Rnds; ?>">Last Round</a> |
Ballot Depth |
<a href="<?php echo $InfoFile; ?>">How it works</a> |
<a href="<?php echo $HomeFile; ?>">Main Page</a>
<hr>
<b>Ballot Depth:</b> This shows how much the lower rankings on ballots contributed to the winning
candidate<?php if ($Seats>1)
{
echo "s";} ?>.<p>
<table border=0 cellspacing=0 cellpadding=<?php echo $CellPad; ?>><tr><td>
<table border=0 cellspacing=0 cellpadding=<?php echo $CellPad; ?>>
<tr>
<td>Rank</td>
<td>&nbsp;</td>
<td colspan=2>Fraction of votes for
winner<?php if ($Seats>1)
{
echo "s";} ?></td></tr> 
<?php 
$qs="?b=0000000";
for ($i0=0; $i0<$Cands; $i0++)
{
$BarSize=Dither($Depth[$i0]*$BarMax/$DepthSum); 
if ($BarSize>0.001)
{
$qs.="&amp;a".$i0."=".substr($ColorStr[$i0],1).$BarSize;
?><tr><td><?php
echo $i0+1;
$Suffixnum=($i0+1)%10;
if ((($i0+1)>10) && (($i0+1)<14)) { $Suffixnum=0; } 
if ($Suffixnum==1) {echo "st";} 
if ($Suffixnum==2) {echo "nd";} 
if ($Suffixnum==3) {echo "rd";} 
if (($Suffixnum>3) || ($Suffixnum==0)) {echo "th";} 
?>&nbsp;</td>
<td><table border=0 cellspacing=0 cellpadding=0>
 <tr>
  <td bgcolor="<?php echo $ColorStr[$i0]; ?>">
   <img border=0 height=<?php echo $BarHeight; ?> alt="&nbsp;"
   width=<?php echo $BarHeight; ?> src=shim.gif></td>
 </tr>
</table></td>
<td align=right><?php echo number_format($Depth[$i0]*100.0/$DepthSum,1); ?>%&nbsp;</td>
<td width="35%">&nbsp;</td>
</tr>
<?php
} 
}
?>
</table>
</td>
<td><img src=shim.gif alt="&nbsp;" width=40></td>
<td><img src="<?php echo "pie.php".$qs ?>" alt="pies need GD"></td>
</tr></table>
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
<br>
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
<?php } ?>
</center>
<?php } ?>
<p>
</body>
</html>
