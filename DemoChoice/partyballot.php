<?php
 /*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information

Partisan ballot with up to 100 parties
shuffle by party and then by candidate within party group
include <!-- ## --> at start of <CANDIDATE> entry in config file
where ## = 2-digit party name index
The zeroth element of the party name list should ideally be "No party" or a similar dummy name in case no party is 
found.

*/ ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>DemoChoice Ballot</title>
<?php
/* In this file, partyname is only used to keep track of
 the number of parties. */
$partyname=array("Democratic","Republican","American Independent", "Green","Libertarian","Peace and Freedom");

$BigBallot=50; // above this, truncate lists and skip unranked in sort
$hrs=getdate(time());
mt_srand($hrs["hours"]);
$ThisFile="partyballot.php";
require("dcconfigload.php");

if (array_key_exists("terse",$_GET))
{ $Verbose=false; }
else
{ $Verbose=true; } 

if (array_key_exists("bb",$_GET))
{ $barebones="true"; }
else
{ $barebones="false"; } 

if (array_key_exists("norot",$_GET))
{ $norot=true; }
else
{ $norot=false; }

$bbfile=$ThisFile;
if ((strpos($ThisFile,"?") ? strpos($ThisFile,"?")+1 : 0)!=0)
{ $bbfile.="&amp;bb=on"; } else { $bbfile.="?bb=on"; }

$InfoFile.="&amp;bt=ts";

$SkipCt=0;
for ($i=0; $i<$Cands; $i++)
{
 if ($Excl[$i])
 {$SkipCt++;}
 else
 {
  $Balrot[$i-$SkipCt]=$i;
  $Party[$i-$SkipCt]=0;
  if (strlen($Name[$i])>11 && is_numeric(substr($Name[$i],5,2)))
  $Party[$i-$SkipCt]=substr($Name[$i],5,2)*1;
 }
} 
$Cands2=$Cands-$ExclCt;
foreach ($partyname as $za => $zb) $Partyrot[$za]=$za;
$za=count($partyname);
for ($i=0; $i<$za; $i++)
{
 $temp=$Partyrot[$i];
 $j=intval(($za-$i)*(mt_rand(0,10000000)/10000000))+$i;
 if ($norot) { $j=$i; }
 $Partyrot[$i]=$Partyrot[$j];
 $Partyrot[$j]=$temp;
}
for ($i=0; $i<$Cands2; $i++)
{
 $temp=$Balrot[$i];
 $j=intval(($Cands-$ExclCt-$i)*(mt_rand(0,10000000)/10000000))+$i;
 if ($norot) { $j=$i; }
 $Balrot[$i]=$Balrot[$j];
 $Balrot[$j]=$temp;
} 
for ($za=1; $za<$Cands; $za++)
{
 $zb=$za;
 while ($zb>0 && $Partyrot[$Party[$Balrot[$zb]]]<$Partyrot[$Party[$Balrot[$zb-1]]])
  {
   $temp=$Balrot[$zb];
   $Balrot[$zb]=$Balrot[$zb-1];
   $Balrot[$zb-1]=$temp;
   $zb--;
  }
}
$ListLength=$Cands2;
if ($ListLength>$BigBallot) { $ListLength=$BigBallot; }
?>
<SCRIPT type="text/javascript" LANGUAGE="JavaScript"><!--
ns4dom=(document.layers)?1:0;
w3cdom=(document.getElementById)?1:0;
Cands=<?php echo $Cands2; ?>;
ListLength=<?php echo $ListLength; ?>;
oldmyval=0;
baddataflag=true;

function vote_check(theform)
{
 if (baddataflag)
 {
  hasarank=false;
  for (i0=0; i0<Cands; i0++)
  {
   if (w3cdom)
   {arank=document.getElementById("sel"+i0).selectedIndex;}
   else {arank=document.forms[0].elements[i0].selectedIndex;}
   if(arank>0) {hasarank=true;} else break;
   if (w3cdom)
   {document.getElementById("sel"+i0).selectedIndex=0;}
   else {document.forms[0].elements[i0].selectedIndex=0;}
  }
//  if (hasarank) {alert('Browser lost form data.  If you have not cast your vote, please try again.');}
  baddataflag=false;
 }
}

