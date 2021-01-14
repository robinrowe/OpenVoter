<?php
/*
DemoChoice: Ranked Choice Web Polls
Copyright (C) 2001 Dave Robinson
See DemoChoice-Readme.txt for license information
Context-sensitive help file
*/
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head><title>DemoChoice Web Poll Info</title></head>
<body bgcolor=white text=black style="margin-left: 5%; margin-right: 5%; font-family: arial, sans-serif; line-height: 140%;">

<H3>About the DemoChoice Web Poll</H3>
<dl>
<?php

if (array_key_exists("bt",$_GET))
{ $BType=$_GET["bt"]; }
else { $BType="0"; }

if ($BType!="0") { ?> 
<dt><b>How should I vote?</b>
<dd>Rank the candidates you support (first is best).
<?php
switch ($BType) {
case "ts":
?>
For each of them, click on the box next to the candidate, and then click
on your rank for that candidate.
<?php break;
case "tsn":
?>
For each of them, click on the box next to the candidate, and then click
on your rank for that candidate. You can also click on a name to add that
candidate to the end of your rankings.  In most browsers, you can also
press the &quot;+&quot; button to add a candidate to the end of your
rankings.
<?php break;
case "tsb":
?>
For each of them, click on the ranking box next to a candidate, and then
click on your rank for that candidate.  You can also press the button to
the left of the ranking box to add that candidate to the end of your
rankings.
<?php break;
case "op":
?>
For each of them, click on the grid point in the row of the candidate and 
the column of the ranking you want to assign. 
<?php break; } ?>
<p>
Note that:<br>
<ul>
<li>Your lower choices won't hurt the chances of your higher choices.
<li>If you don't rank a candidate, it means you'd rather not have your vote 
count than have it count toward that candidate.
<li>Only the order of your ranked candidates matters, and not the actual numbers.
<li>You can't give two or more candidates the same rank.
</ul><p>
<?php
} // Btype if

if (array_key_exists("nw",$_GET))
{ $NumWin=$_GET["nw"]; }
else { $NumWin="2"; }

if ($NumWin!="1") {
?>
<dt><b>What's the basic idea?</b>
<dd>
DemoChoice web polls are designed to produce satisfactory representation
for everyone, with majority rule.<p>

If your favorite candidate has too
few votes to win, your vote will be transferred to your next
favorite, if possible.<p>
If your favorite candidate has more than enough votes, some ballots
may be partially transferred so that all winners represent roughly
equal numbers of voters.
<p>

<dt><b>What is DemoChoice for?</b>
<dd>In a democracy worthy of the name, everyone's voice is heard (or 
represented with their explicit consent), and decisions require at least majority 
support: more 
people should support an idea than oppose it.<p>

If you elect your representatives by majority vote, and <i>they</i> make
decisions by majority vote, a small group can overrule the will of almost
75% of voters - and up to half of all voters don't even have
representatives who will express their protest.<p>

  Usually we
(Americans) elect people by "most votes wins" instead of majority, so it
can be even worse.  And worse than that, the people in power can group you
with others who will vote against your favorite - they can decide
<i>which</i> voters gain representation.  No wonder so many people have
lost faith and don't bother to vote: this approach miserably fails to meet
our goals.<p>

But it can be done! DemoChoice gives you the freedom to express your
preferences in detail among many viable choices, and then counts your
votes in a way that pursues the democratic goals noted above.  It can
usually accommodate almost everyone. As a result, voting actually becomes
a fun, positive, and rewarding experience! <p>

<dt><b>How does DemoChoice pursue its goals?</b>

<dd>DemoChoice attempts to assign everybody to their favorite
representative.  To make this work, a few adjustments need to be made.

<ul>
<li>Not all candidates can win.<br>

There are usually (and hopefully) more candidates than offices, so voters
for candidates with the fewest votes must choose their next favorite.<p>

<li>Representatives should have equal support.<br>

