<?php
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Records voter IP addresses and timestamps, and blocks
repeat voters for a certain time or until enough other
votes have come in 
*/

// $DelayTime=86400;
// time in seconds before one can vote again, now set by dcconfigload
$MaxVoteNum=514; // reset logfile after this many votes
$VoteNum=0;
if (!isset($NotInvited)) $NotInvited=true;
if (!isset($cr)) $cr=chr(13).chr(10);
if (!isset($DelayTime)) $DelayTime=300;

if (array_key_exists("email",$_POST))
{
 $Email=trim($_POST["email"]);
 if (strlen($pwd)>6 && $Email==$pwd) { $DelayTime=0; }
}

function SearchFile($fname,$ip)
{
 global $FilePath,$Pollfile,$VoteNum,$MaxVoteNum;
 $FoundIt=false;
 if (file_exists($FilePath.$Pollfile."_".$fname.".txt"))
 {
  $fp=fopen($FilePath.$Pollfile."_".$fname.".txt","r");
  while (!feof($fp) && $VoteNum<$MaxVoteNum)
  {
   $VoteNum++;
   $Line=fgets($fp,1000);
   $Line2="";
   if (!(($Pipe=strpos($Line,"|"))===false))
   { $Line2=substr($Line,$Pipe+1); $Line=substr($Line,0,$Pipe); }
   $Line=trim($Line);
   $Line2=trim($Line2);
   if(!strcasecmp($Line,$ip)) { $FoundIt=$Line2; }
  }
  fclose($fp);
 } 
 return $FoundIt;
}

$RemoteAddr=$_SERVER['REMOTE_ADDR'].".".crc32($_SERVER["HTTP_USER_AGENT"]);
if (strlen($RemoteAddr)<2) { $RemoteAddr="BadIP"; }
$Timestamp=SearchFile("voters",$RemoteAddr);
if (!($Timestamp===false))
{ if (((int)$Timestamp)<(time()-$DelayTime)) { $Timestamp=false; } }

if ((!$NotInvited) && !($Timestamp===false))
{
 $NotInvited=true;
?>
Our records show that you have recently voted.<br>
Please wait a few hours before casting another vote.<br>
We suggest reading <a href="<?=$InfoFile ?>">how it works</a> in the 
meantime.<br>
If something is wrong, please <a href=<?=$FeedbackFile ?>>contact 
us</a>.<p>
<?php
 echo "<a href=".$BallotFile.">Try again</a><p>";
}
if (!$NotInvited)
{
 if ($VoteNum>=$MaxVoteNum) { $WriteType="w+"; } else { $WriteType="a+"; }
 $fp=fopen($FilePath.$Pollfile."_voters.txt",$WriteType);
 
fputs($fp,$_SERVER['REMOTE_ADDR'].".".crc32($_SERVER['HTTP_USER_AGENT'])."|".time().$cr);
 fclose($fp);
 if ($WriteType=="w+")
 { chmod($FilePath.$Pollfile."_voters.txt",0700); }
}
$Invite=false;
?>
