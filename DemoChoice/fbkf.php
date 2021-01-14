<?php
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Feedback form
*/

$AdminEmail="you@yoursite.org";
$ThisFile="fbkf.php";

require('dcconfigload.php');

if (!array_key_exists("poll",$_GET))
{ $Pollname="_"; }

$cr=chr(13).chr(10);
$Submitted=false;

function BadEmail($StrIn)
{
 $CSOut=false;
 $hasat=false;
 for ($i=1; $i<=strlen($StrIn); $i++)
 {
  $jok=false;
  $j=ord(substr($StrIn,$i-1,1));
  if (($j==32) || ($j==45) || ($j==95) || ($j==46)) { $jok=true; } // space - _ . @
  if (($j>=48) && ($j<=57)) { $jok=true; } // numbers
  if (($j>=65) && ($j<=90)) { $jok=true; } // upper case letters
  if (($j>=97) && ($j<=122)) { $jok=true; } // lower case letters
  if (($j==64) && (!$hasat)) { $jok=true; $hasat=true; } // @
  if (!$jok) { $CSOut=true; }
 } 
 if (!$hasat) { $CSOut=true; }
 if (strlen($StrIn)<7) { $CSOut=true; }
 if (!$CSOut and
  (substr($StrIn,strlen($StrIn)-4,1)!=".") and 
  (substr($StrIn,strlen($StrIn)-3,1)!=".") and
  (substr($StrIn,strlen($StrIn)-5,1)!="."))
 { $CSOut=true; } 
 return $CSOut;
} 

function BadName($StrIn)
{
 $CSOut=false;   
 for ($i=1; $i<=strlen($StrIn); $i++)
 {
  $jok=false;
  $j=ord(substr($StrIn,$i-1,1));
  if (($j==32) || ($j==45) || ($j==95) || ($j==38)) { $jok=true; } // space-_.&
  if (($j>=48) && ($j<=57)) { $jok=true; } // numbers
  if (($j>=65) && ($j<=90)) { $jok=true; } // upper case letters
  if (($j>=97) && ($j<=122)) { $jok=true; } // lower case letters
  if (!$jok) { $CSOut=true; }
 }
 if (strlen($StrIn)==0) { $CSOut=true; }
 return $CSOut;
}


