<?php 
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Loads data from a poll's configuration file
Note: Title and candidate lines cannot exceed 1000 characters
The file including this should define $ThisFile
*/ 

if (array_key_exists("SCRIPT_FILENAME",$_SERVER))
{ $FilePath=str_replace("\\\\","/",$_SERVER["SCRIPT_FILENAME"]); }
else
{ $FilePath=str_replace("\\\\","/",$_SERVER["PATH_TRANSLATED"]); }

if (!(strrpos($FilePath,"/")===false))
{ $FilePath=substr($FilePath,0,strrpos($FilePath,"/")+1)."data/"; }

$AbsURL=$_SERVER["SCRIPT_NAME"];
if (!(strrpos($AbsURL,"/")===false))
{ $AbsURL="http://".$_SERVER["SERVER_NAME"].substr($AbsURL,0,strrpos($AbsURL,"/")+1); }

$BallotFile="dcballot.php";
if (!isset($Pollname)) $Pollname="DC";
$InfoFile="info.php";
$CastFile="dcconfirm.php";
$CastLink="";
$UseKey=false;
$ResultFile="dcresults.php";
$HomeFile="index.php";
$FeedbackFile="fbkf.php";
$nodivs=false;
$ExclStr=NULL;
$ExclCt=0;
$Expired=false;
$ExpireTime=0;
$RegVoters=1;
$NoRunningTally=false;
$Resultpwd=false;
$BordColor="205090";
$BalColor="E0E0E0";
$DoRecycle=true;
$ShowAds=false;
$DelayTime=300;

if (array_key_exists("poll",$_GET))
{ $Pollname=$_GET["poll"]; } 

$StrIn=substr($Pollname,0,40);
$LabelOut="";
$Slashcount=0;
$badlabel=false;
for ($i=1; $i<=strlen($StrIn); $i++)
{
 $Slashcount++;
 $jok=false;
 $j=ord(substr($StrIn,$i-1,1));
 if (($j>=48) && ($j<=57)) { $jok=true; } // numbers
 if (($j>=65) && ($j<=90)) { $jok=true; } // upper case letters
 if (($j>=97) && ($j<=122)) { $jok=true; } // lower case letters
 if ($j==46 && $Slashcount>1) { $Slashcount=0; $jok=true; } 
 if ($jok) $LabelOut.=chr($j); else $badlabel=true;
} 
if ($badlabel || strlen($StrIn)==0) $LabelOut="DC";
$Pollname=$LabelOut;
$Pollfile=str_replace(".","/",$Pollname);

if ((empty($Pollname)) or (!file_exists($FilePath.$Pollfile."_config.txt")))
{ $Pollname="DC"; $Pollfile="DC"; }

$Ballotname=$Pollname;
$Ballotfilename=$Pollfile;

//Read config file to get candidate names, winners, and threshold type
$TitleLines=0;
$Cands=0;
$Hare=false;
$Invite=false;
$pwdexists=false;
$InvisiPoll=false;
$CreatorMail="";
$Creator="";
$VotersFrom="";
$Withholdtag=false;
$pwd="";
$Seats=1;
$MaxName=0;
$NoEx=1; //Remove exhausted ballots from thresh calc (no=0, yes=1)

