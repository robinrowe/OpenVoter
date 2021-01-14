<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
This parses a ballot form, displays what was received, and requests
user confirmation before sending the data to dccast for recording

mobile version: removed center tag, margin style, reference to scroll mouse
and less fancy ballot
*/

if (array_key_exists("terse",$_GET))
{ $Verbose=false; }
else
{ $Verbose=true; }

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
$ThisFile="moconf.php";
require("dcconfigload.php");

$Email="";
$BallotFileNoWho=$BallotFile;
if (array_key_exists("email",$_POST))
{
 $Email=trim($_POST["email"]);
 if (strpos($BallotFile,"?")===false) { $BallotFile.="?"; }
  else { $BallotFile.="&amp;"; }
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

 if ((!$NotInvited) && ($TriedEqualPref==true))
 {
  echo "<h3>Please correct your vote.</h3>";
  echo "Your ballot contained two identical rankings, which is not allowed.<p>";
  echo "<a href=".$BallotFile.">Try again</a><p>";
 } 

 if ((!$NotInvited) && ($Blank==true))
 {
  echo "<h3>Please correct your vote.</h3>";
  echo "Your ballot contained no rankings, so it was not counted.<p>";
  echo "<a href=".$BallotFile.">Try again</a><p>";
 } 

 if (($TriedEqualPref==false) && ($Blank==false) && ($NotInvited==false))
 {
  $ValidBallot=true;

//--------Write ballot to screen and file
?>
<h3>Please confirm your vote.</h3>

Your ballot was received as:<p>
<form action="mocast.php?poll=<?=$Pollname; ?>" method=post>
<?php if ($Email!="") { ?>
<input type=hidden name=email value="<?=$Email; ?>">
<?php } ?>
<input type=hidden name=baltype value=choice>
<table border=1 cellspacing=0 cellpadding=3>
<?php 
  $EntryCounter=0;
  for ($i0=0; $i0<$Cands; $i0++)
  {
   if ($Rank[$i0]<$Cands)
   {
    $NuBallot.=$Ballot[$i0];
    $EntryCounter++;
    echo "<tr><td><b>".$EntryCounter;
    $Suffixnum=$EntryCounter%10;
    if (($EntryCounter>10) && ($EntryCounter<14)) { $Suffixnum=0; } 
    if ($Suffixnum==1) { echo "st"; } 
    if ($Suffixnum==2) { echo "nd"; } 
    if ($Suffixnum==3) { echo "rd"; } 
    if (($Suffixnum>3) || ($Suffixnum==0)) { echo "th";} 
    echo ":</b> ".$Name[$Ballot[$i0]];
?><input type=hidden name="choice<?=$EntryCounter; ?>"
 value="<?=$Ballot[$i0] ?>"></td></tr><?php
   } 
  } 
  echo "</table><p>";
  if ($Email!="")
  { ?><input type=hidden name=email value="<?=$Email; ?>"><?php }
?>
 <br>If your ballot looks correct, press the &quot;confirm&quot; button.<br><br>
 <input type=submit name=submit value=confirm></form><br>
 <p>
 If not, use the &quot;back&quot; button or <a href="<?=$BallotFile; ?>">this 
link</a> to go back and
fix your ballot.<br>
<?php
  if ($Verbose)
  { echo "Note: if you skipped any rankings, lower rankings were moved up.<p>"; }

 } //Valid ballot
} // submitted
?>
<p>
</body>
</html>
