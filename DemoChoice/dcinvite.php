<?php
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Checks to see if a voter is on the registration list
(by email address or provided registration key)
- the registration key is passed to this in $Email
*/

$AdminEmail="you@yoursite.org";
$AdminEmail2="you@yoursite.org";

$MailOn=true;
$OldEmail="";
$passkey="";
$SearchOut=false;

function BadLabel($StrIn)
{
 $CSOut=false;
 for ($i=1; $i<=strlen($StrIn); $i++)
 {
  $jok=false;
  $j=ord(substr($StrIn,$i-1,1));
  if (($j>=48) && ($j<=57)) { $jok=true; } // numbers
  if (($j>=65) && ($j<=90)) { $jok=true; } // upper case letters
  if (($j>=97) && ($j<=122)) { $jok=true; } // lower case letters
  if (!$jok) { $CSOut=true; } 
 } 
 if (strlen($StrIn)==0) { $CSOut=true; } 
 return $CSOut;
}

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
 if (!$CSOut and (substr($StrIn,strlen($StrIn)-4,1)!=".") and (substr($StrIn,strlen($StrIn)-3,1)!="."))
 { $CSOut=true; } 
 return $CSOut;
} 

function SearchRegFile($fname,$regkey)
// search lines in pollname_registrants.txt
// if line format is email|regkey, return email if found, otherwise false
// if line is just email, return true if found, otherwise false
// if line is admin password, return true
{
 global $FilePath,$pwd;
 $FoundIt=false;
 if (file_exists($FilePath.$fname.".txt"))
 {
  $fp=fopen($FilePath.$fname.".txt","r");
  $ValidLine=true;
  while (!feof($fp) && $ValidLine)
  {
   $Line=fgets($fp,1000);
   if (!(($Pipe=strpos($Line,"|"))===false))
   {
    $Email=substr($Line,0,$Pipe);
    $Line=trim(substr($Line,$Pipe+1));
    if (!strcasecmp($Line,$regkey)) { $FoundIt=$Email; break; }
   }
   else
   {
    $Line=trim($Line);
    if (strpos($Line,"@")===false && $Line!=$pwd) { $ValidLine=false; break; }
    if (!strcasecmp($Line,$regkey)) { $FoundIt=true; break; }
   }
  }
  fclose($fp);
 } 
 return $FoundIt;
}

function SearchVoterFile($fname,$Email)
{
 global $FilePath,$Pollname,$pwd;
 $FoundIt=false;
 if (file_exists($FilePath.$fname.".txt"))
 {
  $fp=fopen($FilePath.$fname.".txt","r");
  $ValidLine=true;
  while (!feof($fp) && $ValidLine)
  {
   $Line=fgets($fp,1000);
   if (!(($Pipe=strpos($Line,"|"))===false))
   { $Line=substr($Line,0,$Pipe); }
     $Line=trim($Line);
     if (strpos($Line,"@")===false && $Line!=$pwd && strpos($Line,".")===false) { $ValidLine=false; break; }
     if (strcasecmp($Line,$Email)===0) { $FoundIt=true; break; }
  }
  fclose($fp);
 } 
 return $FoundIt;
}


$NotInvited=false; $bypass=false;
if (isset($Email) && $Email!="")
{
 $Email=trim($_POST["email"]);
 if (strlen($pwd)>6 && $Email==$pwd) { $bypass=true; }
 if ((!$bypass) && BadEmail($Email) && BadLabel($Email)) { $NotInvited=true; }
 $passkey=$Email;
}
else
{ $NotInvited=true; }

if ($NotInvited)
{
 if (!isset($Pollname) || !isset($UseKey) || !$isset($VotersFrom) || $UseKey || $VotersFrom!=$Pollname)
  echo "Your registration key"; else echo "Your email address";
 echo " is not in a valid format.  Please email the administrator if you need help.<p>";
 if (isset($BallotFileNoWho)) echo "<a href=".$BallotFileNoWho.">Try again</a> <br><br>";
}
$SearchOut=false;
if (isset($VotersFrom) && isset($Email))
{
 $Samevotercheck=explode(",",$VotersFrom);
 foreach ($Samevotercheck as $sv)
 {
  if (!($SearchOut=SearchRegFile($sv."_registrants",$Email))===false) 
   break;
 }
}

if ((!$NotInvited) && (!$bypass) && ($SearchOut===false))
{
 $NotInvited=true;
 echo "You were not found on the list of registered voters.<br>";
 if (!$UseKey)
 { ?>
<p style="margin-left: 10%; margin-right: 10%;">
If you have more than one email address,
 please check with the person who invited you to vote
 to find out which address to use.</p>
 <?php } else {
 echo "Please <a href=".$FeedbackFile;
 echo ">contact us</a> if something is wrong.<p>";
 }
 echo "<a href=".$BallotFileNoWho.">Try again</a> <br><br>";
}
if (strlen($SearchOut)>4) { $Email=$SearchOut; }
if ((!$NotInvited) && ((!$bypass) && SearchVoterFile($Pollfile."_voters",$Email)))
{
 $NotInvited=true;
 echo "Our records show that you have already voted.  Please <a href=".$FeedbackFile;
 echo ">contact us</a> if something is wrong.<p>";
 echo "<a href=".$BallotFile.">Try again</a><p>";
}
if (!$NotInvited)
{
 $fp=fopen($FilePath.$Pollfile."_voters.txt","a+");
 fputs($fp,$Email."|".$_SERVER['REMOTE_ADDR'].".".abs(crc32($_SERVER['HTTP_USER_AGENT']))."|".time().$cr);
 fclose($fp);

 $subj="Your vote was cast!";
 $subjt=strip_tags($Title[0]);
 $subjt=str_replace("&quot;",chr(34),$subjt);
 $subjt=str_replace("&lt;",chr(60),$subjt);
 $subjt=str_replace("&gt;",chr(62),$subjt);
 $subjt2=strlen($subjt)>60 ? substr($subjt,0,60)."..." : $subjt;
 $subj.=" (".$subjt2.")";
 $from = "From: ".$AdminEmail.$cr."Reply-To: ".$AdminEmail;

$cr=chr(10); // use unix linebreak for mail text
$MailText="Your vote was counted in a DemoChoice poll:";
$MailText.=$cr.$cr.$subjt.$cr.$cr;
 
$MailText.="If you have any questions, reply to this message or to the ";
$MailText.="person who invited you to vote, and include the name of the poll.";
$MailText.=$cr;
$MailText.="Thank you for using DemoChoice.".$cr.$AbsURL.$cr;
if (!(strpos($Email,"@demochoice.org")===false)) $bypass=true;
 if (!$bypass && $MailOn && !(strpos($Email,"@")===false))
 {
  mail($Email,html_entity_decode($subj),html_entity_decode($MailText),$from);
  echo "An acknowledgement of your vote was emailed to you.<br>";
 }
 if (!$MailOn)
 {
 echo "<hr width=50%><pre>To: ".$Email.$cr.$from.$cr.$subj.$cr.$MailText."</pre><hr width=50%>";
 }
}
if ($OldEmail!="") { $Email=$OldEmail; }
?>
