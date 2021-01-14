<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
This parses a ballot form, displays what was received, and requests
user confirmation before sending the data to dccast for recording
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
$ThisFile="dcconfirm.php";
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
<body bgcolor=white text=black style="font-family: arial;">
<center>
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

  // It is assumed that the ballot forbids at least one type of duplicate,
  // so it is not necessary to check for both.
  ?>
</center>
<p style="margin-left: 10%; margin-right: 10%;">


<?php if ($choice) { ?>
Your ballot contained more than one ranking for the same candidate.  This does not help that candidate.<br><br>
Please use your other choices for other candidates would be acceptable if your favorite cannot win (this does not hurt your favorite).<br><br>
If you find only one candidate acceptable, rank that one first and leave the others blank (or use &quot;None&quot;).<br><br>
<?php } else { ?>
You gave the same ranking to more than one candidate, which is not allowed.  Please fix this.<br><br>
<?php } ?>
</p>
<center>
  <?php
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
<form action="dccast.php?poll=<?=$Pollname; ?>" method=post>
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
    echo "<tr><td>".$EntryCounter;
    $Suffixnum=$EntryCounter%10;
    if (($EntryCounter>10) && ($EntryCounter<14)) { $Suffixnum=0; } 
    if ($Suffixnum==1) { echo "st"; } 
    if ($Suffixnum==2) { echo "nd"; } 
    if ($Suffixnum==3) { echo "rd"; } 
    if (($Suffixnum>3) || ($Suffixnum==0)) { echo "th";} 
    echo "</td><td>".$Name[$Ballot[$i0]];
?><input type=hidden name="choice<?=$EntryCounter; ?>"
 value="<?=$Ballot[$i0] ?>"></td></tr><?php
   } 
  } 
  echo "</table><p>";
  if ($Verbose)
  { echo "If you skipped any rankings, lower rankings were moved up.<p>"; }
  if ($Email!="")
  { ?><input type=hidden name=email value="<?=$Email; ?>"><?php }
?>
 If your ballot looks correct, press the &quot;confirm&quot; button.<br><br>
 <input type=submit name=submit value=confirm></form><br></center>
 <p style="margin-left: 25%; margin-right: 25%;">
 If not, use the &quot;back&quot; button or <a href="<?=$BallotFile; ?>">this 
link</a> to go back and
fix your ballot.<br>
 If your mouse has a scrolling wheel, make sure you don't scroll your rankings when you try to
scroll the screen.
<br><br>
<?php
if (!(strstr($BallotFile,"cballot.php")===false) && (strstr($BallotFile,"bb=on")===false))
{ 
 if (strpos($BallotFile,"?")===false) { $BallotFile.="?"; }
  else { $BallotFile.="&amp;"; }
 $BallotFile.="bb=on";
?>Try the <a href="<?=$BallotFile; ?>">less fancy ballot</a> if problems 
persist.<br><?php
}
echo "<center>";
 } //Valid ballot
} // submitted
?>
</center>
<p>
</body>
</html>