(This doesn't apply to single-winner elections.)<br>
Because representatives have equal voting power, they should each represent
an equal number of voters, in order to satisfy the first goal listed
above.  In pursuit of this, if a candidate receives more than enough votes
to get elected, the extra votes will be counted toward their next
favorite candidate instead.<p>
</ul>
<p>

<dt><b>How are the results tallied?</b>
<dd>It's easiest to understand this by just watching how the votes move on 
the results pages, but here are the detailed rules for the count.

</dl>
<ol>
<li>In each round, each ballot
not assigned to an elected candidate
is assigned to its highest-ranked continuing candidate.  (A
&quot;continuing&quot; candidate is one who has not yet been
elected or eliminated.)

<li>If no continuing candidates are ranked on a ballot, it is assigned to its 
highest-ranked elected candidate, if any, or otherwise counted as a vote for 
"none of these" continuing candidates.

<li>If any continuing candidates have a number of votes exceeding the
threshold (defined below), they are declared elected.

<li>If the number of continuing candidates does not exceed the number to be 
elected, all of them are declared elected.  To finish the process and arrange 
so that each winner represents the same number of voters, a final series of 
transfers is made (by the usual procedure below) from the candidate with the 
most votes to candidates with less than a seat's worth of votes.  This transfer 
is performed for each candidate with more that that number.

<li>If any candidates have a number of votes exceeding the threshold, the 
candidate(s) elected in the earliest round, and then the candidate with the 
most votes among those, is identified. Some ballots assigned to this candidate 
are split into a fraction (defined below) that stays assigned to that candidate 
and a fraction that will count toward continuing candidates in subsequent 
rounds.  Only the ballots most recently assigned to the candidate in the round 
in which s/he won, and only those that have a valid next choice, are subject to 
splitting.

<li>If no candidates exceeded the threshold in this round, the 
last-place candidate is eliminated.

<li>This process repeats until it is finished.
</ol>

If the election is an "instant runoff" for a single winner, rule 5
has no effect, and the process stops immediately if any candidate reaches
the threshold.
<p>
<dl>
<dt><b>Hey!  This is too complicated!</b>
<dd>
The rules behind DemoChoice appear complex, but only because they put
nearly all of the electoral controls within reach of the voter.  With
currently used methods, the outcome of most elections is determined
primarily by political consultants who use sophisticated computer
algorithms and large databases to manipulate district boundaries and
reduce competition.  Casting a DemoChoice vote is straightforward, but
with current methods, voters must fret over strategy to avoid wasting
their vote on a loser or on someone who would win anyway.  Don't give up!
<p>

<dt><b>How many votes does a candidate need to win?</b>
<dd>
A candidate is guaranteed victory if his or her number of votes exceeds
the minimum it could be without making it possible for too many candidates
to win (that would be a majority in the case of a single-winner election).  
If each elected candidate achieves this, then a majority of the
people elected represents a majority of the voting public.<p>

The formula for the threshold is:<p>
<table><tr valign=top><td nowrap>
<table><tr align=center><td rowspan=3 nowrap>threshold = </td>
<td nowrap>total votes</td></tr>
<tr align=center><td><hr></td></tr>
<tr align=center><td nowrap># winners + 1</td></tr></table>
</td><td width="20%">&nbsp;</td>
<td>
So if 100 votes were cast:<p>
<table border cellspacing=0 cellpadding=3>
<tr><th>Seats</th><th>Votes needed</th></tr>
<tr><td>1</td><td>more than 50</td></tr>
<tr><td>2</td><td>more than 33 <font size=2>1/3</font></td></tr>
<tr><td>3</td><td>more than 25</td></tr>
<tr><td>4</td><td>more than 20</td></tr>
</table>
</td></tr>
</table><p>
Votes for "none of these" are not included here.<p>


Note that it is possible for candidates to win without reaching the
threshold if they are the only ones left.  This is especially likely if a
large number of ballots end up with all of their choices eliminated.  In
instant runoff (single-winner) elections, the threshold is traditionally
recalculated in every round, excluding ballots with all choices eliminated
from the total number of votes.  This way, there are always two candidates
left in the final round.<p>

Once all winners are chosen, DemoChoice "raises the bar" and requires that each
winner have the same number of votes, equal to the number of votes divided by the number
of seats, again not including votes for "none of these".<p>

<dt><b>In a multi-winner election, how do you choose which votes stay 
assigned to a winner?</b>

<dd>There are several ways to do this, depending on the situation.  They 
can be randomly chosen, or chosen based on the distance between the 
winner's home precinct and the voters' precincts.<p>

DemoChoice assigns all ballots that counted for the winner in prior 
rounds.  Among ballots that were newly assigned in the winning round,
a fraction of each of ballot is redistributed to their next choices.  This 
fraction is equal to the fraction of those votes above the threshold:<p>

<table><tr align=center><td>total votes - threshold</td></tr>
<tr align=center><td><hr></td></tr>
<tr align=center><td>recently cast ballots</td></tr></table><p>

This ensures that all winners represent constituencies of similar size,
and that people don't avoid voting for popular candidates, thinking that
they will get elected anyway.  It follows the first-come-first-serve rule 
to reduce the number of ballots that are split into fractions.<p>

<dt><b>What happens if there is a tie?</b>

<dd>Ties are not a very significant issue in public elections, because the
number of ballots is large and ties are statistically rare.  However, in a
demonstration poll like this, they can happen frequently.  Here, they are
broken by comparing votes in successively previous rounds, or by random
lot if that fails.<p>

<dt><b>Is this the same as Instant Runoff Voting?</b>
<dd>Yes, if there is one winner.  This method works well for electing 
mayors, governors, or presidents.
The multi-winner version should be used for boards, councils, and
legislatures.  This gives more people representation than the 
usual method of dividing voters into districts and using 
single-winner elections in each.<p>

<dt><b>How well does it work?</b>

<dd>DemoChoice can routinely assign more than 90 percent of voters to
representatives they support.  This usually means that a decision by a
majority of representatives reflects the will of a majority of voters.  
Winners receive nearly equal shares of votes, so that each vote
corresponds to a nearly equal amount of legislative power.  Each
representative has the unanimous support of his/her voters.  Voters have a
large number of options because there is no appreciable 'spoiler' or
'vote-splitting' effect to scare away candidates.  

See for yourself by
looking at the results pages on the DemoChoice site!

<p>

<dt><b>Where did you get this newfangled idea?</b>
<dd>
This method of voting was first proposed in 1821, within a generation of
adoption of the US Constitution.  Similar methods were proposed
independently in the US, Britain, and Denmark, and were used in a few
public and private elections in that century.  John Stuart Mill, the most
well-known scholar on the theory of representative government, tried
unsuccessfully to enact it when he served in the House of Commons.
Australia and Ireland have used this method since the early 20th
century.<p>

About two dozen US cities including New York and Cincinnati elected their 
city councils this way in the first half of the 20th century.  It was very 
effective, but the principle of an equal voice for all was ahead of its 
time - women had only just been allowed to vote, and this was well before 
the civil rights movement - so it was repealed in almost all cases.  The 
only remaining case is Cambridge, MA.  In 2002, San Francisco adopted 
instant runoffs to determine a majority winner for mayor and other 
offices.

<p>
<?php
} // NumWin if
else {
?>
<dt><b>How are votes counted?</b>
<dd>
<OL style="margin-left: 15%; margin-right: 10%;">
<LI>Each ballot is counted toward its highest-ranked remaining candidate.
<LI>Does a candidate have a majority of counted votes?
<BR><i>No:</i> The last-place candidate is eliminated; go to step 1.
<BR><i>Yes:</i> The majority winner wins the election.
</OL>
<p>

<dt><b>Why vote this way?</b>
<dd>
This is an &quot;instant runoff&quot; poll, allowing voters to
conveniently find a strongly supported winner from among many candidates,  
with minimal worries about &quot;wasting&quot; votes on weak candidates or
&quot;splitting&quot; votes between similar candidates.<p>

It improves upon the "most votes wins" method, where:
<ul>
<li> if there are more 
than two candidates, someone can win even though most voters don't like 
that candidate
<li> voting for your favorite 
candidate often helps your least favorite candidate win
<li> If there are 
two similar candidates, even if one is much less popular than the 
other, they can "split" the vote so that neither can win.
</ul>  With instant 
runoffs, these problems practically never occur.<p>

It improves upon the two-round runoff election, where everyone has to go
in and vote twice - often, the inconvenience of the second election
results in very low participation.  Two-round runoffs can have the same 
problems as "most votes wins" when there are more than three 
candidates, but instant runoffs work well for any number of candidates.  
<p>

<dt><b>Which candidate received my vote?</b>
<dd>
Follow the <u>results</u> link for your poll and look at the final round.
At that point, your vote counted toward the remaining candidate who ranked
highest on your ballot, or toward &quot;none of these&quot;.<p>

<dt><b>What happens if there is a tie?</b>

<dd>Ties are not a very significant issue in public elections, because the
number of ballots is large and ties are statistically rare.  However, in a
demonstration poll like this, they can happen frequently.  Here, they are
broken by comparing votes in successively previous rounds, or by random
lot if that fails.<p>
<?php
} // NumWin else
?>

