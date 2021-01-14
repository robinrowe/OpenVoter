<?php /*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
GPL version of index page
*/ ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>DemoChoice Polls</title>
<?php
 $HiliteArray = array (
 "Ice", "DC"
 );
 $Pollname="Ice";
 require("dcballothead.php");
?>
</head>
<body bgcolor=white text=black>
<p style="margin-left: 5%; margin-right: 5%;"> 
<table border=0 cellspacing=4 cellpadding=0>
<tr><td valign=top>
<font face="arial" size="+2">
Ranked Choice Web Polls</font>
<br>
<font face="arial">
<b>Featuring <a href=http://www.demochoice.org>DemoChoice</a> software</b>
</font><br>
<hr>
Control panel:
<ul>
<li> <a href=dcsetup.php>create</a> a new poll
<li> <a href=dcedit.php>edit</a> an existing poll<br>
(you will need its password)
<li> <a href=dcdir.php>Directory</a> of polls
<li> <a href="DemoChoice-Readme.txt">Manual</a>
</ul><br>
<hr>
<table border=0 cellspacing=0 cellpadding=3>
<tr><td>
<font size=2 face="helvetica">Featured Polls
</font></td>
<td align=center><font size=2 face="helvetica">Vote</font></td>
<td align=center colspan=2><font size=2 face="helvetica">Results</font></td>
</tr>
<?php require("dchilite.php"); ?>
</table>
</td><td><img src="shim.gif" width=15 alt=" "></td><td align=center>
<?php require("dcballotbody.php") ?>
</td></tr></table>

<p style="margin-left: 5%; margin-right: 5%;"> 
DemoChoice &copy;2001
<a href="http://www.laweekly.com/news/what-democracy-votes-like-2135837">
Dave Robinson</a>
</p>
</body>
</html>
