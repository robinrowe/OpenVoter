<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
This parses ballot form data, checks for unique or authorized users,
invokes a tally, and outputs how a vote counted.

mobile version - removed formatting table, mailto thing
*/

if (array_key_exists("terse",$_GET))
{ $Verbose=false; }
else
{ $Verbose=true; }

function getmicrotime()
{ 
 list($usec, $sec) = explode(" ",microtime()); 
 return ((float)$usec + (float)$sec); 
}

$cr=chr(13).chr(10);
mt_srand((double)microtime()*1000000);
$ValidBallot=false;
$NuBallot="";
$DoIncTally=false;
$Submitted=false;
if (array_key_exists("submit",$_POST))
{
 $Submitted=true;
}
$ThisFile="mocast.php";
require("dcconfigload.php");
$ResultFile="mopie.php?poll=".$Pollname;
$Email="";
$BallotFileNoWho=$BallotFile;
if (array_key_exists("email",$_POST))
{
 $Email=trim($_POST["email"]);
 if (strpos($BallotFile,"?")===false) { $BallotFile.="?"; }
  else { $BallotFile.="&"; }
 $BallotFile.="who=".$Email;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>DemoChoice Tally</title>
</head>
<body bgcolor=white text=black>

<h3>DemoChoice Web Poll</h3>
<?php 
if ($Submitted)
{
//------------------------------------Parse Ballot
 $TriedEqualPref=false;

$choice=false;
if (array_key_exists("baltype",$_POST))
{
 if ($_POST["baltype"]=="choice") { $choice=true; }
}
//For forms where ranks (1st, 2nd, etc.) are provided for each candidate 
 if (!$choice)
 {
   for ($i0=0; $i0<$Cands; $i0++)
   {
    if (array_key_exists("cand".$i0,$_POST))
    { $Rank[$i0]=trim($_POST["cand".$i0]); }
    else
    { $Rank[$i0]="--"; }
    if ($Rank[$i0]=="--")
    {
      $Rank[$i0]=$Cands+1;
    }
    else
    {
     $Rank[$i0]=substr($Rank[$i0],0,strlen($Rank[$i0])-2);
    } 
    $Rank[$i0]--;
    if ($Rank[$i0]<0 || $Rank[$i0]>$Cands) { $Rank[$i0]=$Cands; }
    $Ballot[$i0]=$i0;
   } 
 } 
 else //For forms where the candidate number is provided for each rank
 {
  for ($i0=0; $i0<$Cands; $i0++)
  {
   $Ballot[$i0]=$i0;
   $Rank[$i0]=$Cands;
  } 
  for ($i0=1; $i0<=$Cands; $i0++)
  {
   if (array_key_exists("choice".$i0,$_POST))
   { $Chosen=(1*$_POST["choice".$i0]); }
   else
   { $Chosen=$Cands; }
   if ($Chosen<0 || $Chosen>$Cands) { $Chosen=$Cands; }

   if ($Chosen<$Cands)
   {
    if ($Rank[$Chosen]<$Cands) { $TriedEqualPref=true; } 
    $Rank[$Chosen]=($i0-1);
   } 
  } 
 } 


//Sort according to rank
 for ($i0=1; $i0<$Cands; $i0++)
 {
  $Sortme=$Rank[$i0];
  $Sortb=$Ballot[$i0];
  $i1=$i0;
  while($Rank[$i1-1]>$Sortme)
  {
   $Rank[$i1]=$Rank[$i1-1];
   $Ballot[$i1]=$Ballot[$i1-1];
   $i1--;
   if ($i1<=0) { break; }   
  }
  $Rank[$i1]=$Sortme;
  $Ballot[$i1]=$Sortb;
 } 

//-----------------Catch invalid ballots
 if ($Rank[0]<$Cands)
 { $Blank=false; }
 else
 { $Blank=true; } 
 if ($Cands>1)
 {
  for ($i0=0; $i0<$Cands-1; $i0++)
  {
   if (($Rank[$i0]==$Rank[$i0+1]) && ($Rank[$i0]!=$Cands)) { $TriedEqualPref=true; } 
   if ($Rank[$i0+1]<$Cands) { $Blank=false; } 
  } 
 }

$NotInvited=false;
if (($Blank==false) && ($TriedEqualPref==false) && (!$Expired))
{
 if ($Invite) { include("dcinvite.php"); }
 else { include("dcvotelog.php"); }
}

 if ((!$NotInvited) && ($TriedEqualPref==true))
 {
  echo "Your ballot contained two identical rankings, which is not allowed.<p>";
  echo "<a href=".$BallotFile.">Try again</a><p>";
 } 

 if ((!$NotInvited) && ($Blank==true))
 {
  echo "Your ballot contained no rankings, so it was not counted.<p>";
  echo "<a href=".$BallotFile.">Try again</a><p>";
 } 

 if (($TriedEqualPref==false) && ($Blank==false) && ($NotInvited==false))
 {
  $ValidBallot=true;

//-- check for file lock
if(file_exists($FilePath.$Pollfile."_lock.txt") &&
    filemtime($FilePath.$Pollfile."_lock.txt")>(time()-30))
{ 
?>
Somebody else is currently voting.  Please wait a few seconds and then press your
browser's refresh button.  If you are unable to cast your vote after several attempts,
please <a href=<?=$FeedbackFile ?>>contact us</a>.
<?php
}
else
{
$lf=fopen($FilePath.$Pollfile."_lock.txt","w+");
fputs($lf,time());
fclose($lf);

//--------Write ballot to screen and file
?>
Your ballot was cast as:<p>
<table border=1 cellspacing=0 cellpadding=3>
<?php 
  $EntryCounter=0;
  $FirstVote=true;
  for ($i0=0; $i0<$Cands; $i0++)
  {
   if ($Rank[$i0]<$Cands)
   {
    if ($FirstVote==false)
    { $NuBallot.=","; }
    else
    { $FirstVote=false; }
    $NuBallot.=$Ballot[$i0];
    $EntryCounter++;
    echo "<tr><td><b>".$EntryCounter;
    $Suffixnum=$EntryCounter%10;
    if (($EntryCounter>10) && ($EntryCounter<14)) { $Suffixnum=0; } 
    if ($Suffixnum==1) { echo "st"; } 
    if ($Suffixnum==2) { echo "nd"; } 
    if ($Suffixnum==3) { echo "rd"; } 
    if (($Suffixnum>3) || ($Suffixnum==0)) { echo "th";} 
    echo ":</b> ".$Name[$Ballot[$i0]]."</td></tr>";
   } 
  } 
  echo "</table><p>";
/*
  if ($Verbose)
  { echo "If you skipped any rankings, lower rankings were moved up.<p>"; }
*/
  if ($Expired) { echo "The deadline for this poll has passed, so this ballot will not be recorded.<br>"; }
  else
  {
   $fp=fopen($FilePath.$Ballotfilename."_ballots.txt","a+");
   fputs($fp,$NuBallot.$cr);
   fclose($fp);
  }
 } // file lock didnt exist
 } //Valid ballot
} // submitted

if ($ValidBallot || !$Submitted)
{
//-----------------------------------------Run Tally Script
 $time_start=getmicrotime();
 require("dcinctally.php");

$Ballots=NULL;
 $time_end=getmicrotime();
 if (file_exists($FilePath.$Pollfile."_lock.txt"))
 { unlink($FilePath.$Pollfile."_lock.txt"); }
} 

if ($ValidBallot && !$NoRunningTally && $Verbose)
{
//------------------------------------------Do My Tally
?>
In the <i>final</i> round of this tally, your vote counted for:
<br>
<table border=0 cellspacing=3 cellpadding=3>
<?php 
for ($i=0; $i<$Cands; $i++)
{
 if ($Rank[$i]<$Cands)
 {
  if (round($MyTally[$Ballot[$i]],4)>0.0)
  {
   echo "<tr><td>";
   if (round($MyTally[$Ballot[$i]],4)<1.0)
   { echo round($MyTally[$Ballot[$i]]*100,1)."%: "; } 
   echo $Name[$Ballot[$i]]."</td></tr>";
  }
 } 
} 

if ($MyTally[$Cands]>0)
{
 echo "<tr><td>";
 if (round($MyTally[$Cands],4)<1.0)
 { echo round($MyTally[$Cands]*100,1)."%: "; }
 echo "None of the remaining candidates</td></tr>";
} 
?>
</table>
<p>
<?php 
  if (!$Expired)
 {
  echo "Note that your vote could be counted differently as more people vote.<br>";
  if ($Invite)
  { ?>
 Recast your vote after <?php echo date("F j, Y",$ExpireTime); ?>
 to see who your vote counted for in the final results.<p>
  <?php }
 } ?>
</p>
<?php 
} //Do My Tally

  if ($NoRunningTally)
  { ?>
<table><tr><td width=15%>&nbsp;</td><td width=70%>
 Recast your vote after <?php echo date("F d, Y",$ExpireTime); ?>
 to see who your vote counted for in the final results.
</td><td width=15%>&nbsp;</td></tr></table><p>
  <?php } else { ?>

See the <a
<?php if ($CastLink!="") echo "target=_blank "; ?>
 href="<?php echo $ResultFile."#Round1"; ?>">
<font size="+1"><b>results</b></font></a>
<?php
 if (!$Expired) { echo " so far"; }
 if ($CastLink!="") echo " <font size=-1>(new window)</font>";
 } // norunningtally

 if ($CastLink!="")
 {
  if ($Invite && substr($CastLink,0,2)=="dc")
  // if invite and linking to a demochoice script
  {
   if (strstr($CastLink,"?")===false)
   { $CastLink.="?who=".$passkey; }
   else
   { $CastLink.="&who=".$passkey; }
  }
 if ($NoRunningTally) echo "Go "; else echo " or go ";
 ?>
 to the
 <a href=<?=$CastLink; ?>><font size=+1><b>next
 <?php if (!(strstr($CastLink,"ballot")===false)) echo "question"; else echo "page"; ?>
</b></font></a>
 <?php } else { ?>
<p>
Read <a href="<?php echo $InfoFile; ?>">how it works</a><br>
<a href="<?php echo $HomeFile; ?>">Main Page</a>
</p>
<?php
 } // castlink
?>
</body>
</html>
