<?php
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
This file is NOT released under GPL
Includes up to 50 lines of text from keynote.txt
Key distribution script
*/

$pwd="dc2018";
$ThisFile="keynote.php";
require("dcconfigload.php");

$Opened=false;
if (file_exists($FilePath."keynote.txt"))
{
 $Opened=!(($fp=fopen($FilePath."keynote.txt","r"))===false);
}
$Lines=0;
$Note="";
while($Opened && !feof($fp))
{
 $Note.=fgets($fp,1000);
 $Lines++;   
 if ($Lines>50) { $Opened=false; }
}
if ($Opened) fclose($fp);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<head>
<title>DemoChoice Key Distribution</title>
</head>
<body>
<?php

if(!(array_key_exists("pwd",$_POST) && $_POST["pwd"]==$pwd))
{
if(array_key_exists("first",$_POST) && $_POST["first"]=="no")
{ echo "Incorrect password.<p>"; }
?>
This script will send the following to lots of people:
<hr>
<?php echo $Note; ?>
<hr>
change data/keynote.txt to change this.

<p>Accessing this function requires a password - please enter it now:<br>
<form action="<?=$ThisFile; ?>" method="post">
<input type="hidden" name="first" value="no">
<input type="password" name="pwd" size=10>
<input type="submit" name="submit" value="enter">
</form>
<?php
}
else
{
$AdminEmail="you@yoursite.org";

$cr=chr(13).chr(10);
$from="From: ".$AdminEmail.$cr."Reply-To: ".$AdminEmail;
$subj="You are invited to vote in a DemoChoice poll";
$cr=chr(10);
$MailText1="You are invited to make your voice heard in a new poll:".$cr.$cr;
$MailText1.=$Title[0].$cr.$cr;
$MailText1.=$Note.$cr;
$MailText1.="To participate, go to:".$cr.$cr;
$MailText1.=$AbsURL.$BallotFile."&who=";
$MailText2=$cr.$cr;
$MailText2.="Please try it!  Your opinion is valued.";
$MailText2.="  The final results will be available at".$cr.$cr;
$MailText2.=$AbsURL.$ResultFile.$cr.$cr;
$MailText2.=" on ".date("j M Y",$ExpireTime);
$MailText2.=".  Be sure to cast your vote before then!".$cr.$cr;
$MailText2.="Thanks,".$cr."DemoChoice".$cr;
$MailText2.="on behalf of ".$Creator.$cr;

 $BadLines=0;
 $GoodLines=0;
 $fname=$Pollfile."_newkeys.txt";
 if (file_exists($FilePath.$fname))
 {
  $fp=fopen($FilePath.$fname,"r");
  while (!feof($fp) && $BadLines<20)
  {
   $Line=fgets($fp,1000);
   if (!(($Pipe=strpos($Line,"|"))===false))
   {
    $Email=substr($Line,0,$Pipe);
    $Key=trim(substr($Line,$Pipe+1));
    mail($Email,$subj,html_entity_decode($MailText1.$Key.$MailText2),$from);
    echo $Email."<br>";
    $GoodLines++;
   }
   else $BadLines++;
  }
  fclose($fp);
 }
 if ($BadLines>=20) echo "Too many bad lines found - mission aborted<br>";
 else if ($GoodLines>0)
 {
  unlink($FilePath.$fname);
  require("dcconfigload.php");
  $subj="DemoChoice keys distributed";
  $mailtext=$GoodLines." keys to your DemoChoice poll, ".$Pollname.", have been distributed.".$cr;
  $Mailtext.="Voters receiving these keys will now be able to vote.".$cr;
  $from = "From: ".$AdminEmail.$cr."Reply-To: ".$AdminEmail;
  if ($Pollname!="DC" && $CreatorMail!="")
  {
   // echo $CreatorMail." ".$subj."<br>".$mailtext."<br>".$from."<br>";
   mail($CreatorMail,$subj,$mailtext,$from);
  }
  echo $GoodLines." keys distributed<br>";
 }
 else echo "No keys to distribute";
} // correct password
