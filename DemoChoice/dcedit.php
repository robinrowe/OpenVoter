<?php
/* PROBLEM: doesn't properly handle multiple samevoters entries in post-form HTML output */
// Someday the variable names should be made more sane.
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Allows poll creators with passwords to edit existing polls
The edit and setup forms don't work with subdirectories because
Pollname is often used when it should be Pollfile.
*/

$AdminEmail="you@yoursite.org>";

$KeyFile="keydist.php";
$MailOn=true;
$AllowedCands=80;
$AllowedVoters=12000;
$MaxWin=15;
$ThisFile="dcedit.php";
$Link="";
$cr=chr(13).chr(10);
$allowed_tags="a img b br dd dl dt em font i li ol pre bdo ul p fieldset legend big small hr";
$allowed_tags=explode(" ",$allowed_tags);
$allowed_attr="href src target style width height border size face align dir lang bgcolor";
$allowed_attr.="clear color alt hspace vspace tabindex accesskey charset hreflang type rel rev";
$allowed_attr=explode(" ",$allowed_attr);

include_once("inputfilter.php");
$infilter = new InputFilter($allowed_tags,$allowed_attr);
$_POST = $infilter->process($_POST);
$_GET = $infilter->process($_GET);


require("dcconfigload.php");

// adjust caps based on how many are already there
$AllowedCands=max($AllowedCands,$Cands);
if (!$Invite) { $RegVoters=0; }
$AllowedVoters-=$RegVoters;
$AllowedVoters=max($AllowedVoters,5);