if (array_key_exists("submit",$_POST))
{
 $Submitted=true;

 if (array_key_exists("unm",$_POST))
 {
  $Username=stripslashes(trim($_POST["unm"]));
  if (BadName($Username))
  { $Username="DemoChoice User"; }
 }

 if (array_key_exists("ml",$_POST))
 {
  $Email=strip_tags(stripslashes(trim($_POST["ml"])));
  if (strlen($Email)>0)
  {
   if (BadEmail($Email))
   { $Email="bad email address"; }
  }
  else { $Email="no email provided"; }
 }
 else
 { $Email="no email provided"; }

 if (array_key_exists("comment",$_POST))
 { $ComStr=trim($_POST["comment"]); }
 else { $ComStr="Blank form!"; }

 if (array_key_exists("dc",$_POST))
 { $dcStr=trim($_POST["dc"]); }
 else { $dcStr="Blank dc box!"; }
 $hasdc=false;
 if ($dcStr=="demochoice") $hasdc=true;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<title>DemoChoice &#102;&#101;&#101;&#100;&#98;a&#99;&#107; &#102;orm</title>
<body bgcolor=white text=black style="font-family: arial;">
<table
style="margin-left: 10%; margin-right: 10%;"
 border=0 cellspacing=0 cellpadding=4>
<tr><td><img src="img/dclogo40.gif" alt=""></td>
<td><font size=5 face="Helvetica">DemoChoice &#102;<!-- e -->&#101;&#101;&#100;&#98;a&#99;&#107; 
&#102;orm</font></td>
</tr></table>
<?php 
if ($Submitted)
{

  $MailText="This message is user feedback from a DemoChoice poll.".$cr;
  $MailText.="You are receiving this because you are on record as the ";
  $MailText.="creator of the poll.  Please contact ".$AdminEmail;
  $MailText.=" if you do not want to receive such messages.".$cr; 
  $MailText.="Name: ".$Username.$cr."Email: ".$Email.$cr;
  $MailText.="IP addr: ".$_SERVER["REMOTE_ADDR"].$cr;
  $MailText.="Browser: ".$_SERVER["HTTP_USER_AGENT"].$cr;
  if ($Pollname!="_")
  {
   $MailText.="Poll: ".$Pollname.$cr;
   $MailText.=$AbsURL.$BallotFile.$cr; 
  }
  $MailText.=$cr;

  $to=$AdminEmail;
  if ($CreatorMail!="") { $to.=",".$CreatorMail; }
  $subj="DemoChoice feedback";
  if ($Email!="no email provided" && $Email!="bad email address")
  { $from="From: ".$Email; }
  else { $from = "From: ".$AdminEmail; }
  if (($ComStr!="Blank form!") && $hasdc)
  {
   mail($to,$subj,$MailText.html_entity_decode($ComStr).$cr,$from);
//   echo "<hr width=50%><pre>To: ".$Email.$cr.$from.$cr;
//   echo $subj.$cr.$MailText."</pre><hr width=50%>";
?>
Thanks for sending your &#99;o&#109;m&#101;<!-- r -->&#110;&#116;&#115;!
<?php
  } else
echo "You didn't type &quot;demochoice&quot; in the box, or said nothing, so nothing was sent."; 
?>
<p>
<?php if ($Pollname!="_") { ?>
 <a href=dcballot.php?poll=<?php echo $Pollname; ?>>Vote</a> |
 <a href=dcresults.php?poll=<?php echo $Pollname; ?>#Round1>View Results</a> |
<?php } ?>
 <a href=index.php>DemoChoice Main Page</a>
<?php 
} // submitted
else
{
?>
<p align=center style="margin-left:15%; margin-right: 15%;">
<center>
<form action=<?=$Pollname=="_"?"fbkf.php":$ThisFile; ?> method="post">
<table border=0 cellspacing=0 cellpadding=5>
<tr><td bgcolor=f0d0e0>Yo<!-- hey -->ur na<!-- go away robots -->&#109;e:</td>
<td bgcolor=f0d0e0> <input type="text" size=40 maxlength=40 name="unm"></td></tr>
<tr><td bgcolor=f0d0e0>Yo<!-- hey -->ur e&#109;<!-- go away robots -->ail:<br>
<font size=1>(optional)</font></td>
<td bgcolor=f0d0e0><input type="text" size=40 maxlength=40 name="ml"></td></tr>
<tr><td bgcolor=f0d0e0>Type &quot;demochoice&quot; in this box:</td>
<td bgcolor=f0d0e0><input type="text" size=40 maxlength=40 name="dc"></td></tr>

<tr><td colspan=2 align=left bgcolor=e0d0f0>
<b>Enter your &#99;o&#109;m&#101;&#110;&#116;&#115; here!</b><br>
<textarea name="comment" rows=12 cols=80></textarea>
<?php if ($Pollname=="_") { ?>
<br>If you are writing about a specific poll, please indicate which one.
<?php } ?>
</td></tr>
</table>
<input type="submit" name="submit" value="s&#101;n&#100; &#99;o&#109;m&#101;&#110;&#116;&#115;">
</form>
</center>
<p style="margin-left: 10%; margin-right: 10%;">
You can also directly write to<br><img src="img/dc.gif" alt="us.">
<?php if ($Pollname!="_") { ?>
<br>but if you do, please mention your poll's label
(<?=$Pollname ?>). 
<?php } ?>
</p>
<p style="margin-left: 10%; margin-right: 10%;">
<table border=0 width="80%"><tr><td align=left>
DemoChoice &copy;2001
<a 
href="http://www.laweekly.com/news/features/what-democracy-votes-like/3237/">Dave 
Robinson</a>
</td><td align=right><a href=<?=$HomeFile; ?>>Main page</a>
</td></tr></table>
<?php } ?>
</body>
</html>
