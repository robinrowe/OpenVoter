<?php
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Form allowing web user to create a poll
*/

$AdminEmail="you@yoursite.org>";
$FirstChoiceMod="you@yoursite.org";

$KeyFile="keydist.php";
$MailOn=true;
$AllowedCands=32;
$AllowedVoters=12000;
$MaxWin=12;
$ThisFile="dcsetup.php";

if (array_key_exists("SCRIPT_FILENAME",$_SERVER))
{ $FilePath=str_replace("\\\\","/",$_SERVER["SCRIPT_FILENAME"]); }
else
{ $FilePath=str_replace("\\\\","/",$_SERVER["PATH_TRANSLATED"]); }

if (!(strrpos($FilePath,"/")===false))
{ $FilePath=substr($FilePath,0,strrpos($FilePath,"/")+1)."data/"; }

$AbsURL=$_SERVER["SCRIPT_NAME"];
if (!(strrpos($AbsURL,"/")===false))
{ $AbsURL="http://".$_SERVER["SERVER_NAME"].substr($AbsURL,0,strrpos($AbsURL,"/")+1); }

// Prepare one-month expiration date
$now=getdate(time());
$yr=$now["year"];
$mo=$now["mon"];
$dy=$now["mday"];
$mo++;
if ($mo>12) { $mo=1; $yr++; }

$cr=chr(13).chr(10);
$Submitted=false;
$Pollname="DC";

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
     ($j==34) || ($j==60) || ($j==62)) { $CSOut=true; }
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
 if (strlen($StrIn)>20)
 { $LabelOut=substr($LabelOut,0,20); } 
 return $LabelOut;
} 

