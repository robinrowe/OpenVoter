<?php /*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Standard ballot that sorts rankings and avoids duplicates and gaps.
*/ ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>DemoChoice Ballot</title>
<link rel="shortcut icon" href="favicon.ico" type="image/vnd.microsoft.icon">
<?php require("dcballothead.php"); ?>
</head>
<body bgcolor=white text=black style="font-family: arial;">

<?php 
 if ($Verbose)
 {
 ?>
<center>
<font size=5 face="Helvetica">DemoChoice Web Poll</font>
<p>
<?php
  if ($Expired)
  {
   echo "The deadline for this poll has passed.  You may cast a ballot anyway to see who it would count for.<p>";
  }
  if ($Invite)
  {
   if (!$Expired)
   {
    echo "<b>Note:</b> You may only vote in this poll if you were invited.<br>";
    echo "Please cast your vote before ".date("F d, Y",$ExpireTime).".<p>";
   }
  }
 }
require("dcballotbody.php");
if ($Verbose) { ?>
</center>
<p style="margin-left: 10%; margin-right: 10%; text-align: left;">
<?php if ($Seats>1) { ?>
DemoChoice web polls are designed to produce satisfactory representation 
for everyone, with majority rule.  If your favorite candidate has too 
few votes to win, your vote will be transferred to your next 
favorite, if possible.
If your favorite candidate has more than enough votes, some ballots 
may be partially transferred so that all winners represent 
equal numbers of voters.
<?php } else { ?>
This is an &quot;instant runoff&quot; poll, allowing voters to 
conveniently find a strongly supported winner from among many candidates, 
with minimal worries about &quot;wasting&quot; votes on weak candidates or 
&quot;splitting&quot; votes between similar candidates.
Here's
<a href="<?php echo $InfoFile; ?>">how it works</a>:
<OL style="margin-left: 15%; margin-right: 10%; text-align: left;">
<LI>Each ballot is counted toward its highest-ranked remaining candidate.
<LI>Does a candidate have a majority of counted votes?
<BR><i>No:</i> The last-place candidate is eliminated; go to step 1.
<BR><i>Yes:</i> The majority winner wins the election.
</OL>
<?php } ?>
<p style="margin-left: 10%; margin-right: 10%; text-align: left;">
Tips:
<ul style="margin-left: 15%; margin-right: 10%; text-align: left;">
<li>Your lower choices won't hurt the chances of your higher choices.
<li>Please don't give two or more candidates the same rank.
<li>Only the order of your ranked candidates matters, and not the actual numbers.
<li>If you don't rank a candidate, it means you'd rather not have your vote 
count than have it count toward that candidate.
</ul>

<?php

 } /* verbose */ ?>
</body>
</html>
