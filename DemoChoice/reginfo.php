<?php /*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Voter registration help file
*/
$AllowedVoters=800;
$now=getdate();
 ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>DemoChoice Voter Registration</title>
</head>
<body style="font-family: arial;">
<h3>About Voter 
Registration</h3>
<dl style="margin-right: 5%;">
<dt><b>What is it?</b>

<dd>Voter registration allows you to restrict polls to authorized voters,
and ensure that they only vote once.<p>

<dt><b>How do I use it?</b>

<dd>Enter up to <?=$AllowedVoters ?> email addresses in the box on the form
(more can be accommodated upon request).  
Each email must be on a separate line, in the form name@domain.org. There can be 
a comma afterward.  The email addresses will only be used for 
confirmation of each vote.  For email-based authentication, you will need to announce the 
poll on your own, but 
DemoChoice will generate a form letter that you can send.  Key-based authentication is more 
secure, and we will send out announcements for you, but you will not have as much control over the 
timing of the announcement.<p>

<dt><b>What if my poll has multiple questions?</b>

<dd>
You should only enter your email list for the first question in a sequence.  For subsequent questions, put
the label of the first question in the &quot;accept voters from&quot; box on the setup form.
This way, you have fewer voter lists to maintain, and your voters will only have
to enter their email address or registration key once.
<br><br>
A separate necessary task is to connect the questions by indicating the label of the next poll 
question in the series in the box for that purpose on the form.
<br><br>
If you have subgroups for some questions, such as an election of district representatives and 
then of an executive office for which everyone votes: enter the executive poll label in the 
&quot;next poll&quot; box in each district, and enter a comma-separated list of the labels for 
each district in the &quot;accept voters from&quot; box for the executive poll.
<br><br>
You can include voters from a previous question while also adding voters for the new 
question.  Put the new voters in the list box, and then put the labels of both that 
poll and the previous question in the &quot;accept voters from&quot; box.
<br><br>
 You may want to
<a href=fbkf.php>ask</a> before trying anything really complicated with a large voter list.
<p>

<dt><b>How does email authentication work?</b>

<dd>Voters are required to enter their email addresses in order to cast a
vote.  If a voter is authorized and has not voted yet, the vote will be
counted, and an email confirmation is sent.  You should make
sure that each email address corresponds to a unique, real voter.<p>

<dt><b>How does this prevent fraud?</b>

<dd>It prevents multiple votes from the same person, and detects and
traces imposters so that damage can be corrected.  This will be sufficient
if there is sufficient trust within your group.  If you have many voters who
you don't know well, you may want to consider key-based authentication.<p>

<dt><b>How does key-based authentication work?</b>

<dd>Each voter gets a unique alphanumeric key, essentially eliminating the 
possibility that someone could vote on someone else's behalf.  DemoChoice can 
send email announcements to your voters including these keys, but for security 
each poll requires review by a human before we allow large numbers of email 
messages to be sent by our server.  Be sure to include your full name on the 
setup form because this will be used in the signature of your announcements.
Please <a href=fbkf.php>contact</a> us promptly after creation of your poll 
if you have special instructions regarding when these announcements should be 
sent.<p>
You should also separately announce the poll to your voters, asking them to look
for a message containing a link to a ballot.  If voters complain that they have not
received a link message, but others have, ask them to check their spam folders.<p>

<dt><b>What will you do with my voters' email addresses?</b>
<dd>Voter addresses are used only to confirm votes.
Your address information will be used only with regard to your poll.<p>

<dt><b>What if I have a mix of paper ballots and online votes?</b>
<dd>
<ul>
<li> Without voter registration: By default, your poll features a delay to block 
votes from the same computer. If you've entered an email address with your poll, 
this feature can be disabled by setting the delay time to zero.  After this, you 
can enter as many votes as you want.  It's wise to reimpose the delay once you 
are done.
<li> With voter registration: Just enter your poll's password on the ballot as 
the key or email address and you can cast multiple votes.  In this case it's 
convenient to use your browser's &quot;back&quot; button so you don't have to 
retype the email address (assuming you have a cooperative browser).<p>
</ul>
</dl>
<a name="expire">&nbsp;</a>
<dl style="margin-right: 5%;">
<dt><b>What happens when my poll expires?</b>
<dd>No new votes will be accepted as of the beginning of the day of
expiration, according to the server's clock, which currently reads <?php echo
$now["hours"].":".$now["minutes"]; ?>.  After that, anyone may cast a vote
to see who it counts for, but the vote will not be added to the tally.  
The poll will be subject to deletion two weeks after the expiration
date.<p>
</dl>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
<br><br>
-
</body>
</html>