function testForEnter() 
{    
 if ((!<?php echo $barebones; ?>) && (w3cdom) && (event.keyCode == 13)) 
 {        
  event.cancelBubble = true;
  event.returnValue = false;
 }
} 

function newoldmyval(theval) { oldmyval=theval; }

function listsort(me,myval) {

// initialize arrays
var newstate = new Array(
<?php
 for ($i0=0; $i0<=$Cands2; $i0++)
{ 
 echo 0;
 if ($i0!=$Cands2) { echo chr(44); }
}
?> );
var changedit = new Array(
<?php
 for ($i0=0; $i0<=$Cands2; $i0++)
{ 
 echo 0;
 if ($i0!=$Cands2) { echo chr(44); }
}
?> );
var rankedit = new Array(
<?php
 for ($i0=0; $i0<=$ListLength; $i0++)
{ 
 echo 0;
 if ($i0!=$ListLength) { echo chr(44); }
}
?> );
changedit[me]=1;
baddataflag=false;

if ((!<?php echo $barebones; ?>) && (w3cdom || ns4dom)) {

// load form data
Lorank=0;
for (i0=0; i0<Cands; i0++)
{
 if (w3cdom) 
 {newstate[i0]=document.getElementById("sel"+i0).selectedIndex;}
 else {newstate[i0]=document.forms[0].elements[i0].selectedIndex;}
 rankedit[newstate[i0]]++;
 if(newstate[i0]>Lorank) {Lorank=newstate[i0];}
}

// determine button action
if (myval<0)
{
 if (w3cdom)  
 {oldmyval=document.getElementById("sel"+me).selectedIndex;}
 else {oldmyval=document.forms[0].elements[me].selectedIndex;}
 if (oldmyval!=0) { myval=0; }
 else if (Lorank==ListLength) { myval = Lorank; }
 else { Lorank++; myval=Lorank; }
 newstate[me]=myval;
 rankedit[oldmyval]--;
 rankedit[myval]++;
}

// process duplicate rankings 
if ((rankedit[myval]>1) && (myval!=0))
{
 // find first gap in rankings above and below myval 
 higap=myval;
 while((higap>0) && (rankedit[higap]>0)) { higap--; }
 logap=myval;
 while((logap<=ListLength) && (rankedit[logap]>0)) { logap++; }

 // lots of conditions in case browser trashes oldmyval somehow
 goup=false;
 godown=false;
 if (myval>oldmyval)
{ if (higap>0) { goup=true; } else if (logap<=ListLength) { godown=true; } }
 else if (myval<oldmyval) 
{ if (logap<=ListLength) { godown=true; } else if (higap>0) { godown=true; } }
 else 
{ if (higap>0) { goup=true; } else if (logap<=ListLength) { godown=true; } }

 if (goup)
 {
  for(i0=0; i0<Cands; i0++)
  {
   si=newstate[i0];
   if ((si>higap) && (si<=myval))
   { newstate[i0]--; changedit[i0]=1; }
  }
  newstate[me]=myval;
 }

 if (godown)
 { 
  for(i0=0; i0<Cands; i0++)
  {
   si=newstate[i0];
   if ((si<logap) && (si>=myval))
   {
    if (si!=ListLength)
    { newstate[i0]++; changedit[i0]=1; }
    else { newstate[i0]=0; changedit[i0]=1; }
   }
  }
  newstate[me]=myval;
 } 
} // end duplicate elimination

// remove gap if candidate is unranked
if (myval==0)
{
 higap=Lorank;
 while((higap>0) && (rankedit[higap]>0)) { higap--; }
 if (higap>0)
 {
  for(i0=0; i0<Cands; i0++)
  {
   if (newstate[i0]>higap) { newstate[i0]--; changedit[i0]=1; }
  }
 }
}

// bubble sort
if (w3cdom) {
 for(i0=1; i0<Cands; i0++)  
 { 
  Sortrank=newstate[i0];
  if (Sortrank==0) { Sortrank=Cands+1; }
  i1=i0;
  Tryrank=newstate[i1-1];
  if (Tryrank==0) { Tryrank=Cands+1; }
  unrankskip=0;
  movedit=false;
  while(Tryrank>Sortrank)
  {
   if (!movedit)
   {
    movedit=true;
    Sortname=document.getElementById("sel"+i0).getAttribute('name');
    Sorthtml=document.getElementById("label"+i0).innerHTML;
   }
   if (Tryrank==Cands+1)
   {
    // skip over unranked, if possible
    if ((Cands><?php echo $BigBallot; ?>) && (i1>1))
    {
     if (newstate[i1-2]==0)
     { i1--; unrankskip++; continue; }
    } 
    Tryrank=0;
   }
   Tryname=document.getElementById("sel"+(i1-1)).getAttribute('name');
   Tryhtml=document.getElementById("label"+(i1-1)).innerHTML;
   newstate[i1+unrankskip]=Tryrank; changedit[i1+unrankskip]=1;
   document.getElementById("sel"+(i1+unrankskip)).setAttribute('name',Tryname);
   document.getElementById("label"+(i1+unrankskip)).innerHTML=Tryhtml;
   i1--; unrankskip=0;
   if (i1<=0) { break; }
  Tryrank=newstate[i1-1];   
  if (Tryrank==0) { Tryrank=Cands+1; }
  }   
  if (Sortrank==Cands+1) { Sortrank=0; }
  if (movedit)
  {
   newstate[i1]=Sortrank; changedit[i1]=1;
   document.getElementById("sel"+i1).setAttribute('name',Sortname);
   document.getElementById("label"+i1).innerHTML=Sorthtml;
  }
 }
} // if w3cdom
notfoundyet=true;
for (i0=0; i0<Cands; i0++)
{
 if (changedit[i0]==1)
 {
  if (w3cdom) {document.getElementById("sel"+i0).selectedIndex=newstate[i0];}
  else {document.forms[0].elements[i0].selectedIndex=newstate[i0];}
 }
 if ((w3cdom) && (notfoundyet) && (newstate[i0]==myval))
 { document.getElementById("sel"+i0).focus(); notfoundyet=false; }
}
} // if either dom
oldmyval=myval;
}
//--></SCRIPT>
</head>
<body bgcolor=white text=black>
<?php 
 if ($Verbose)
 {

 ?>
<center>
<font size=5 face="Helvetica">DemoChoice Web Poll</font>
<p>
<?php
  if ($Expired)
  {
   echo "The deadline for this poll has passed.  You may cast a ballot anyway to see who it would count for.<p>";
  }
  if ($Invite)
  {
   if (!$Expired)
   {
    echo "<b>Note:</b> You may only vote in this poll if you were invited.<br>";
    if (!array_key_exists("who",$_GET) && !($UseKey || $VotersFrom!=$Pollname))
     echo "Your email address will be used only to confirm your vote.<br>";
    echo "Please cast your vote before ".date("F d, Y",$ExpireTime).".<p>";
   }
  }
 }