<dt><b>Let's do this in our local, state, and federal governments!</b>
<dd>If you are interested in promoting this method of voting, 
<a href="http://www.fairvote.org">FairVote</a> can 
provide more information and help you find like-minded people.
Also, browse the DemoChoice <a href="library.html">library</a>.
<p>

<dt><b>How can I print (or save) the results?</b>

<dd>To print the bar charts, you may need to change your browser settings to 
enable printing of background colors.  For example, in Microsoft Internet 
Explorer, choose "internet options" from the "tools" menu, go to the "advanced" 
tab, and check the "print background colors and images" box.  If the dotted 
threshold line doesn't print, add "&amp;thickdot=on" (without the quotes) to 
the page's web address.<p>

If the poll has a large number of candidates, the results may be broken into 
pages.  To disable this in order to save or print results, use "&amp;page=0" (no 
quotes) in the page's web address.<p>

<dt><b>Why didn't the totals change after I voted?</b>
<dd>They did - try pressing your browser's 'Refresh' button.<p>

<dt><b>I still don't get it!</b>
<dd>We want
to make sure that everyone who uses this site leaves with a comfortable
understanding of how it works.  Please feel free to 
<a href="fbkf.php">ask a question</a>.  Our <a href=library.html>library</a> has
many links to other explanations and discussions where you can learn more.
<p>

<dt><b>What do you do with my email address in a private poll?</b>
<dd>Your email address will be used to send a confirmation of your vote.
In the rare event that your vote is not properly recorded, you may be contacted.
Voter address information is not used for any other purpose.<p>

<dt><b>Send us your feedback!</b>
<dd>DemoChoice is an ongoing project, and user feedback is an essential 
part of it.  Everybody has a slightly different experience and it helps 
to hear what parts you found illuminating and what parts you found 
confusing or cumbersome.  Please <a href="fbkf.php">share your thoughts</a>! 
<p>
</dl>

<H3>Acknowledgements</h3>

<a href="http://www.initcomp.com">Steve Willett</a> created the first
web-based instant runoff poll in 2000, as an interface to
<a href="http://www.votingsolutions.com">ChoicePlus Pro</a>.  DemoChoice
evolved from this into its own project.  Steve and the Center for Voting and Democracy (now 
<a href="http://www.fairvote.org">FairVote</a>) helped 
provide web space for the first two years.  Many others have provided helpful
advice and encouragement.  Further comments would be appreciated.<p>

DemoChoice Web Polls &copy;2001
<a 
href="http://www.laweekly.com/news/what-democracy-votes-like-2135837">Dave 
Robinson</a>
</body>
</html>
