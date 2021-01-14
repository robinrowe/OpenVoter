<?php /*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Standard ballot that sorts rankings and avoids duplicates and gaps.
*/ ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>DemoChoice Ballot</title>
<?php
$BigBallot=50; // above this, truncate lists and skip unranked in sort
$hrs=getdate(time());
mt_srand($hrs["hours"]);
$ThisFile="mobal.php";
require("dcconfigload.php");
$CastFile="moconf.php?poll=".$Pollname;

if (array_key_exists("norot",$_GET))
{ $norot=true; }
else
{ $norot=false; }

$InfoFile.="&amp;bt=ts";

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
$Cands2=$Cands-$ExclCt;
$ListLength=$Cands2;
if ($ListLength>$BigBallot) { $ListLength=$BigBallot; }
?>
</head>
<body bgcolor=white text=black>
<font face="Helvetica">DemoChoice Web Poll</font>
<p>
<form id="balform" action="<?php echo $AbsURL.$CastFile; ?>" method="post">
<table border=1 cellspacing=0 cellpadding=2><tr><td>
<?php
for ($i=0; $i<$TitleLines; $i++)
{ echo $Title[$i]."<br>"; }
?>
(Rank the candidates you support!)<br>
<?php
echo $Seats." candidate";
if ($Seats>1)
{echo "s";}
?> will be elected.</td></tr>
<?php
for ($i0=0; $i0<$Cands2; $i0++)
{ ?>
<tr><td>
<select name="cand<?php echo $Balrot[$i0]; ?>">
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
<?php echo $Name[$Balrot[$i0]]; ?></td></tr>
<?php
} 
echo "<tr><td>";
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
 if ($UseKey || $VotersFrom!=$Pollname) echo "Key: "; else echo "Email: "; 
 ?>
 <input type="text" name="email" size=20 maxlength=40><br>
<?php
} // invite and not expired and no whotemp

?>
<input type="submit" name="submit" value="vote">
<input type="reset" name="reset" value="clear">
</td></tr></table>
<?php
if ($Invite && $CastLink!="")
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
<a href="<?=$CastLink; ?>">skip this question</a><br>
<?php
} // include skip link
?>
<a href="<?php echo $InfoFile; ?>"
 target="_blank">how it works</a><br>
<a href="<?php echo "mopie.php?poll=".$Pollname."#Round1"; ?>">view 
results</a><br>
<?php
if ($Expired)
{
 echo "The deadline for this poll has passed.  You may cast a ballot anyway to see who it would count for.<p>";
}
if ($Invite)
{
 if (!$Expired)
 {
  if (!array_key_exists("who",$_GET))
  echo "<b>Note:</b> You may only vote in this poll if you were invited.<br>";
  if (!array_key_exists("who",$_GET) && !($UseKey || $VotersFrom!=$Pollname))
   echo "Your email address will be used only to confirm your vote.<br>";
  echo "Please cast your vote before ".date("F d, Y",$ExpireTime).".<p>";
 }
}
?>
Create your own <a href="http://www.demochoice.org">DemoChoice</a> poll!
</form>
</body>
</html>
