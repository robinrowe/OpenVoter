<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>3-choice IRV Ballot</title>
<?php 
//DemoChoice: A preference voting package for the web (C) 2001 Dave Robinson
//Ballot header include
//Produces form field array of Cand0...Cand(#candidates-1)
//w/ values = a rank from 0 to #candidates
//0 = first place, #candidates=not ranked

$choicedepth=3;
$cname[0]="First";
$cname[1]="Second";
$cname[2]="Third";
$color[0]="rgb(128,128,256)";
$color[1]="rgb(192,128,192)";
$color[2]="rgb(128,192,192)";

$hrs=getdate(time());
mt_srand($hrs["hours"]);
$ThisFile="sfballot.php";
require("dcconfigload.php");
$InfoFile="sfinfo.php";

//Internet Explorer can handle dynamic style sheets, allowing
//text to be moved around.  Other browsers can't.
//(Not tested on ancient Explorer versions)

$UsrAgt=$_SERVER['HTTP_USER_AGENT'];
if ($nodivs) { $UsrAgt="nodivs"; }

if (!(($dodivs=strpos($UsrAgt,"MSIE"))===false))
{
 $UsrAgt=substr($UsrAgt,$dodivs+5);
 $version=(float)trim(substr($UsrAgt,0,(strpos($UsrAgt,";") ? strpos($UsrAgt,";") : 0)));
 $dodivs=true;
 if ($version<5) { $dodivs=false; } 
}
else if (!(($dodivs=strpos($UsrAgt,"Mozilla"))===false))
{
 $UsrAgt=substr($UsrAgt,$dodivs+8);
 $version=(float)trim(substr($UsrAgt,0,(strpos($UsrAgt,";") ? strpos($UsrAgt,";") : 0)));
 $dodivs=true;
 if ($version<5) { $dodivs=false; } 
}
if (array_key_exists("terse",$_GET))
{ $Verbose=false; }
else
{ $Verbose=true; } 

if (array_key_exists("norot",$_GET))
{ $norot=true; }
else
{ $norot=false; }

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
<?php if ($dodivs && $Invite) { ?>
// This disables the automatic submit feature (bug?) when enter is hit in a text box in internet explorer
function testForEnter() 
{    
 if (event.keyCode == 13) 
 {        
  event.cancelBubble = true;
  event.returnValue = false;
 }
} 
<?php } ?>

//--></SCRIPT>
</head>
<body style="font-family: Arial; background-color: white; color: black">
<center>
<table border=0 cellspacing=0 cellpadding=4>
<tr>
<td align=center>
<font size=4 face="Arial"><B>RANKED-CHOICE POLL</B></font><br>
</td>
</tr>

<tr><td style="padding-left: 15%; padding-right: 15%;">
<font size=2 face="Arial"><b>INSTRUCTIONS TO VOTERS:</b>
<!--
Mark your first choice in the first column by clicking the button in the 
arrow pointing to your choice.  To indicate a second choice, select a 
different candidate in the second column.  To indicate a third choice, 
select a different candidate in the third column.
-->
Mark your first choice in the first column by clicking the button in
the arrow pointing to your choice. Mark a second choice by selecting a
<b>different</b> candidate in the second column. Mark a third choice by
selecting a different candidate in the third column. Your vote is 
most likely to affect who will win if you mark all three choices.
</font>
</td></tr>
</table><p>
<?php
  if ($Expired)
  {
   echo "The deadline for this poll has passed.  You may cast a ballot anyway to see who it would count for.<p>";
  }
  else if ($Invite)
  {
    echo "<b>Note:</b> You may only vote in this poll if you were invited.<br>";
    echo "Your email address will be used only to confirm your vote.<p>";
  }
?>

<form action="dcconfirm.php?poll=<?=$Pollname ?>" method="post">
<input type="hidden" name="baltype" value="choice">
<table border=1 cellspacing=0 cellpadding=2>
<tr>
<?php 
for ($cnum=0; $cnum<$choicedepth; $cnum++)
{
 echo "<td><center>";
 for ($i=0; $i<$TitleLines; $i++)
 { echo "<font size=-1>".$Title[$i]."</font>"; }
?>
</center><hr>
<center><table cellspacing=5 cellpadding=2><tr>
<td rowspan=2 valign=middle style="color: <?=$color[$cnum] ?>">
<font size=6><?=($cnum+1) ?>&nbsp;&nbsp;</font></td>
<td><b><?=$cname[$cnum] ?> Choice</b></td></tr>
<tr><td><font size=-1>Vote for One</font></tr></table></center>
<table cellspacing=1 cellpadding=1>
<?php
 for ($i0=0; $i0<($Cands-$ExclCt); $i0++)
 { 
?>
<tr><td colspan=2><hr></td></tr>
<tr align=right>
<td align=left width="65%"><font size=2>
<?php echo $Name[$Balrot[$i0]]; ?>
&nbsp;&nbsp;</font></td>
<td align=right width="35%" nowrap>
<img src="img/arrowhead.gif" alt="<-">
<input type="radio" name="choice<?php echo $cnum+1; ?>"
 value="<?php echo $Balrot[$i0]; ?>">
<img src="img/arrowtail.gif" alt="-[">
&nbsp;</td>
</tr>
<?php 
 } 
?>
<tr><td colspan=2><hr></td></tr>
<tr align=right>
<td align=left width="65%"><font size=2>
None
</font></td>
<td align=right width="35%" nowrap>
<img src="img/arrowhead.gif" alt="<-">
<input type="radio" name="choice<?php echo $cnum+1; ?>"
 value="<?php echo $Cands; ?>" checked>
<img src="img/arrowtail.gif" alt="-[">
&nbsp;</td>
</tr>
</table>
</td>
<?php } ?>
</tr>
</table>
<table border=0 cellspacing=2 cellpadding=4>
<?php
if ($Invite && !$Expired)
{
?><tr align=center><td>
Email: <input type="text" name="email" size=20 maxlength=40
<?php if ($dodivs) { echo "onkeydown=testForEnter();"; } ?>>
</td></tr><?php
}
?><tr align=center><td>
<input type="submit" name="submit" value="vote">
<font size=2>
| <a href="<?=$InfoFile ?>" target="_blank">
how it works</a>
| <a href="<?=$ResultFile ?>">view results</a> |
</font>
<input type="reset" name="reset" value="clear">
</td></tr>
<tr align=center><td>
<font size=2>Create your own <a href="http://www.demochoice.org">DemoChoice</a> 
poll!
</font>
</td></tr></table>
</form>
</center>
</body>
</html>
