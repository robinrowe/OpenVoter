<?php /*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Ballot simulating paper optical scan grid
One radio button group per candidate
*/ ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>DemoChoice Ballot</title>
<link rel="shortcut icon" href="favicon.ico" type="image/vnd.microsoft.icon">
<?php 
//DemoChoice: A preference voting package for the web (C) 2001 Dave Robinson
//Ballot header include
//Produces form field array of Cand0...Cand(#candidates-1)
//w/ values = a rank from 0 to #candidates
//0 = first place, #candidates=not ranked
//Renumbers duplicate rankings and sorts if browser can handle it.
//Still backward-compatible w/ primitive browsers.
$hrs=getdate(time());
mt_srand($hrs["hours"]);
$ThisFile="dcgridhballot.php";
require("dcconfigload.php");
$InfoFile.="&amp;bt=op";

if (array_key_exists("bb",$_GET))
{ $barebones="true"; }
else
{ $barebones="false"; }

if (array_key_exists("norot",$_GET))
{ $norot=true; }
else
{ $norot=false; }

if (array_key_exists("terse",$_GET))
{ $Verbose=false; }
else
{ $Verbose=true; } 

$SkipCt=0;
for ($i=0; $i<$Cands; $i++)
{ if ($Excl[$i]) {$SkipCt++;} else {$Balrot[$i-$SkipCt]=$i; } } 
for ($i=0; $i<($Cands-$ExclCt); $i++)
{
 $temp=$Balrot[$i];
 $j=intval(($Cands-$ExclCt-$i)*(mt_rand(0,10000000)/10000000))+$i;
 if ($norot) { $j=$i; }
 $Balrot[$i]=$Balrot[$j];
 $Balrot[$j]=$temp;
 $invBalrot[$Balrot[$i]]=$i;
} 
?>

<SCRIPT type="text/javascript" LANGUAGE="JavaScript"><!--
w3cdom=(document.getElementById)?1:0;

// This disables the automatic submit feature (bug?) when enter is hit in a text box in internet explorer
function testForEnter() 
{    
 if ((!<?=$barebones ?>) && (w3cdom) && (event.keyCode == 13)) 
 {        
  event.cancelBubble = true;
  event.returnValue = false;
 }
} 

//--></SCRIPT>

</head>
<body bgcolor=white text=black style="font-family: arial;">
<?php if ($Verbose)
 { 

 ?>
<center>
<table border=0 cellspacing=0 cellpadding=4>
<tr>
<td><font size=5 face="Helvetica">DemoChoice Web Poll</font></td>
</tr></table><p>
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
    echo "Please cast your vote before ".date("F d, Y",$ExpireTime).".<p>";
   }
  }
 } 

?>
<form action="<?php echo $AbsURL.$CastFile; ?>" method="post">
<table cellspacing=0 cellpadding=4 
style="background-color: rgb(224,224,224);
 border-style: solid;
 border-width: 4px;
 border-color: rgb(32,80,116);">

<?php 
for ($i=0; $i<$TitleLines; $i++)
{ echo "<tr align=center><td>".$Title[$i]."</td></tr>"; } 
?>

<tr align=center><td>(Rank the candidates you support!)</td></tr>
<tr align=center><td>
<?php 
echo $Seats." candidate";
if ($Seats>1) { echo "s"; }
?>
 will be elected.</td></tr>
<tr><td>
<center>
<table cellpadding=3>
<tr>
<th>&nbsp;</th>
<th colspan=<?php echo $Cands-$ExclCt+1; ?>>Preferences</th>
</tr>

<tr>
<th>Candidates</th>
<?php 
  for ($i1=1; $i1<=$Cands-$ExclCt; $i1++)
  {
   echo "<th>".$i1;
   $Suffixnum=$i1%10;
   if (($i1>10) && ($i1<14)) { $Suffixnum=0; } 
   if ($Suffixnum==1) { echo "st"; } 
   if ($Suffixnum==2) { echo "nd"; } 
   if ($Suffixnum==3) { echo "rd"; } 
   if (($Suffixnum>3) || ($Suffixnum==0)) { echo "th"; }
   echo "</th>"; 
  } 
?><th>No</th>
</tr>
<!-- google_ad_section_start -->
<?php
for ($i0=0; $i0<($Cands-$ExclCt); $i0++)
{ 
?>
<tr align=left><td style="border-top: thin solid;"><font size=2>
<?php echo $Name[$Balrot[$i0]]; ?>
</font></td>
<?php
  for ($i1=1; $i1<=$Cands-$ExclCt; $i1++)
  { 
?>
<td style="border-top: thin solid;">
<input type="radio" name="cand<?php echo $Balrot[$i0]; ?>" value="<?php 
   echo $i1;
   $Suffixnum=$i1%10;
   if (($i1>10) && ($i1<14)) { $Suffixnum=0; } 
   if ($Suffixnum==1) { echo "st"; } 
   if ($Suffixnum==2) { echo "nd"; } 
   if ($Suffixnum==3) { echo "rd"; } 
   if (($Suffixnum>3) || ($Suffixnum==0)) { echo "th"; } 
?>"></td><?php 
  } 
  ?>
<td style="border-top: thin solid;">
<input type="radio" name="cand<?php echo $Balrot[$i0]; ?>" value="--" checked></td> 
</tr>
<?php } ?>
<!-- google_ad_section_end -->
</table></center></td></tr>
<?php

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
</td></tr><?php
}

?><tr align=center><td>
<input type="submit" name="submit" value="vote">
<font size=2>
| <a target=_blank href="<?php echo $InfoFile; ?>"> 
how it works</a>
| <a href="<?php echo $ResultFile."#Round1"; ?>">view results</a> |
</font>
<input type="reset" name="reset" value="clear">
</td></tr>
<?php if ($Invite && $CastLink!="")
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
?>
<tr align=center><td>
<font size=2>Create your own
<a href="http://www.demochoice.org">DemoChoice</a> poll!
</font>
</td></tr></table>
</form>
</center>
<?php
if ($Verbose) {
 if (!$ShowAds) {
 ?>
<p style="margin-left: 10%; margin-right: 10%;">
DemoChoice web polls are designed to produce satisfactory representation 
for everyone, with majority rule.</p>
<p style="margin-left: 10%; margin-right: 10%;">
Tips:</p>
<ul style="margin-left: 12%; margin-right: 10%;">
<li>Your lower choices won't hurt the chances of your higher choices.
<li>If you don't rank a candidate, it means you'd rather not have your vote 
count than have it count toward that candidate.
<li>Only the order of your ranked candidates matters, and not the actual numbers.
<li>You can't give two or more candidates the same rank.
</ul>
<p style="margin-left: 10%; margin-right: 10%;">
Click <a href="<?php echo $InfoFile; ?>">here</a> to see how it works, or just 
give it a try!
</p>
<?php } 
 } /* verbose */ ?>
</body>
</html>