if(!$pwdexists || !(array_key_exists("pwd",$_POST) &&
   trim($_POST["pwd"])==$pwd))
{
// -----------------------------------------------------------get pwd
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>DemoChoice Poll Editor</title>
<link rel="shortcut icon" href="favicon.ico" type="image/vnd.microsoft.icon">
</head>
<body bgcolor=white text=black style="font-family: arial;">
<table border=0 cellspacing=0 cellpadding=4>
<tr><td><img src="img/dclogo40.gif" alt=""></td>
<td><font size=5 face="Helvetica">DemoChoice Poll Editor</font></td>
</tr></table><p>
<?php
 if ($pwdexists && array_key_exists("pwd",$_POST) &&
     trim($_POST["pwd"])==$Pollname)
 {
  echo "Check your email box for instructions.";
  $editmail="Someone, hopefully you, has requested help with the ";
  $editmail.="DemoChoice poll editor.".$cr;
  $editmail.="To edit your poll, go to:".$cr.$cr;
  $editmail.="  ".$AbsURL.$ThisFile.$cr.$cr;
  $editmail.="and enter ".$pwd." in the form box.".$cr;
  $editmail.="If you were not expecting this message, please reply.".$cr;
  $editmail.="Thank you for using DemoChoice!".$cr;
  $to=$CreatorMail;
  $subj="DemoChoice poll editor";
  $from = "From: ".$AdminEmail.$cr."Reply-To: ".$AdminEmail;
  if ($MailOn) mail($to,$subj,html_entity_decode($editmail),$from);
 }
 else
 {
  if (array_key_exists("first",$_POST) && 
      $_POST["first"]=="no")
  { echo "Incorrect password.<p>"; }
?>
<form action="<?=$ThisFile ?>" method="post">
<input type="hidden" name="first" value="no">
<input type="password" name="pwd" size=10>
<input type="submit" name="submit" value="enter">
</form><br>
<p style="margin-left: 5%; margin-right: 5%;">
The poll editor is for those who have created a poll and received login 
instructions.  If you have lost login instructions that you received 
previously, enter your poll's name (label) in the box above and press the 
button.  Instructions will be emailed to you.  Your poll's name should 
appear in all web addresses related to your poll, including this page.</p>
<?php
 }
} else {
//------------------------------------------------------------------have pwd
// Prepare expiration date
$expdate=getdate($ExpireTime);
$yr=$expdate["year"];
$mo=$expdate["mon"];
$dy=$expdate["mday"];
$now=getdate(time());
$Submitted=false;

// convert delay time in config file to index value for listbox
$DelayIndex=2;
{
 switch ($DelayTime)
 {
  case 0: $DelayIndex=3; break;
  case 600: $DelayIndex=2; break;
  case 3600: $DelayIndex=1; break;
  case 86400: $DelayIndex=0; break;
  default: $DelayIndex=2; break;
 }
}

function BadEmail($StrIn)
{
 $CSOut=false;
 $hasat=false;
 $EmailOut="";
 for ($i=1; $i<=strlen($StrIn); $i++)
 {
  $jok=false;
  $j=ord(substr($StrIn,$i-1,1));
  if (($j==32) || ($j==45) || ($j==95) || ($j==46)) { $jok=true; } // space - _ . @
  if (($j>=48) && ($j<=57)) { $jok=true; } // numbers
  if (($j>=65) && ($j<=90)) { $jok=true; } // upper case letters
  if (($j>=97) && ($j<=122)) { $jok=true; } // lower case letters
  if (($j==64) && (!$hasat)) { $jok=true; $hasat=true; } // @
  if (!$jok) { $CSOut=true; } else { $EmailOut.=chr($j); }
 } 
 if (!$hasat) { $CSOut=true; }
 if (strlen($StrIn)<7 || strlen($StrIn)>60)
 { $CSOut=true; $EmailOut=substr($EmailOut,0,60); }
 if (!$CSOut and
  (substr($StrIn,strlen($StrIn)-4,1)!=".") and 
  (substr($StrIn,strlen($StrIn)-3,1)!=".") and
  (substr($StrIn,strlen($StrIn)-5,1)!="."))
 { $CSOut=true; }
 if ($CSOut) $EmailOut="*-".$EmailOut;
 return $EmailOut;
} 

function BadString($StrIn)
{
 $StrOut="";
 for ($i=1; $i<=strlen($StrIn); $i++)
 {
  $j=ord(substr($StrIn,$i-1,1));
  if (($j<32) ||   ($j>126) ||
     ($j==34) /* || ($j==60) || ($j==62) */) { $CSOut=true; }
  else { $StrOut.=chr($j); }
 } 
// if (strlen($StrIn)==0) $StrOut="*";
 if (strlen($StrIn)>1000) $StrOut=substr($StrOut,0,1000); 
 return $StrOut;
} 

function BadLabel($StrIn)
{
 $LabelOut="";
 for ($i=1; $i<=strlen($StrIn); $i++)
 {
  $jok=false;
  $j=ord(substr($StrIn,$i-1,1));
  if (($j>=48) && ($j<=57)) { $jok=true; } // numbers
  if (($j>=65) && ($j<=90)) { $jok=true; } // upper case letters
  if (($j>=97) && ($j<=122)) { $jok=true; } // lower case letters
  if (!$jok) { $CSOut=true; } else { $LabelOut.=chr($j); }
 } 
 if (strlen($StrIn)==0) $LabelOut="*";
 if (strlen($StrIn)>20) $LabelOut=substr($LabelOut,0,20); 
 return $LabelOut;
} 

// extract/validate link data 
if (strstr($HomeFile,"http://")===false)
   $HomeLink=""; else $HomeLink=$HomeFile;

$OldCastLink=$CastLink;
if ($CastLink!="")
{
 if (!(($Pipe=strpos($CastLink,"poll="))===false))
 { $CastLink=trim(substr($CastLink,$Pipe+5)); }
 else
 { $CastLink=""; }
 if (!(($Pipe=strpos($CastLink,"&"))===false))
 { $CastLink=trim(substr($CastLink,0,$Pipe-1)); }
 $CastLink=BadLabel($CastLink);
 $Link=$CastLink;
 // echo $HomeFile." ".$OldCastLink."<br>";
 if ($HomeFile==$OldCastLink) $Link="@home";
}

if (array_key_exists("submit",$_POST) && 
    $_POST["submit"]!="enter")
{
// ----------------------------------------------------------submitted main form
 $Submitted=true;
 $ValidPoll=true;
 $Reason="<p>";
 $OldCands=$Cands;
 $Cands=0;
 $OldSeats=$Seats;
 $Seats=min(max(1,1*$_POST["seats"]),$AllowedCands-1);

 if (array_key_exists("title",$_POST))
 {
  $Title=htmlentities(stripslashes(trim($_POST["title"])));
  $Title=str_replace("&amp;","&",$Title);
  $Title=str_replace("&lt;","<",$Title);
  $Title=str_replace("&gt;",">",$Title);
  $Title=str_replace("&quot;","'",$Title);
  $StrOrig=$Title;
  $Title=BadString($Title);
  if ($StrOrig!=$Title || $Title=="")
  { $ValidPoll=false;
  $Reason.="The title is blank or contained disallowed characters,";
  $Reason.=" which have been removed.  Please check.<br>";
 } 
 } else { $ValidPoll=false; $Reason.="No title was provided.<br>"; }

 if (array_key_exists("homelink",$_POST))
 {
  $HomeLink=htmlentities(stripslashes(trim($_POST["homelink"])));
  $HomeLink=str_replace("&amp;","&",$HomeLink);
  $StrOrig=$HomeLink;
  $HomeLink=BadString($HomeLink);
  if ($StrOrig!=$HomeLink)
  { $ValidPoll=false;
  $Reason.="The home page link contained disallowed characters,";
  $Reason.=" which have been removed.  Please check.<br>";
  } 
 }

 if ($HomeLink!="" && strstr($HomeLink,"http://")===false)
    $HomeLink="http://".$HomeLink;

 $Email=$CreatorMail;
 $OldExclStr=$ExclStr;
 $ExclStr="";

 if (array_key_exists("link",$_POST))
 {
  $Link=strip_tags(stripslashes(trim($_POST["link"])));
  if (strlen($Link)>0)
  {
   if ($Link=="@home") 
   {
    $CastLink=$HomeLink;
   }
   else
   {
    $LabelOrig=$Link;
    $Link=BadLabel($Link);
    if ($LabelOrig!=$Link)
    {
     $ValidPoll=false;
     $Reason.="The link label you provided is invalid.<br>";
    }
   }
  }
  else { $Link=""; }
 }
 else
 { $Link=""; }

 if (array_key_exists("samevoters",$_POST))
 {
  $Samevtr=strip_tags(stripslashes(trim($_POST["samevoters"])));
  $Samevtr=str_replace(" ","",$Samevtr);
  if (strlen($Samevtr)>0)
  {
   $Samevtrcheck=explode(",",$Samevtr);
   $hasme=false;
   foreach ($Samevtrcheck as $sv)
   {
    $LabelOrig=$sv;
    $sv1=BadLabel($sv);
    if ($LabelOrig!=$sv1)
    {
     $ValidPoll=false;
     $Reason.="A same-voters-as label you provided is invalid.<br>";
    }
    else if ($sv1==$Pollname) $hasme=true;
   }

   if (($RegVoters>0 || (array_key_exists("maillist",$_POST) &&
       strlen(trim($_POST['maillist']))>0)) && !$hasme)
   {
    $ValidPoll=false;
    $Reason.="Your poll has its own voters and shares voters with other polls.";
    $Reason.="If you really want this, list your poll's name in the &quot;accept voters from&quot; box.<br>";
   }
  }
  else { $Samevtr=""; }
 }
 else
 { $Samevtr=""; }

 if (array_key_exists("expdy",$_POST))
 { $dy=min(max(1*$_POST["expdy"],1),31); }
 if (array_key_exists("expyr",$_POST))
 { $yr=min(max(1*$_POST["expyr"],1970),9999); }
 if (array_key_exists("expmo",$_POST))
 {
  $mo=substr($_POST["expmo"],0,3);
  $now2=getdate(strtotime($dy."-".$mo."-".$yr));
  $mo=$now2["mon"];
 }
 $invis=false;
 $nores=false;
 $respw=false; 
 $useads=false;
 if (array_key_exists("invis",$_POST)) { $invis=($_POST["invis"]=="on"); }
 if (array_key_exists("nores",$_POST)) { $nores=($_POST["nores"]=="on"); }
 if (array_key_exists("respw",$_POST)) { $respw=($_POST["respw"]=="on"); }
 if (array_key_exists("ads",$_POST)) { $useads=($_POST["ads"]=="on"); }
 if (array_key_exists("usekeys",$_POST))
  { $usekeys=($_POST["usekeys"]=="on"); }

 if (array_key_exists("delay",$_POST))
 {
  switch(trim($_POST["delay"]))
  {
   case "none": $DelayTime=0; $DelayIndex=3; break;
   case "5 minutes": $DelayTime=600; $DelayIndex=2; break;
   case "1 hour": $DelayTime=3600; $DelayIndex=1; break;
   default: $DelayTime=86400; $DelayIndex=0; break;
  }
 }

 $Name[0]="";
 for ($i=1; $i<=$AllowedCands; $i++)
 {
  if (array_key_exists("cand".$i,$_POST))
  {
   $TempName=htmlentities(stripslashes(trim($_POST["cand".$i])));
   $TempName=str_replace("&amp;","&",$TempName);
   $TempName=str_replace("&lt;","<",$TempName);
   $TempName=str_replace("&gt;",">",$TempName);
   $TempName=str_replace("&quot;","'",$TempName);
   $StrOrig=$TempName;
   $TempName=BadString($TempName);
   if ($TempName!="" && $StrOrig!=$TempName)
   {
    $ValidPoll=false;
    $Reason.="Candidate name #".($Cands+1)." contained disallowed characters,";
    $Reason.=" which have been removed.  Please check.<br>"; 
   }
   if ($TempName!="")
   {
    $Cands++;
    $Name[$Cands-1]=$TempName;
    if (array_key_exists("excl".$i,$_POST) && 
        $_POST["excl".$i]=="on")
    {
     if ($ExclStr!="") { $ExclStr.=","; }
     $ExclStr.=($Cands-1);
    }

   }
  }
  else 
  { $ValidPoll=false; $Reason.="Candidate information was lost.<br>"; } 
 } 

 if ($Cands<$Seats || $Cands<$OldCands)
{ $ValidPoll=false; $Reason.="Not enough candidates were provided.<br>"; } 

 if (array_key_exists("balfile",$_POST))
 {
 switch ($_POST["balfile"])
 {
  case "standard":
   $BalFile="dcballot.php"; break;

  case "nojava":
   $BalFile="dcballot.php?bb=on"; break;

  case "hgrid":
   $BalFile="dcgridhballot.php"; break;

  case "vgrid":
   $BalFile="dcgridvballot.php"; break;

  case "bullet":
   $BalFile="dcbullet.php"; break;

  default:
   $BalFile=$BallotFile; break;
 }
 } else { $BalFile=$BallotFile; }

 if (!(array_key_exists("balrot",$_POST) &&
     $_POST["balrot"]=="on"))
 {
  if (strstr($BalFile,"?")===false)
  { $BalFile.="?norot=on"; }
  else
   if (strstr($BalFile,"norot=on")===false)
   { $BalFile.="&norot=on"; }
 }

 if (array_key_exists("resfile",$_POST))
 {
 switch ($_POST["resfile"])
 {
  case "bar":
   $ResFile="dcresults.php"; break;

  case "table":
   $ResFile="dcresults.php?type=table"; break;

  case "pies":
   $ResFile="dcpies.php"; break;

  case "sum":
   $ResFile="dcresultsum.php"; break;

  default:
   $ResFile=$ResultFile; break;
 }
 } else { $ResFile=$ResultFile; }

 if (array_key_exists("castbal",$_POST))
 {
 switch ($_POST["castbal"])
 {
  case "standard":
   $castbl="dcballot.php"; break;

  case "no sorting":
   $castbl="dcballot.php?bb=on"; break;

  case "horiz. grid":
   $castbl="dcgridhballot.php"; break;

  case "vert. grid":
   $castbl="dcgridvballot.php"; break;
  
  case "yes or no":
   $castbl="dcbullet.php"; break;

  default:
   $castbl=$OldCastLink; break;
 }
 } else { $castbl=$OldCastLink; }

 if (!(array_key_exists("castbalrot",$_POST) &&
     $_POST["castbalrot"]=="on"))  
 {
  if (strstr($castbl,"?")===false)
  { $castbl.="?norot=on"; }
  else { $castbl.="&norot=on"; }
 }

$PollQS="poll=".$Pollname;
$CastQS="poll=".$Link;   

if (strstr($BalFile,"poll=")===false)
{
 if (strstr($BalFile,"?")===false)
  { $BalFile.="?".$PollQS; }
  else { $BalFile.="&".$PollQS; }
}

if (strstr($ResFile,"poll=")===false)
{
 if (strstr($ResFile,"?")===false)
  { $ResFile.="?".$PollQS; }
  else { $ResFile.="&".$PollQS; }
}

if (strstr($castbl,"poll=")===false)
{
 if (strstr($castbl,"?")===false)
  { $castbl.="?".$CastQS; }
  else { $castbl.="&".$CastQS; }
}
if ($Link=="@home")
{
 if (strstr($HomeLink,"http://")===false) $castbl=$HomeLink;
 else $castbl=$HomeFile;
}

 $NumLines=0;
 $Parsefail=false;
 $PrevEntry=NULL; 
 if (array_key_exists("maillist",$_POST))
 {
  $ListStr=trim($_POST["maillist"]);
  $ListStr=strtr($ListStr,chr(10),chr(13));
  $ListStr=str_replace(chr(13).chr(13),chr(13),$ListStr);
  $List=explode(chr(13),$ListStr);
  $NumLines=count($List);
  if ($NumLines>$AllowedVoters)
  { $ValidPoll=false; $Reason.="Too many registered voters were listed.<br>"; }
  else
  {
   for ($i=0; $i<$NumLines; $i++)
   {
    $List[$i]=trim($List[$i]);
    $List[$i]=trim($List[$i],",");
    $ListOrig=$List[$i];
    $List[$i]=BadEmail($List[$i]);
    if ($ListOrig!=$List[$i])
    { if (($i==$NumLines-1) && (strlen($List[$i])<4))
      { $NumLines--; } else { $Parsefail=true; } }
    if (!$Parsefail) { $PrevEntry=$List[$i]; } 
   }
  }
 }
 if ($Parsefail)
 { $ValidPoll=false;
 $Reason.="The voter list contains an improperly formatted email address.<br>"; 
 }
}
// -------------------------------------------done preprocessing form data
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<META http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>DemoChoice Poll Editor</title>
<script language="javascript" type="text/javascript">
<!--
function checkCR(evt) {
var evt = (evt) ? evt : ((event) ? event : null);
var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
if ((evt.keyCode == 13) && (node.type=="text")) {return false;}
}
document.onkeypress = checkCR;
//-->
</script>
</head>
<body bgcolor=white text=black>
<table border=0 cellspacing=0 cellpadding=4>
<tr><td><img src="img/dclogo40.gif" alt=""></td>
<td><font size=5 face="Helvetica">DemoChoice Poll Editor</font></td>
</tr></table><p>
<?php 
if ($Submitted)
{
 if ($ValidPoll)
 {

  $MailText="Name: ".$Creator.$cr."Email: ".$Email.$cr;
  $MailText.="Title: ".strip_tags($Title).$cr."Label: ".$Pollname.$cr;
  $MailText.="http://www.demochoice.org/dcballot.php?poll=".$Pollname.$cr;

  $fp=fopen($FilePath.$Pollfile."_configtemp.txt","w+");
  if ($invis)
  { fputs($fp,"<INVISIBLE>".$cr); $MailText.="Hidden".$cr; }

  fputs($fp,"<TITLE> ".$Title.$cr);
  echo "Title: ".$Title."<br>";
  if ($RegVoters+$NumLines>0)
  {
   fputs($fp,"<INVITE> ".($RegVoters+$NumLines).$cr);
   echo ($RegVoters+$NumLines)." eligible voters<br>";
   $MailText.=$RegVoters."+".$NumLines." voters are eligible.".$cr;
  }
  else
  {
   fputs($fp,"<DELAY> ".$DelayTime.$cr);
   $MailText.="Delay time ".$DelayTime.$cr;
  }
  if ($NumLines==0) $usekeys=false;

  if ($Samevtr!=$Pollname && strlen(trim($Samevtr))>0)
  {
   fputs($fp,"<SAMEVOTERS> ".$Samevtr.$cr);
   echo "Using voters from <a href=dcballot.php?poll=".$Samevtr.">";
   echo $Samevtr."</a><br>";
   $MailText.="Using voters from ".$Samevtr;
   if ($Samevtr!=$VotersFrom) $MailText.=" (was ".$VotersFrom.")";
   $MailText.=$cr;
  }

  if ($UseKey || $usekeys)
  { fputs($fp,"<USEKEY>".$cr); }

  if ($nores)
  { fputs($fp,"<WITHHOLD>".($respw?" pw":"").$cr); }
  else if ($respw)
  { fputs($fp,"<WITHHOLD> pw".$cr); }


  if ($useads)
  {
   if ($invis) echo "No ads will be shown because this is a hidden poll.<br>";
   else fputs($fp,"<SHOWADS>".$cr);
  }

  if ($Seats>1 && !$DoRecycle && $OldSeats!=1)
  { fputs($fp,"<UNRECYCLE>".$cr); }

  if (substr($InfoFile,0,8)!="info.php")
  { fputs($fp,"<INFOFILE> ".$InfoFile.$cr); }

  if ($HomeLink!="")
  { fputs($fp,"<HOMEFILE> ".$HomeLink.$cr); }

  fputs($fp,"<EDITPW> ".$pwd.",".$Email.",".$Creator.$cr);
  fputs($fp,"<SEATS> ".$Seats.$cr);
  echo "Seats: ".$Seats."<br>";
  $MailText.="Winners: ".$Seats.$cr;
  fputs($fp,"<EXPIRE> ".strtotime($yr."-".$mo."-".$dy).$cr);
  echo "Expires: ".$yr."-".$mo."-".$dy."<br>";
  $MailText.="Expires: ".$yr."-".$mo."-".$dy.$cr; 

  for ($i=0; $i<$Cands; $i++)
  {
   fputs($fp,"<CANDIDATE> ".$Name[$i].$cr);
   echo "Candidate: ".$Name[$i]."<br>";
   $MailText.="Candidate: ".strip_tags($Name[$i]).$cr;
  }

  if ($ExclStr!="")
  {
   fputs($fp,"<EXCLUDE> ".$ExclStr.$cr);
   $MailText.="Excluding ".$ExclStr.$cr;
  }

  if ($Link!="")
  {
   fputs($fp,"<CASTLINK> ".$castbl.$cr);
   if ($Link!="@home")
   {
    echo "Links to the poll named <a href=".$castbl.">";
    echo $Link."</a> after a vote is cast</br>";
   }
   $MailText.="Links to ".$Link;
  }
  if ($BalFile!="dcballot.php?".$PollQS)
  { fputs($fp,"<BALLOTFILE> ".$BalFile.$cr); }
  if ($ResFile!="dcresults.php?".$PollQS)
  { fputs($fp,"<RESULTFILE> ".$ResFile.$cr); }

  fclose($fp);
  unlink($FilePath.$Pollfile."_config.txt");
  rename($FilePath.$Pollfile."_configtemp.txt",
         $FilePath.$Pollfile."_config.txt");
  chmod($FilePath.$Pollfile."_config.txt",0700);
  $MailText.=$cr;

  $subj=$Creator." edited ".$Pollname;
  if ($NumLines>0 && ($UseKey || $usekeys))
  {
   $subj="KEYS for ".$subj;
   $MailText.=$cr.$AbsURL.$KeyFile."?poll=".$Pollname.$cr;
  }
  $from = "From: ".$AdminEmail.$cr."Reply-To: ".$AdminEmail;
  if ($MailOn) mail($AdminEmail,$subj,html_entity_decode($MailText),$from);
  //echo "<hr width=50%><pre>To: ".$Email.$cr.$from.$cr.$subj.$cr.$MailText."</pre><hr width=50%>";

/*
  $fp=fopen($FilePath."setuplog.txt","a");
  fputs($fp,$MailText);
  fclose($fp);
*/
  if ($NumLines>0)
  {
   $fp=fopen($FilePath.$Pollfile."_registrants.txt","a+");
   if ($UseKey || $usekeys)
   {
    if (file_exists($FilePath.$Pollfile."_newkeys.txt"))
     $fp2=fopen($FilePath.$Pollfile."_newkeys.txt","a+");
    else
     $fp2=fopen($FilePath.$Pollfile."_newkeys.txt","w+");
   }
   foreach ($List as $li => $e)
   {
    $regout=$e;
    if ($UseKey || $usekeys)
    {
     $nukey[$li]=substr(str_shuffle("abcdefghijklmnopqrstuvwxyz1234567890"),0,8);
     $regout.="|".$nukey[$li];
     fputs($fp2,$regout.$cr);
    }
    fputs($fp,$regout.$cr);
   }
   fclose($fp);
   // chmod($FilePath.$Pollfile."_registrants.txt",0700);
   if ($UseKey || $usekeys) fclose($fp2);
   if ($usekeys)
   {
    fclose($fp2);
    chmod($FilePath.$Pollfile."_newkeys.txt",0700);
   }
   echo $NumLines." voter";
   if ($NumLines>1) { echo "s were "; } else { echo " was "; }
   echo "added. ";
  }

 $subjt=strip_tags($Title);
 $subjt=str_replace("&quot;",chr(34),$subjt);
 $subjt=str_replace("&quot;",chr(60),$subjt);
 $subjt=str_replace("&quot;",chr(62),$subjt);
 $subjt2=strlen($subjt)>60 ? substr($subjt,0,60)."..." : $subjt;

 if (!($UseKey || $usekeys))
 {
  if ($NumLines>0)   
  {
   $mailto.=$Email."?bcc=";
   foreach ($List as $e) { $mailto.=$e.","; }
   $mailto=rtrim($mailto,",");
   $mailto.="&amp;";
  }
  else { $mailto="?"; }
  $mailto.="subject=".rawurlencode("DemoChoice Poll: ".$subjt2);
  $mailtobody="You are invited to make your voice heard in a DemoChoice poll:".$cr.$cr;
  $mailtobody.=$subjt.$cr.$cr."To participate, go to:".$cr.$cr;
  $mailtobody.=$AbsURL.$BalFile.$cr.$cr;
  if ($RegVoters+$NumLines>0)
  {
   $mailtobody.="You will need to enter your email address. ";
   $mailtobody.="(Your email address will only be used to confirm your vote.)".$cr.$cr;
  }
  $mailtobody.="Please try it!  Your opinion is valued.  ";
  $mailtobody.="The final results will be available at".$cr.$cr;
  $mailtobody.=$AbsURL.$ResFile.$cr.$cr;
  $mailtobody.="on ".$dy." ".$mo." ".$yr.".  ";
  $mailtobody.="Be sure to cast your vote before then!".$cr.$cr."Thanks,".$cr;
  $mailtobody.=$Creator;
  $mailto.="&amp;body=".rawurlencode($mailtobody);
?>
<p>
<a href="mailto:<?=$mailto; ?>">Announce</a> your poll to new voters via 
email<br>
(This link sets up a message in your local email program that you can send.)
<?php
  if ($VotersFrom!=$Pollname)
  {
   echo "<br>If you use this, be sure to add the recipients to the registration list ";
   echo " of this poll or one that it shares voters with (".$VotersFrom.").";
  }
 } // not usekey
 else
 {
  if ($NumLines>0)
  {
  ?>
Your poll uses keys to authenticate votes.<br>
The keys will be distributed for you after review by a moderator.<br>  
If you have any special instructions, please          
<a href="fbkf.php">contact us</a>.
</p>
<?php
 } // NumLines>0
 } // usekeys

 ?>
<p>

 <p>The web address for your poll is:<p>
<?php
  echo $AbsURL.$BalFile."<p>";
 if ($NumLines>0)
 {
?>
<B>WARNING</b>: If you need to edit your poll further, DO NOT use your 
browser's &quot;back&quot; button.  Instead,
<a href="<?=$AbsURL.$ThisFile; ?>">use this link</a>.<p>
<?php
 }
 if ($Seats!=$OldSeats || $ExclStr!=$OldExclStr || $Cands!=$OldCands)
 {
?>
<b>You are not done yet!</b><p>
Because you changed the number of candidates or winners, 
the tally must be updated.  Just click on the 
link:<p>
<center><font size=+1><b>
<a href="<?=$AbsURL."dccast.php?poll=".$Pollname ?>">Update the tally</a>
</b></font></center>
<?php
 } else {
?>
<p>
 <a href="<?php echo $BalFile; ?>">Vote</a> |
 <a href="<?php echo $ResFile; ?>#Round1">View Results</a> |
 <a href="index.php">DemoChoice Main Page</a>
<?php 
  } // need to update tally
 } //ValidPoll
 else
 {
  if ($Parsefail && (strlen($PrevEntry)>0))
  { echo "The email address after ".$PrevEntry." appears to be invalid."; }
  else {
?>
 This poll could not be created. This is because:
<?php echo $Reason; ?>
<?php if (!(strstr($Reason,"character")===false)) { ?>
For fancy characters like &ouml; &iexcl; &copy;, use the appropriate
<a href=spchar.html>HTML entity code</a>.
<?php } ?>
<br>Incorrect entries or blank lines may be marked with &quot;*&quot;.
<p>Please try again.  If 
you still can't solve the problem, please
<a href="fbkf.php">contact us</a>.<p>
<a href="dcedit.php?poll=<?=$Pollname; ?>">Try again</a>
<?php
  } // else Parsefail 
 } //ValidPoll
} // submitted
if (!$Submitted || ($Submitted && !$ValidPoll))
{
 if (!$Submitted) { 
// -------------------------------------------------------main form
?>
<p style="margin-left: 5%; margin-right: 5%;">
If you need more features than are allowed by this form, such as more 
candidates,
or more than <?=$AllowedVoters ?> registered voters, send a
<a href=fbkf.php>request</a>.
HTML (links, photos, simple formatting) can be embedded in titles and candidate names using 
this form - see the <a href="#htmltips">tips</a> at the bottom of the page.
</p>
<?php } ?>
<form action="<?=$ThisFile ?>" method="post">
<input type=hidden name=pwd value=<?=$pwd ?>>
<table border=0 cellspacing=20 cellpadding=5><tr valign=top><td>
<table border=0 cellspacing=0 cellpadding=5>
<tr><td colspan=2 bgcolor=f0d0e0><b>Basic setup</b></td></tr>
<tr><td bgcolor=f0d0e0>Title:</td>
<td bgcolor=f0d0e0>
<input type="text" size=40 maxlength=250 name="title"
 value="<?=$Submitted?$Title:htmlspecialchars($Title[0]); ?>">
</td></tr>
<tr><td bgcolor=f0d0e0>Home page:</td>
<td bgcolor=f0d0e0>
<input type="text" size=40 maxlength=250 name="homelink"
 value="<?=$HomeLink; ?>">
</td></tr>
<tr><td colspan=2 bgcolor=f0d0e0>
<font size=-1>
If you are linking from your own website to this poll,<br>
put your website address in the &quot;home page&quot; box.<br>
&quot;Main page&quot; links on DemoChoice will lead back to your site.</font> 
</td></tr>
<tr><td colspan=2 bgcolor=f0d0e0>
Number of candidates to elect: <select name="seats">
<?php 
 for ($i=1; $i<=$MaxWin; $i++)
 {
  echo "<option";
  if ($i==$Seats)
  { echo " selected"; }
  echo ">".$i;
 } 
?>
</select></td></tr>
<tr><td bgcolor=f0d0e0>Expires:</td>
<td bgcolor=f0d0e0><select name="expdy">
<?php
 for ($dayi=1; $dayi<=31; $dayi++)
 {
  echo "<option";
  if ($dayi==$dy) { echo " selected"; }
  echo ">".$dayi;
 }
?>
</select>
<select name="expmo">
<option <?php if ($mo==1) { echo "selected"; } ?>>Jan 
<option <?php if ($mo==2) { echo "selected"; } ?>>Feb
<option <?php if ($mo==3) { echo "selected"; } ?>>Mar
<option <?php if ($mo==4) { echo "selected"; } ?>>Apr
<option <?php if ($mo==5) { echo "selected"; } ?>>May
<option <?php if ($mo==6) { echo "selected"; } ?>>Jun
<option <?php if ($mo==7) { echo "selected"; } ?>>Jul
<option <?php if ($mo==8) { echo "selected"; } ?>>Aug
<option <?php if ($mo==9) { echo "selected"; } ?>>Sep
<option <?php if ($mo==10) { echo "selected"; } ?>>Oct
<option <?php if ($mo==11) { echo "selected"; } ?>>Nov
<option <?php if ($mo==12) { echo "selected"; } ?>>Dec
</select>
<select name="expyr">
<?php
  for ($yri=$now["year"]; $yri<$yr; $yri++)
  { echo "<option>".$yri; }
?>
<option selected><?php echo $yr; ?>
<?php
  if ($yr==$now["year"]) { echo "<option>".($yr+1); }
?>
</select></td></tr>
<tr><td colspan=2 align=left bgcolor=f0d0e0>
<font size=2>
Votes are no longer counted after the start of the expiration day.<br>
Polls may be deleted a week or two after they expire.
<a target=_blank href="reginfo.php#expire">details</a>
</font>
</td></tr>
<tr><td colspan=2 align=left bgcolor=e0d0f0>
<b>Voter Registration</b><br><font size=2>
<?php if ($Invite) { echo $RegVoters; ?>
 voters are already registered.<br>
Enter email addresses of additional voters, if desired.<br>
<?php } else { ?>
Enter the email addresses of those registered to vote.<br>
If you want to allow anyone to vote, leave this empty.<br>
Addresses are used only to confirm votes.<br>
Be sure to <a target=_blank href=reginfo.php>read the instructions</a>!
<?php } ?>
</font><br>
<textarea name="maillist" rows=12 cols=30><?php
if ($Submitted)
{
 $ii=min($AllowedVoters,$NumLines);
 for ($i=0; $i<$ii; $i++)
 {  echo $List[$i].$cr; }
}
?></textarea><br><?php
if ($RegVoters>0)
{
 echo "This poll uses <a target=_blank href=reginfo.php>";
 if ($UseKey) echo "registration keys";
 else echo "email authentication";
 echo "</a>.";
}
else
{
?>
<input type="checkbox" name="usekeys"
<?php if ($Submitted && $usekeys) echo "checked"; ?>
> Use registration keys
[<a target=_blank href=reginfo.php>read this first!</a>]<br>
<font size=2>
For polls that don't use registration, DemoChoice can block<br>
votes from the same computer for a certain period of time.<br>
This method sometimes blocks legitimate voters, so the<br>
delay should not be too long.<br> You can adjust the delay to:
<select name=delay>
<option <?=($DelayIndex==0)?"selected":"" ?>>1 day
<option <?=($DelayIndex==1)?"selected":"" ?>>1 hour
<option <?=($DelayIndex==2)?"selected":"" ?>>5 minutes
<option <?=($DelayIndex==3)?"selected":"" ?>>none
</select></font>
<?php } ?>
</td></tr>

<tr><td colspan=2 bgcolor=d0e0f0>Optional <b>fancy features</b> for sophisticated users</td></tr>

<tr>
<td colspan=2 bgcolor=d0e0f0>Preferred ballot type:</td>
</tr>
<tr>
<td bgcolor=d0e0f0>&nbsp;</td>
<td bgcolor=d0e0f0>
<input type=radio name="balfile" value="no change" 
<?php if (!$Submitted || ($Submitted && $_POST["resfile"]=="no change")) echo "checked"; ?>
> no change<br>
<input type=radio name="balfile" value=standard
<?php if ($Submitted && $_POST["balfile"]=="standard") echo "checked"; ?>
>
<a href="dcballot.php">standard</a><br>
<input type=radio name="balfile" value=nojava
<?php if ($Submitted && $_POST["balfile"]=="nojava") echo "checked"; ?>
>
<a href="dcballot.php?bb=on">no sorting</a><br>
<input type=radio name="balfile" value=hgrid
<?php if ($Submitted && $_POST["balfile"]=="hgrid") echo "checked"; ?>
>
<a href="dcgridhballot.php">horizontal grid</a><br>
<input type=radio name="balfile" value=vgrid
<?php if ($Submitted && $_POST["balfile"]=="vgrid") echo "checked"; ?>
>
<a href="dcgridvballot.php">vertical grid</a><br>
<input type=radio name="balfile" value=bullet
<?php if ($Submitted && $_POST["balfile"]=="bullet") echo "checked"; ?>
>
<a href="dcbullet.php?poll=yesno">yes or no</a><br>
</td>
</tr>
<tr>
<td colspan=2 bgcolor=d0e0f0>
<input type=checkbox name=balrot
<?php if (!$Submitted || ($Submitted && $_POST["balrot"]=="on")) echo "checked"; ?>
>
periodically shuffle candidate order (if changed)
</td>
</tr>
<tr>
<td colspan=2 bgcolor=d0e0f0>Preferred results display:</td>
</tr>
<tr>
<td bgcolor=d0e0f0>&nbsp;</td>
<td bgcolor=d0e0f0>
<input type=radio name="resfile" value="no change"
<?php if (!$Submitted || ($Submitted && $_POST["resfile"]=="no change")) echo "checked"; ?>
> no change<br>
<input type=radio name="resfile" value=bar
<?php if ($Submitted && $_POST["resfile"]=="bar") echo "checked"; ?>
>
<a href="dcresults.php">bar charts</a><br>
<input type=radio name="resfile" value=table
<?php if ($Submitted && $_POST["resfile"]=="table") echo "checked"; ?>
>
<a href="dcresults.php?type=table">numerical table</a><br>
<input type=radio name="resfile" value=pies
<?php if ($Submitted && $_POST["resfile"]=="pies") echo "checked"; ?>
>
<a href="dcpies.php">pie charts</a><br>
<input type=radio name="resfile" value=sum
<?php if ($Submitted && $_POST["resfile"]=="sum") echo "checked"; ?>
>
<a href="dcresultsum.php">summary bar chart</a><br>
</td>
</tr>
<tr>
<td bgcolor=e0d0f0>Next poll:</td>
<td bgcolor=e0d0f0><input type="text" size=10 maxlength=10 name="link"
 value="<?=$Link; ?>">
</td></tr>
<tr><td colspan=2 bgcolor=d0e0f0>
<font size=2>Provide the label of a subsequent poll question here if you wish.<br>
Enter &quot;@home&quot; to direct voters to your home page (as entered above) or<br>
to the DemoChoice main page when they are done.</font>
</tr>
<tr>
<td bgcolor=d0e0f0>Accept voters from: </td>
<td bgcolor=d0e0f0><input type="text" size=10 maxlength=90 name="samevoters"
value="<?php if ($Submitted) echo $Samevtr; else if ($VotersFrom!=$Pollname) echo $VotersFrom; ?>"
></td></tr>
<tr><td colspan=2 bgcolor=d0e0f0>
<?php
if ($VotersFrom!=$Pollname)
echo "<b>WARNING:</b> Do not remove polls from this list if you have<br> already announced the poll to those voters.<br>";
?>
<font size=2>For multi-question polls with registered voters, register your<br>
voters in the first question, and list that poll in this box for subsequent one.<br>
If you have both shared voters and voters exclusive to this question,<br>
include this question's label in the box.
See <a target=_blank href=reginfo.php>detailed instructions</a>.<br>
</font>
</td></tr>
<tr>
<td colspan=2 bgcolor=d0e0f0>
Ballot type for next poll: <select name=castbal>
<option
<?php if (!$Submitted || ($Submitted && $_POST["castbal"]=="standard")) echo "selected"; ?>
>standard
<option
<?php if ($Submitted && $_POST["castbal"]=="no sorting") echo "selected"; ?>
>no sorting
<option
<?php if ($Submitted && $_POST["castbal"]=="horiz. grid") echo "selected"; ?>
>horiz. grid
<option
<?php if ($Submitted && $_POST["castbal"]=="vert. grid") echo "selected"; ?>
>vert. grid
<option
<?php if ($Submitted && $_POST["castbal"]=="yes or no") echo "selected"; ?>
>yes or no
</select><br>
<input type=checkbox name=castbalrot 
<?php if (!$Submitted || ($Submitted && $_POST["castbalrot"]=="on")) echo "checked"; ?>
>
Shuffle candidates on next poll's ballot (if changed)
</td></tr>

<tr><td bgcolor=f0d0e0 colspan=2>
<br>Privacy:<br>
<font size=2>
<input type="checkbox" name="invis"
<?php if ($Submitted?$invis:$InvisiPoll) { echo "checked"; } ?>>
 Hide poll from directory<br>
<!-- The following two options require voter registration.<br> -->
<input type="checkbox" name="nores"
<?php if ($Submitted?$nores:$Withholdtag) { echo "checked"; } ?>>
 Hide results until expiration<br>
<input type="checkbox" name="respw"
<?php if ($Submitted?$respw:$Resultpwd) { echo "checked"; } ?>>
 Password protected results<br>
Add &quot;&amp;who=asdfghjk&quot; to the end of the URL of the results page,<br>
 where &quot;asdfghjk&quot; is your password.<br><br>
<input type="checkbox" name="ads"
<?php if ($Submitted?$useads:$ShowAds) { echo "checked"; } ?>>
 Allow <a href=adinfo.php target=_blank>ads</a><br></font>
</td></tr>
<tr><td colspan=2 bgcolor=e0f0d0>
<font size=2>
Please check your poll for mistakes.<p>
<input type="submit" name="submit" value="Edit Poll">
or
<a href='<?=$HomeFile; ?>'>cancel </a><p></font>
</td></tr>

</table></td><td>
<table border=0 cellspacing=0 cellpadding=5><tr>
<td bgcolor=e0f0d0>
<b>Candidates</b><br>
<font size=2>Edit or add candidates.<br>They will 
appear in random 
order on the ballot.<br>
Do not delete candidates;<br> use the 
"exclude" checkbox instead.</font><p>
<table border=0 cellspacing=1 cellpadding=2>
<tr><td>&nbsp;</td><td><font size=2>Exclude</font></td></tr>
<?php 
 for ($i=1; $i<=$AllowedCands; $i++)
 {
  echo "<tr><td>";
  echo "<input type=text size=40 maxlength=512 name=cand".$i;
  if ($i<=$Cands)
  { echo " value=".chr(34).htmlspecialchars($Name[$i-1]).chr(34); }
  echo "></td><td> ";
  if ($i<=$Cands)
  {
   echo "<input type=checkbox name=excl".$i;
   if ($Submitted?($_POST["excl".$i]=="on"):$Excl[$i-1])
   { echo " checked"; }
   echo ">";
  }
  echo " </td></tr>";
 } 
?>
</table>
</td></tr>
</table>
</td></tr></table>
</form>
<p style="margin-left: 5%; margin-right: 5%;">
<b>Tips:</b> if you need to submit multiple votes, use the following address:<br>
<a 
href="<?=((strstr($BallotFile,"http:")===false)?$AbsURL:"").$BallotFile."&amp;who=".$pwd; 
?>">
<?=((strstr($BallotFile,"http:")===false)?$AbsURL:"").$BallotFile."&amp;who=".$pwd; 
?></a><br>
You can use your browser's "back" button after each vote.
</p>

<p style="margin-left: 5%; margin-right: 5%;">
If you are hiding the results of your poll, this link allows you to peek:<br>
<a 
href="<?=$AbsURL."dcresultsum.php?poll=".$Pollname."&amp;who=".$pwd; ?>">
<?=$AbsURL."dcresultsum.php?poll=".$Pollname."&amp;who=".$pwd; ?>
</a>
</p>

<p style="margin-left: 5%; margin-right: 5%;">
<a name="htmltips">
<b>HTML tips:</b></a>
to add pictures and links to your ballot, make your candidate names look like 
this:</p>
<pre style="margin-left: 5%; margin-right:5%">
&lt;a target=_blank href='http://www.candidate1.org'&gt;
&lt;img style='vertical-align: top;' src='http://www.candidate1.org/candidate1.jpg' width=66 height=50&gt;&lt;/a&gt;
&amp;nbsp;&amp;nbsp;&amp;nbsp;
&lt;a target=_blank href='http://www.candidate1.org'&gt;
&lt;font size=+1&gt;Candidate 1&lt;/font&gt;&lt;/a&gt;
</pre>
<p style="margin-left: 5%; margin-right: 5%;">
It should be all one line, with no line breaks as shown here.  You can use just the last two lines 
above if you want to use a link but not a picture.<br><br>
Use <a href=spchar.html>HTML entity codes</a> for special characters like &ouml; &lt; &quot.<br>
If you need quotes within HTML tags, use single quotes ' and not &quot;.
</p>
<hr>
DemoChoice &copy;2001
<a href="http://www.laweekly.com/news/what-democracy-votes-like-2135837">Dave Robinson</a>
<?php
 } // form not submitted
} // password OK
?>
</body>
</html>