if (array_key_exists("submit",$_POST))
{
 $Submitted=true;
 $ValidPoll=true;
 $Reason="<p>";
 $Cands=0;
 $Seats=min(max(1,1*$_POST["seats"]),$AllowedCands-1);
  
 if (array_key_exists("label",$_POST))
 {
  $Pollname=trim($_POST["label"]);
  $LabelOrig=$Pollname;
  $Pollname=BadLabel($Pollname);
  if ($LabelOrig!=$Pollname)
   { $ValidPoll=false;
   $Reason.="The label is blank or contained disallowed characters,";
   $Reason.=" which have been removed.  Please check.<br>";
   }
 } else { $ValidPoll=false; $Reason.="No label was provided.<br>"; }

 if (array_key_exists("title",$_POST))
 {
  $Title=htmlentities(stripslashes(trim($_POST["title"])));
  $Title=str_replace("&amp;","&",$Title);
  $StrOrig=$Title;
  $Title=BadString($Title);
  if ($StrOrig!=$Title || $Title=="")
  { $ValidPoll=false;
  $Reason.="The title is blank or contained disallowed characters,";
  $Reason.=" which have been removed.  Please check.<br>";
 } 
 } else { $ValidPoll=false; $Reason.="No title was provided.<br>"; }

 if (array_key_exists("creator",$_POST))
 {
  $Creator=htmlentities(stripslashes(trim($_POST["creator"])));
  $Creator=str_replace("&amp;","&",$Creator);
  $StrOrig=$Creator;
  $Creator=BadString($Creator);
  if ($StrOrig!=$Creator || $Creator=="")
  { $ValidPoll=false;
  $Reason.="Your name was not provided or contained disallowed characters,";
  $Reason.=" which have been removed.  Please check.<br>"; } 
 } else { $ValidPoll=false; $Reason.="Your name was not provided.<br>"; }

 if (array_key_exists("ml",$_POST))
 {
  $Email=strip_tags(stripslashes(trim($_POST["ml"])));
  if (strlen($Email)>0 && $Email!="no email provided")
  {
   $EmailOrig=$Email;
   $Email=BadEmail($Email);
   if ($Email!=$EmailOrig)
   {
    $ValidPoll=false;
    $Reason.="Your email address appears to be incorrect.<br>";
   }
  }
  else { $Email="no email provided"; }
 }
 else
 { $Email="no email provided"; }

 if (array_key_exists("link",$_POST))
 {
  $Link=strip_tags(stripslashes(trim($_POST["link"])));
  if (strlen($Link)>0)
  {
   $LabelOrig=$Link;
   $Link=BadLabel($Link);
   if ($LabelOrig!=$Link)
   {
    $ValidPoll=false;
    $Reason.="The label provided for the link to the next poll is invalid.<br>";
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
  if (strlen(trim($Samevtr))>0)
  {
   $Samevtrcheck=explode(",",$Samevtr);
   foreach ($Samevtrcheck as $sv)
   {
    $LabelOrig=$sv;
    $hasme=false;
    $sv1=BadLabel($sv);
    if ($LabelOrig!=$sv1)
    {
     $ValidPoll=false;
     $Reason.="A same-voters-as label you provided is invalid.<br>";
    }
    else if ($sv1==$Pollname) $hasme=true;
   }

   if ((array_key_exists("maillist",$_POST) &&
       strlen(trim($_POST['maillist']))>0) && !$hasme)
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
  $now2=getdate(strtotime($dy." ".$mo." ".$yr));
  $mo=$now2["mon"];
 }
 $invis=false;
 $nores=false;
 $usekeys=false;
 $useads=false;
 $dofirst=false;
 if (array_key_exists("invis",$_POST))
  { $invis=($_POST["invis"]=="on"); }
 if (array_key_exists("nores",$_POST))
  { $nores=($_POST["nores"]=="on"); }
 if (array_key_exists("ads",$_POST))
  { $useads=($_POST["ads"]=="on"); }
 if (array_key_exists("dofirst",$_POST))
  { $dofirst=($_POST["dofirst"]=="on"); }
 if (array_key_exists("usekeys",$_POST))
  { $usekeys=($_POST["usekeys"]=="on"); }

 $Name[0]="";
 for ($i=1; $i<=$AllowedCands; $i++)
 {
  if (array_key_exists("cand".$i,$_POST))
  {
   $TempName=htmlentities(stripslashes(trim($_POST["cand".$i])));
   $TempName=str_replace("&amp;","&",$TempName);
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
   }
  } else { $ValidPoll=false; $Reason.="Candidate information was lost.<br>"; } 
 } 

 if ($Cands<$Seats)
{ $ValidPoll=false; $Reason.="Not enough candidates were provided.<br>"; } 

 if (array_key_exists("balfile",$_POST))
 {
 switch ($_POST["balfile"])
 {
  case "nojava":
   $BalFile="dcballot.php?bb=on"; break;

  case "hgrid":
   $BalFile="dcgridhballot.php"; break;

  case "vgrid":
   $BalFile="dcgridvballot.php"; break;

  case "bullet":
   $BalFile="dcbullet.php"; break;

  default:
   $BalFile="dcballot.php"; break;
 }
 } else { $BalFile="dcballot.php"; }

 if (!(array_key_exists("balrot",$_POST) &&
     $_POST["balrot"]=="on"))
 {
  if (strstr($BalFile,"?")===false)
  { $BalFile.="?norot=on"; }
  else { $BalFile.="&amp;norot=on"; }
 }

 if (array_key_exists("resfile",$_POST))
 {
 switch ($_POST["resfile"])
 {
  case "table":
   $ResFile="dcresults.php?type=table"; break;

  case "pies":
   $ResFile="dcpies.php"; break;

  case "sum":
   $ResFile="dcresultsum.php"; break;

  default:
   $ResFile="dcresults.php"; break;
 }
 } else { $ResFile="dcresults.php"; }

 if (array_key_exists("castbal",$_POST))
 {
 switch ($_POST["castbal"])
 {
  case "no sorting":
   $castbl="dcballot.php?bb=on"; break;

  case "horiz. grid":
   $castbl="dcgridhballot.php"; break;

  case "vert. grid":
   $castbl="dcgridvballot.php"; break;
  
  case "yes or no":
   $castbl="dcbullet.php"; break;

  default:
   $castbl="dcballot.php"; break;
 }
 } else { $castbl="dcballot.php"; }

 if (!(array_key_exists("castbalrot",$_POST) &&
     $_POST["castbalrot"]=="on"))  
 {
  if (strstr($castbl,"?")===false)
  { $castbl.="?norot=on"; }
  else { $castbl.="&norot=on"; }
 }

$PollQS="poll=".$Pollname;
$CastQS="poll=".$Link;   

if (strstr($BalFile,"?")===false)
 { $BalFile.="?".$PollQS; }
 else { $BalFile.="&".$PollQS; }

if (strstr($ResFile,"?")===false)
 { $ResFile.="?".$PollQS; }
 else { $ResFile.="&".$PollQS; }

if (strstr($castbl,"?")===false)
 { $castbl.="?".$CastQS; }
 else { $castbl.="&".$CastQS; }


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
    if ($List[$i]!=$ListOrig)
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

 if ($ValidPoll)
 {
  if (file_exists($FilePath.$Pollname."_config.txt"))
  {
   $ValidPoll=false;
   $Reason.="A poll with this label already exists.<br>";
  }
  if (($NumLines>0 || $Samevtr!="") && $Email=="no email provided")
  {
   $ValidPoll=false;
   $Reason="Your email address was not provided but you registered voters.<br>";
  } 
 } 
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<META http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>DemoChoice Poll Setup</title>
<link rel="shortcut icon" href="favicon.ico" type="image/vnd.microsoft.icon">
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
<body bgcolor=white text=black style="font-family: arial;">
<table border=0 cellspacing=0 cellpadding=4>
<tr><td><img src="img/dclogo40.gif" alt=""></td>
<td><font size=5 face="Helvetica">DemoChoice Poll Setup</font></td>
</tr></table><p>
<?php 
if ($Submitted)
{
 if ($ValidPoll)
 {
  $MailText="Name: ".$Creator.$cr."Email: ".$Email.$cr."Title: ".$Title.$cr."Label: ".$Pollname.$cr;
  $MailText.="http://www.demochoice.org/dcballot.php?poll=".$Pollname.$cr;
  $fp=fopen($FilePath.$Pollname."_config.txt","w+");
  if ($invis)
  { fputs($fp,"<INVISIBLE>".$cr); $MailText.="Hidden".$cr; }

  fputs($fp,"<TITLE> ".$Title.$cr);
  echo "Title: ".$Title."<br>";
  
  if ($NumLines>0)
  {
   fputs($fp,"<INVITE> ".$NumLines.$cr);
   echo $NumLines." eligible voters<br>";
   $MailText.=$NumLines." voters are eligible.".$cr;
  } else { $usekeys=false; }

  if ($Samevtr!="")
  {
   fputs($fp,"<SAMEVOTERS> ".$Samevtr.$cr);
   echo "Using voters from ";
   $Samevtrcheck=explode(",",$Samevtr);
   foreach ($Samevtrcheck as $sv)
   {
    echo "<a href=dcballot.php?poll=".$sv.">";
    echo $sv."</a> ";
   }
   echo "<br>";
   $MailText.="Using voters from ".$Samevtr.$cr;
  }

  if ($usekeys)
  { fputs($fp,"<USEKEY>".$cr); }

  if ($nores)
  { fputs($fp,"<WITHHOLD>".$cr); }

  if ($useads)
  {
   if ($invis) echo "No ads will be shown because this is a hidden poll.<br>";
   else fputs($fp,"<SHOWADS>".$cr);
  }

  if ($Email!="no email provided")
  {
   $pswd=substr(str_shuffle("abcdefghijklmnopqrstuvwxyz1234567890"),0,8);
   fputs($fp,"<EDITPW> ".$pswd.",".$Email.",".$Creator.$cr);
   $editmail="Your DemoChoice poll:".$cr.$cr;
   $editmail.="  ".$Title.$cr.$cr;
   $editmail.="has been created.".$cr;
   $editmail.="To edit your poll, go to:".$cr.$cr;
   $editmail.="  ".$AbsURL."dcedit.php?poll=".$Pollname.$cr.$cr;
   $editmail.="and enter ".$pswd." in the form box.".$cr;
   $editmail.="Please be sure that this information is not posted on";
   $editmail.=" the web or forwarded to anyone.".$cr.$cr;
   $editmail.="A ballot for your poll (with a link to results) can be found at:".$cr.$cr;
   $editmail.="  ".$AbsURL."dcballot.php?poll=".$Pollname.$cr.$cr;

   $editmail.="If you can suggest an improvement to this software that";
   $editmail.=" would help it meet your needs, please reply";
   $editmail.=" to this message.".$cr.$cr;
   $editmail.="Thank you for using DemoChoice!".$cr;
   $to=$Email;
   $subj="DemoChoice poll created (".$Pollname.")";
   $from = "From: ".$AdminEmail.$cr."Reply-To: ".$AdminEmail;
   if ($MailOn) mail($to,$subj,html_entity_decode($editmail),$from);
  }

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
   $MailText.="Candidate: ".$Name[$i].$cr;
  }
  if ($Link!="")
  {
   fputs($fp,"<CASTLINK> ".$castbl.$cr);
   echo "Links to the poll named <a href=".$castbl.">";
   echo $Link."</a> after a vote is cast</br>";
   $MailText.="Links to ".$Link;
  }
  if ($BalFile!="dcballot.php?".$PollQS)
  { fputs($fp,"<BALLOTFILE> ".$BalFile.$cr); }
  if ($ResFile!="dcresults.php?".$PollQS)
  { fputs($fp,"<RESULTFILE> ".$ResFile.$cr); }
  fclose($fp);
  chmod($FilePath.$Pollname."_config.txt",0700);
  $MailText.=$cr;
  $fp=fopen($FilePath.$Pollname."_tally.txt","w+");
  fputs($fp,"Ballots   |0");
  fclose($fp);
  chmod($FilePath.$Pollname."_tally.txt",0700);
  $fp=fopen($FilePath.$Pollname."_ballots.txt","w+");
  fclose($fp);
  chmod($FilePath.$Pollname."_ballots.txt",0700);
  $fp=fopen($FilePath.$Pollname."_voters.txt","w+");
  fclose($fp);
  chmod($FilePath.$Pollname."_voters.txt",0700);

  $subj=$Creator." created ".$Pollname;
  if ($NumLines>0 && $usekeys)
  {
   $subj="KEYS for ".$subj;
   $MailText.=$AbsURL.$KeyFile."?poll=".$Pollname.$cr;
  }
  if ($dofirst) $MailText.="Announce to FirstChoice requested".$cr;

  $from = "From: ".$AdminEmail.$cr."Reply-To: ".$AdminEmail;
  if ($MailOn) mail($AdminEmail,$subj,html_entity_decode($MailText),$from);
  // if ($MailOn && $dofirst) mail($FirstChoiceMod,$subj,html_entity_decode($MailText),$from);
  // echo "<hr width=50%><pre>To: ".$Email.$cr.$from.$cr.$subj.$cr.$MailText."</pre><hr width=50%>";

  $fp=fopen($FilePath."setuplog.txt","a");
  fputs($fp,"<CREATED> ".time().$cr);
  fputs($fp,"<TITLE> ".$Title.$cr);
  fputs($fp,"<LINK> ".$AbsURL.$BalFile.$cr);
  if ($dofirst && $NumLines==0) fputs($fp,"<ANNOUNCE>".$cr);
  fclose($fp);

  if ($NumLines>0)
  {
   $fp=fopen($FilePath.$Pollname."_registrants.txt","w+");
   if ($usekeys) $fp2=fopen($FilePath.$Pollname."_newkeys.txt","w+");
   foreach ($List as $li => $e)
   {
    $regout=$e;
    if ($usekeys)
    {
     $nukey[$li]=substr(str_shuffle("abcdefghijklmnopqrstuvwxyz1234567890"),0,8);
     $regout.="|".$nukey[$li];
     fputs($fp2,$regout.$cr);
    }
    fputs($fp,$regout.$cr);
   }
   fclose($fp);
   chmod($FilePath.$Pollname."_registrants.txt",0700);
   if ($usekeys)
   {
    fclose($fp2);
    chmod($FilePath.$Pollname."_newkeys.txt",0700);
   }
  }
?>
 <p>Your poll was successfully created.
<?php
 if ($NumLines>0)
 {
  echo " ".$NumLines." voter";
  if ($NumLines>1) { echo "s are "; } else { echo " is "; }
  echo "now registered.";
 }
 echo "<p>";

 $subjt=strip_tags($Title);
 $subjt=str_replace("&quot;",chr(34),$subjt);
 $subjt=str_replace("&quot;",chr(60),$subjt);
 $subjt=str_replace("&quot;",chr(62),$subjt);
 $subjt2=strlen($subjt)>60 ? substr($subjt,0,60)."..." : $subjt;

 if (!$usekeys)
 {
  if ($NumLines>0)
  {
   $mailto=$Email."?bcc=";
   foreach ($List as $e) { $mailto.=$e.","; }
   $mailto=rtrim($mailto,",");
   $mailto.="&";
  }
  else { $mailto="?"; }
  $mailto.="subject=".rawurlencode("DemoChoice Poll: ".$subjt2);
  $mailtobody="You are invited to make your voice heard in a new poll:".$cr.$cr;
  $mailtobody.=$subjt.$cr.$cr."To participate, go to:".$cr.$cr;
  $mailtobody.=$AbsURL.$BalFile.$cr.$cr;
  if ($NumLines>0 || $Samevtr!="")
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
  $mailto.="&body=".rawurlencode($mailtobody);
?>
<img src=img/dcmailseal30.gif> 
<a href=mailto:<?=$mailto; ?>>Announce</a> your poll via email<br>
(This link sets up a message in your local email program that you can send.)
<?php 
  if ($Samevtr!="")
  {
   echo "<br>If you use this, be sure to add the recipients to ";
   echo "&quot;".$Samevtr."&quot;.";
  }
 } // not usekeys
 else
 {
  ?>
You chose to use keys to authenticate votes.<br>
The keys will be distributed for you after review by a moderator.<br>
If you have any special instructions, please
<a href="fbkf.php">contact us</a>.
<p>
<?php
 } // usekeys
 ?>
<p>
The web address for your poll is:<p>
<?=$AbsURL.$BalFile; ?>
<p>
<?php
 if ($Email!="no email provided")
 {
  echo "An email message was sent to you with instructions for editing your poll.";
  echo " Please check your spam folder if you have trouble finding it.<p>";
 }
?>
<p> 
 <a href=<?php echo $BalFile; ?>>Vote</a> |
 <a href=<?php echo $ResFile; ?>#Round1>View Results</a> |
 <a href=index.php>DemoChoice Main Page</a>
<?php 
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
<?php
  } // else Parsefail
 } //ValidPoll
} // submitted
if (!$Submitted || ($Submitted && !$ValidPoll))
{
 if (!$Submitted) { 
?>
<p style="margin-left: 5%; margin-right: 5%;">
Create your own poll!  With DemoChoice, you can conduct anything from a quick
survey to a full-featured election with security and multiple questions of
different types.<br><br>
DemoChoice polls can help make decisions among friends, 
family, or coworkers that are fair and conclusive.  They are a fun way to 
spread the word about ranked-choice voting methods.
</p>
<?php } ?>
<form action="<?=$ThisFile ?>" method="post">
<table border=0 cellspacing=20 cellpadding=5><tr valign=top><td>
<table border=0 cellspacing=0 cellpadding=5>
<tr><td colspan=2 bgcolor=f0d0e0><b>Basic setup</b></td></tr>
<tr><td bgcolor=f0d0e0>Y&#111;&#117;<!-- r -->r &#110;<!-- n -->&#97;m&#101;:</td>
<td bgcolor=f0d0e0> <input type="text" size=40 maxlength=40 name="creator"
<?php if ($Submitted) echo "value=".chr(34).$Creator.chr(34); ?>
></td></tr>
<tr><td bgcolor=f0d0e0>Y&#111;&#117;<!-- r -->r &#101;<!-- e -->&#109;a&#105;l&#32;:<br>
<font size=1>(optional)</font></td>
<td bgcolor=f0d0e0><input type="text" size=40 maxlength=60 name="ml"
<?php if ($Submitted) echo "value=".chr(34).$Email.chr(34); ?>
></td></tr>
<tr><td colspan=2 bgcolor=f0d0e0>
<font size=2>
If you enter an email address, you will receive instructions allowing you<br>
to edit your poll.  Your email address will be used only with regard to<br>
your poll. It's OK to leave this blank if you aren't using voter 
registration.<br></font> </td></tr>

<tr><td bgcolor=f0d0e0>Label:</td>
<td bgcolor=f0d0e0><input type="text" size=10 maxlength=10 name="label"
<?php if ($Submitted) echo "value=".chr(34).$Pollname.chr(34); ?>
></td></tr>
<tr><td colspan=2 bgcolor=f0d0e0>
<font size=2>The label is a short file identifier that must be unique.<br>
Only letters and numbers are allowed.</font>
</td></tr>
<tr><td bgcolor=f0d0e0>Title:</td>
<td bgcolor=f0d0e0><input type="text" size=40 maxlength=65 name="title"
<?php if ($Submitted) echo "value=".chr(34).$Title.chr(34); ?>
></td></tr>
<tr><td colspan=2 bgcolor=f0d0e0>Number of candidates to elect: <select name="seats">
<?php 
 for ($i=1; $i<=$MaxWin; $i++)
 {
  echo "<option";
  if ((!$Submitted && $i==1) || ($Submitted && $i==$Seats))
  { echo " selected"; }
  echo ">".$i;
 } 
?>
</select></td></tr>
<tr><td bgcolor=f0d0e0>Expires:</td>
<td bgcolor=f0d0e0><select name="expdy">
<?php
 for ($i=1; $i<=31; $i++)
 {
  echo "<option";
  if ((!$Submitted && $i==1) || ($Submitted && $i==$dy))
  { echo " selected"; }
  echo ">".$i;
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
<?php if ($mo==1) { echo "<option> "; echo $yr-1; } ?>
<option selected><?php echo $yr; ?>
<option><?php echo $yr+1; ?>
</select></td></tr>
<tr><td colspan=2 align=left bgcolor=f0d0e0>
<font size=2>
Votes are no longer counted at the start of the expiration day.<br>
Polls may be deleted a week or two after they expire.
<a target=_blank href="reginfo.php#expire">details</a></font>
</td></tr>
<tr><td colspan=2 align=left bgcolor=e0d0f0>
<b>Voter Registration</b><br><font size=2>
Enter the email addresses of those registered to vote.<br>
The limit of <?=$AllowedVoters; ?> voters can be increased upon request.<br>
If you want to allow anyone to vote, leave this empty.<br>
Addresses are used only to confirm votes.<br>
Be sure to <a target=_blank href=reginfo.php>read the instructions</a>!
</font><br>
<textarea name="maillist" rows=12 cols=30><?php
if ($Submitted)
{
 $ii=min($AllowedVoters,$NumLines);
 for ($i=0; $i<$ii; $i++)
 {  echo $List[$i].$cr; }
}
?></textarea><br>
<input type="checkbox" name="usekeys"
<?php if ($Submitted && $usekeys) echo "checked"; ?>
> Use registration keys
[<a target=_blank href=reginfo.php>read this first!</a>]
</td></tr>
<tr><td colspan=2 bgcolor=d0e0f0>Optional <b>fancy features</b> for sophisticated users</td></tr>

<tr>
<td colspan=2 bgcolor=d0e0f0>Preferred ballot type:</td>
</tr>
<tr>
<td bgcolor=d0e0f0>&nbsp;</td>
<td bgcolor=d0e0f0>
<input type=radio name="balfile" value=standard 
<?php if (!$Submitted || ($Submitted && $_POST["balfile"]=="standard")) echo "checked"; ?>
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
<input type=checkbox name=balrot 
<?php if (!$Submitted || ($Submitted && $_POST["balrot"]=="on")) echo "checked"; ?>
>
periodically shuffle candidate order
</td>
</tr>
<tr>
<td colspan=2 bgcolor=d0e0f0>Preferred results display:</td>
</tr>
<tr>
<td bgcolor=d0e0f0>&nbsp;</td>
<td bgcolor=d0e0f0>
<input type=radio name="resfile" value=bar 
<?php if (!$Submitted || ($Submitted && $_POST["resfile"]=="bar")) echo "checked"; ?>
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
<td bgcolor=e0d0f0>Next poll:<br></td>
<td bgcolor=e0d0f0><input type="text" size=10 maxlength=10 name="link"
<?php if ($Submitted) echo "value=".chr(34).$Link.chr(34); ?>
></td></tr>
<tr><td colspan=2 bgcolor=e0d0f0>
<font size=2>
Provide the label of a subsequent poll question here if you wish.
</font>
</td></tr>
<tr>
<td bgcolor=d0e0f0>Accept voters from: </td>
<td bgcolor=d0e0f0><input type="text" size=10 maxlength=90 name="samevoters"
<?php if ($Submitted) echo "value=".chr(34).$Samevtr.chr(34); ?>
></td></tr>
<tr><td colspan=2 bgcolor=d0e0f0>
<font size=2>
For multi-question polls with registered voters, register your<br>
voters in the first question, and list that poll in this box for subsequent ones.<br>
If you have both shared voters and voters exclusive to this question,<br>
include this question's label in the box.
See <a target=_blank href=reginfo.php>detailed instructions</a>.
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
Shuffle candidates on next poll's ballot
</td></tr>
</table></td><td>
<table border=0 cellspacing=0 cellpadding=5><tr>
<td bgcolor=e0f0d0>
<b>Candidates</b><br>
<font size=2>Enter up to <?=$AllowedCands ?> candidates.<br>They will 
appear in random 
order on the ballot,<br> except as specified in the Fancy Features section.<br>
Plain text only; edit your poll later to add links.
</font><p>
<?php 
 for ($i=0; $i<$AllowedCands; $i++)
 { echo "<input type=text size=40 maxlength=125 name=cand".($i+1);
   if ($Submitted && array_key_exists($i,$Name) && strlen($Name[$i])>0)
    echo " value=".chr(34).$Name[$i].chr(34);
   echo "><br>"; } 
?>
</td></tr>
<tr><td bgcolor=f0d0e0 colspan=2>
<br>Privacy:<br>
<font size=2>
<input type="checkbox" name="invis"
<?php if ($Submitted && $invis) echo "checked"; ?>
> Hide poll from directory<br>
<input type="checkbox" name="nores"
<?php if ($Submitted && $nores) echo "checked"; ?>
> Hide results until expiration<br>
<!-- (requires voter registration)<br> -->

</font>
</td></tr>
<tr><td bgcolor=e0f0d0>
<font size=2>
Please check your poll for mistakes.<p>
<input type="submit" name="submit" value="Create Poll">
or
<a href='<?=$HomeFile; ?>'>cancel</a> and return to the main page</p></font>
</td></tr>
</table>
</td></tr></table>
</form>
<p style="margin-left: 5%; margin-right: 5%;">
If you need 
more features than are allowed by this form, such as more candidates, 
candidate photos or links, or more than <?=$AllowedVoters ?> registered 
voters, send us a <a href="fbkf.php">request</a>.  If you can help translate
DemoChoice into other languages, please let us know.<br><br>

For fancy characters like &ouml; &iexcl; &copy;, use the appropriate
<a href=spchar.html>HTML entity code</a>.

<hr>
DemoChoice &copy;2001
<a 
href="http://www.laweekly.com/news/features/what-democracy-votes-like/3237/">Dave 
Robinson</a>
<?php } ?>
</body>
</html>