$Opened=!(($fp=fopen($FilePath.$Pollfile."_config.txt","r"))===false);
$WeirdLines=0;
while($Opened && !feof($fp))
{
 $Line=fgets($fp,1000);

 if (!(($Pipe=strpos($Line,">"))===false))
 {
  $Tag=trim(substr($Line,1,$Pipe-1));
  $Arg=trim(substr($Line,$Pipe+2));
 }
 else
 { $Tag="NOTAG"; $Arg=""; }

 switch ($Tag)
 {
  case "CANDIDATE":
   $Cands++;
   $Name[$Cands-1]=$Arg;
   $stlen=strlen(strip_tags($Name[$Cands-1]));
   if ($stlen>$MaxName) { $MaxName=$stlen; } 
  break;
 
  case "TITLE":
   $TitleLines++;
   $Title[$TitleLines-1]=$Arg;
  break;

  case "SEATS":
   $Seats=1*$Arg;
  break;

  case "EXPIRE":
   $ExpireTime=1*$Arg;
   if ($ExpireTime<time()) { $Expired=true; }
  break;

  case "EXCLUDE":
   $ExclStr=$Arg;
  break;

  case "INVISIBLE":
   $InvisiPoll=true;
  break;

  case "WITHHOLD":
   $NoRunningTally=true;
   $Withholdtag=true;
   if ($Arg=="pw") $Resultpwd=true;
  break;

  case "INVITE":
   $Invite=true;
   $RegVoters=1*$Arg;
  break;

  case "USEKEY":
   $UseKey=true;
  break;

  case "EDITPW":
   $pwdexists=true;
   $pwd1=explode(",",$Arg);
   $pwd=$pwd1[0];
   $CreatorMail=$pwd1[1];
   $Creator=$pwd1[2];
  break;

  case "DELAY":
   $DelayTime=1*$Arg;
  break;

  case "SHOWADS":
   $ShowAds=true;
  break;

  case "HOMEFILE":
   $HomeFile=$Arg;
  break;

  case "INFOFILE":
   $InfoFile=$Arg;
  break;

  case "CASTFILE":
   $CastFile=$Arg;
  break;

  case "CASTLINK":
   $CastLink=$Arg;
  break;

  case "RESULTFILE":
   $ResultFile=$Arg;
  break;

  case "BALLOTFILE":
   $BallotFile=$Arg;
  break;

  case "BALLOTSTO":
   $Ballotname=$Arg;
   $Ballotfilename=str_replace(".","/",$Arg);
  break;

  case "SAMEVOTERS":
   $VotersFrom=$Arg;
   if (!$Invite) $RegVoters=0;
   $Invite=true;
  break;

  case "NODIVS":
   $nodivs=true;
  break;

  case "HARE":
   $Hare=true;
  break;  

  case "NOEX":
   $NoEx=0;
  break;
  
  case "UNRECYCLE":
   $DoRecycle=false;
  break;

  default:
   $WeirdLines++;
   if ($WeirdLines>20) { $Opened=false; }
  break;
 }
} 
fclose($fp);

$PollQS="poll=".$Pollname;

if (strstr($BallotFile,"poll=")===false)
{
 if (strstr($BallotFile,"?")===false)
  { $BallotFile.="?".$PollQS; }
  else { $BallotFile.="&amp;".$PollQS; }
}

if (strstr($ThisFile,"poll=")===false)
{
 if (strstr($ThisFile,"?")===false)
  { $ThisFile.="?".$PollQS; }
  else { $ThisFile.="&amp;".$PollQS; }
}

if (strstr($CastFile,"poll=")===false)
{
 if (strstr($CastFile,"?")===false)
  { $CastFile.="?".$PollQS; }
  else { $CastFile.="&amp;".$PollQS; }
}

if (strstr($ResultFile,"poll=")===false)
{
 if (strstr($ResultFile,"?")===false)
  { $ResultFile.="?".$PollQS; }
  else { $ResultFile.="&amp;".$PollQS; }
}

if (strstr($FeedbackFile,"poll=")===false)
{
 if (strstr($FeedbackFile,"?")===false)
  { $FeedbackFile.="?".$PollQS; }
  else { $FeedbackFile.="&amp;".$PollQS; }
}

// if ($Expired || !$Invite) { $NoRunningTally=false; }
if (($ExpireTime)<time()) { $NoRunningTally=false; }
if (!$pwdexists) $Resultpwd=false;

$Name[$Cands]="None of these";

if ($VotersFrom=="") { $VotersFrom=$Pollname; }

if ($Seats==1)
{
 $Hare=false;
 $NoEx=1;
 $DoRecycle=!$DoRecycle;
}

if (strstr($InfoFile,"?")===false)
 { $InfoFile.="?nw=".$Seats; }
 else { $InfoFile.="&amp;nw=".$Seats; }

for ($i=0; $i<=$Cands; $i++) { $Excl[$i]=false; }
if (strlen($ExclStr)>0)
{
 $ExclTemp=explode(",",$ExclStr);
 $ExclCt=0;

 foreach ($ExclTemp as $e)
 {
  $f=trim($e);
  if ((($f>0) or ($f=="0")) and ($f<$Cands))
  { $Excl[$f]=true; $ExclCt++; }
 }
}
?>