?>
<form id="balform" action="<?php echo $AbsURL.$CastFile; ?>" method="post">
<table cellspacing=0 cellpadding=4 border=0
style="background-color: rgb(224,224,224);
 border-style: solid;
 border-width: 4px;
 border-color: rgb(32,80,116);">

<?php
for ($i=0; $i<$TitleLines; $i++)
{ echo "<tr align=center><td>".$Title[$i]."</td></tr>"; }
?>

<tr align=center><td>
(Rank the candidates you support!)</td></tr>
<tr align=center><td>
<?php
echo $Seats." candidate";
if ($Seats>1)
{echo "s";}
?> will be elected.</td></tr>

<?php
for ($i0=0; $i0<$Cands2; $i0++)
{ ?>
<tr style="text-align: left; vertical-align: middle; font-size: smaller;">
<td nowrap>
<?php
if ($barebones=="false") {
?>
<img src="btna.gif" 
style="vertical-align: top;"
onmousedown="this.src='btnb.gif';"
onmouseup="this.src='btna.gif'; listsort(<?php echo $i0; ?>,-1);" 
onmouseout="this.src='btna.gif';"
onmouseover="vote_check();"
alt="[add/drop]" title="[add/drop]"
>
<?php } ?>
<select name="cand<?php echo $Balrot[$i0]; ?>" id="sel<?php echo $i0; ?>"
 onmouseover="newoldmyval(this.selectedIndex); vote_check;"
 onfocus="newoldmyval(this.selectedIndex); vote_check;"
 onChange="listsort(<?php echo $i0; ?>,this.selectedIndex);"
 style="vertical-align: baseline;">
 <option selected> --
<?php
  for ($i1=1; $i1<=$ListLength; $i1++)
  {
   echo "<option>".$i1;
   $Suffixnum=$i1%10;
   if (($i1>10) && ($i1<14)) { $Suffixnum=0; }
   if ($Suffixnum==1) { echo "st"; }
   if ($Suffixnum==2) { echo "nd"; }
   if ($Suffixnum==3) { echo "rd"; }
   if (($Suffixnum>3) || ($Suffixnum==0)) { echo "th"; }
  }
  ?></select>
<span id="label<?php echo $i0; ?>"><?php echo $Name[$Balrot[$i0]]; ?></span>
</td></tr>
<?php
} 

