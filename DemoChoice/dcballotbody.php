<?php /*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Standard ballot form
*/ ?>
<form id="balform" action="<?php echo $AbsURL.$CastFile; ?>" method="post">
<table cellspacing=0 cellpadding=4 border=0
style="background-color: rgb(224,224,224);
 border-style: solid;
 border-width: 4px;
 border-color: rgb(32,80,116);">
<!-- google_ad_section_start -->
<?php
for ($i=0; $i<$TitleLines; $i++)
{ echo "<tr align=center><td>".$Title[$i]."</td></tr>"; }
?>
<!-- google_ad_section_end -->
<tr align=center><td>
(Rank the candidates you support!)</td></tr>
<tr align=center><td>
<?php
echo $Seats." candidate";
if ($Seats>1)
{echo "s";}
?> will be elected.</td></tr>
<!-- google_ad_section_start -->
<?php
for ($i0=0; $i0<$Cands2; $i0++)
{ ?>
<tr style="text-align: left; vertical-align: middle; font-size: smaller;">
<td nowrap>
<?php
if ($barebones=="false") {
?>
<img src="btna.gif" 
style="vertical-align: top;"
onmousedown="this.src='btnb.gif';"
onmouseup="this.src='btna.gif'; listsort(<?php echo $i0; ?>,-1);" 
onmouseout="this.src='btna.gif';"
onmouseover="vote_check();"
alt="[add/drop]" title="[add/drop]"
>
<?php } ?>
<select name="cand<?php echo $Balrot[$i0]; ?>" id="sel<?php echo $i0; ?>"
 onmouseover="newoldmyval(this.selectedIndex); vote_check;"
 onfocus="newoldmyval(this.selectedIndex); vote_check;"
 onChange="listsort(<?php echo $i0; ?>,this.selectedIndex);"
 style="vertical-align: baseline;">
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
<span id="label<?php echo $i0; ?>"><?php echo $Name[$Balrot[$i0]]; ?></span>
</td></tr>
<?php
} 
echo "<!-- google_ad_section_end -->";

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
</td></tr>
<?php
} // invite and not expired and no whotemp

?><tr align=center><td>
<input type="submit" name="submit" id="castvote" 
value="vote" onmouseover="vote_check();" onfocus="vote_check();">
<font size=2>
| <a href="<?php echo $InfoFile; ?>"
 target="_blank">how it works</a>
| <a href="<?php echo $ResultFile."#Round1"; ?>">view results</a> |
</font>
<input type="reset" name="reset" value="clear" id="clear">
</td></tr>
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
<tr align=center><td>
<font size=2><a href="<?=$CastLink; ?>">skip this question</a></font>
</td></tr>
<?php
} // include skip link

if ($barebones=="false") {
?>
<tr align=center><td><font size=2>

Try the <a href="<?php echo $bbfile; ?>">less fancy ballot</a> if this one 
fails.
</font></td></tr>
<?php } ?>
<tr align=center><td><font size=2>
Create your own <a href="http://www.demochoice.org">DemoChoice</a> poll!
</font></td></tr>
</table>
</form>