$whotemp="";
if (array_key_exists("who",$_GET))
{
 $whotemp=rawurlencode($_GET['who']);
 $whotemp=str_replace("%40","@",$whotemp);
 echo "<input type=hidden name=email value=";
 echo substr($whotemp,0,40);                           
 echo ">";
}   
if ($Invite && !$Expired && $whotemp=="")
{
 echo "<tr align=center><td>";
 if ($UseKey || $VotersFrom!=$Pollname) echo "Key: "; else echo "Email: "; 
 ?>
 <input type="text" name="email" size=20 maxlength=40 onkeydown="testForEnter();">
</td></tr>
<?php
} // invite and not expired and no whotemp

?><tr align=center><td>
<input type="submit" name="submit" id="castvote" 
value="vote" onmouseover="vote_check();" onfocus="vote_check();">
<font size=2>
| <a href="<?php echo $InfoFile; ?>"
 target="_blank">how it works</a>
| <a href="<?php echo $ResultFile."#Round1"; ?>">view results</a> |
</font>
<input type="reset" name="reset" value="clear" id="clear">
</td></tr>
<?php
if (/* $Invite && */ $CastLink!="")
{
  if (substr($CastLink,0,2)=="dc" && $whotemp!="")
  // assuming castlink is to a DemoChoice script
  {
   if (strstr($CastLink,"?")===false)
   { $CastLink.="?who=".$whotemp; }
   else
   { $CastLink.="&who=".$whotemp; }
  }
?>
<tr align=center><td>
<font size=2><a href="<?=$CastLink; ?>">skip this question</a></font>
</td></tr>
<?php
} // include skip link

if ($barebones=="false") {
?>
<tr align=center><td><font size=2>

Try the <a href="<?php echo $bbfile; ?>">less fancy ballot</a> if this one 
fails.
</font></td></tr>
<?php } ?>
<tr align=center><td><font size=2>
Create your own <a href="http://www.demochoice.org">DemoChoice</a> poll!
</font></td></tr>
</table>
</form>
<?php
if ($Verbose) { ?>
</center>

<p style="margin-left: 10%; margin-right: 10%; text-align: left;">
<?php if ($Seats>1) { ?>
DemoChoice web polls are designed to produce satisfactory representation 
for everyone, with majority rule.  If your favorite candidate has too 
few votes to win, your vote will be transferred to your next 
favorite, if possible.
If your favorite candidate has more than enough votes, some ballots 
may be partially transferred so that all winners represent 
equal numbers of voters.
<?php } else { ?>
This is an &quot;instant runoff&quot; poll, allowing voters to 
conveniently find a strongly supported winner from among many candidates, 
with minimal worries about &quot;wasting&quot; votes on weak candidates or 
&quot;splitting&quot; votes between similar candidates.
Here's
<a href="<?php echo $InfoFile; ?>">how it works</a>:
<OL style="margin-left: 15%; margin-right: 10%; text-align: left;">
<LI>Each ballot is counted toward its highest-ranked remaining candidate.
<LI>Does a candidate have a majority of counted votes?
<BR><i>No:</i> The last-place candidate is eliminated; go to step 1.
<BR><i>Yes:</i> The majority winner wins the election.
</OL>
<?php } ?>
<p style="margin-left: 10%; margin-right: 10%; text-align: left;">
Tips:
<ul style="margin-left: 15%; margin-right: 10%; text-align: left;">
<li>Your lower choices won't hurt the chances of your higher choices.
<li>You can't give two or more candidates the same rank.
<li>Only the order of your ranked candidates matters, and not the actual numbers.
<li>If you don't rank a candidate, it means you'd rather not have your vote 
count than have it count toward that candidate.
</ul>

<?php

 } ?>
</body>
</html>
