package DataCheck;

#Copyright (C) 2004 Susan J. Dridi sdridi@greens.org
#
#This program is free software; you can redistribute it and/or
#modify it under the terms of the GNU General Public License
#as published by the Free Software Foundation; either version 2
#of the License, or (at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

use strict;
use warnings;
use lib qw(LIB);
use DBConnect;

#Check data submitted in forms for illegal characters and values.
#Test for all supporting tables that populate pop up menus and radio groups.
#Test to match correct patterns and max length

sub quotes {
my ($text) = @_;
$text =~s/["']*//g;
return $text;
}

sub alpha {
my ($text, $max) = @_;
$text =~s/[^a-zA-Z]//g;
if ($max) {
	#cut the string down to the max length
	my $count = length($text);
	if ($count <= $max) 
		{return "";}
	else
		{return $text;}
	}
else
	{return $text;}
}

sub num {
my ($num) = @_;
$num =~s/[^0-9]//g;
return $num;
}

sub alphanum {
my ($text) = @_;
$text =~s/[^a-zA-Z0-9]//g;
return $text;
}

sub alphanumext {
my ($text) = @_;
$text =~s/[^a-zA-Z0-9 ,':-]//g;
return $text;
}

sub filename {
my ($text) = @_;
$text =~s/([^\w-])/_/g;
$text =~s/^[-.]+//;
$text = lc($text);
return $text;
}

sub name {
my ($text) = @_;
$text =~ s/[^a-zA-Z\. '-]//g;
$text =~ s/\s{2,}/ /g;
$text =~ s/-{2,}/-/g;
$text =~ s/'{2,}/'/g;
$text =~ s/\.{2,}/\./g;
return $text;
}

sub pw {
my ($text) = @_;
$text =~s/[^a-zA-Z0-9:.'-]//g;
return $text;
}

sub states {
my ($id) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select abbr from states where abbr = ?}; 
my $stateabbr = $dbh->selectrow_array($sql, undef, $id);
$dbh->disconnect;
return $stateabbr;
}

sub zip1 {
my ($text) = @_;
$text =~s/\D//g;
$text =~s/^(\d{5}?)(\d{4}?)(.*)/$1-$2/;
return $text;
}

sub zip {
my ($text) = @_;
my ($z1, $z2) = split (/-/,$text);
my $zip;
$z1 =~s/^(\d{5}?)(.*)/$1/;
if ($z2) {
	$z2 =~s/^(\d{4}?)(.*)/$1/;
	$zip = "$z1" . "-" . "$z2";
	}
else
	{
	$zip = $z1;
	}
return $zip;
}

sub text {
#need regular expression to test for HTML and Javascript
my ($text, $max) = @_;
if ($max)
	{
	my $count = length($text);
	if (($text =~ /\w/) and ($count <= $max))
		{return 1;}
	else	
		{return 0;}
	}
else
	{
	if ($text =~ /\w/)
		{return 1;}
	else	
		{return 0;}
	}
}

sub link {
my ($text, $max) = @_;
my $count = length($text);
if (($text =~ /^(?:http:\/\/|https:\/\/){1}.*\.(?:com|edu|org|net|gov|mil|us|ws|info|ca|uk){1}.*\z/) and ($count <= $max))
	{return 1;}
else	
	{return 0;}
}

sub striphtml {
use HTML::Parser;
my ($input) = @_;
my $text;
my $html = HTML::Parser->new(
	api_version => 3,
	text_h	=> [sub{ $text .= shift;}, 'dtext'],
	start_h	=> [sub{ shift;}, 'text'],
	end_h	=> [sub{ shift;}, 'text']);
$html->ignore_elements(qw(script comment));
$html->parse("$input");
$html->eof;
return $text;
}

sub html {
my ($text) = @_;
#put tags around urls, get rid of extra newlines and convert newlines to <br> tags
$text =~ s/(http)([^\s]*)/<a href=$1$2>$1$2<\/a>/g;
1 while $text =~ s/\r//mg;
$text =~ s/^ /&nbsp;/mg;
1 while $text =~ s/(\n\n\n)/\n\n/mg;
$text =~ s/\A\s*//;
$text =~ s/\n/<br>/g;
$text =~ s/<br>/<br>\n/g;
return $text;
}

sub email {
my ($text, $max) = @_;
my $email = lc($text);
my $count = length($email);
if (($email =~ /[^\\\(\)\|\[ "'<>;,\/!#%&=`~@\]]{2,}@[^\\\(\)\|\[ "'<>;,\/!#%&=`~@\]]{2,}\.(?:com|edu|org|net|gov|mil|us|ws|ca|fm){1}\z/) and ($count <= $max))
	{return 1;}
else	
	{return 0;}
}

sub phone {
my ($text, $max) = @_;
my $count = length($text);
if (($text =~ /^\d{3}-\d{3}-\d{4}x{0,1}\d{0,9}/) and ($count <= $max))
	{return 1;}
else	
	{return 0;}
}

sub date {
my ($input) = @_;
my ($year, $month, $day) = split(/-/,$input);
my (%yl, %ml, %dl);
my ($yearlist, $monthlist, $daylist);
my @yearlist = (1980 .. 2050);
my @monthlist = qw(01 02 03 04 05 06 07 08 09 10 11 12);
my @daylist = qw(01 02 03 04 05 06 07 08 09);

my $leapyear = 
	$year % 4 ? 0 :
	$year % 100 ? 1 :
	$year % 400 ? 0 : 1;
	
if ($month eq "02")
	{
	if ($leapyear == 1)
		{push (@daylist, (10 .. 29));}
	else
		{push (@daylist, (10 .. 28));}
	}
elsif ($month eq "04" or $month eq "06" or $month eq "09" or $month eq "11")
	{push (@daylist, (10 .. 30));}
else
	{push (@daylist, (10 .. 31));}

foreach $yearlist(@yearlist)
	{$yl{$yearlist} = 1;}
foreach $monthlist(@monthlist)
	{$ml{$monthlist} = 1;}
foreach $daylist(@daylist)
	{$dl{$daylist} = 1;}
	
if (exists $yl{$year} and exists $ml{$month} and exists $dl{$day})
	{return 1;}
else
	{return 0;}
}

# I need to redo these to pass <=> and the difference to calculate (eg 2 days earlier, 1 day later, etc.)

sub compdates {
my ($earlier, $later) = @_;
$earlier =~ s/-//g;
$later =~ s/-//g;
if ($later > $earlier)
	{return 1;}
else
	{return 0;}
}

sub eqdates {
my ($date1, $date2) = @_;
$date1 =~ s/-//g;
$date2 =~ s/-//g;
if ($date1 == $date2)
	{return 1;}
else
	{return 0;}
}

sub votesleft {
my ($pid, $did) = @_;
my $dbh = DBConnect::connectr();
my $sql1 = qq{select count(vote) from votes where pid = ? and distid = ?};
my $voted = $dbh->selectrow_array($sql1, undef, $pid, $did);
my $sql2 = qq{select voters from districts where distid = ?};
my $votesallowed = $dbh->selectrow_array($sql2, undef, $did);
$dbh->disconnect;
if ($voted < $votesallowed)
	{return 1;}
else
	{return 0;}
}

sub candname {
my ($id, $text) = @_;
$text = lc $text;
my $dbh = DBConnect::connectr();
my $sql = qq {select candid from cands where lower(cand) = ? and pid = ?};
my $candid = $dbh->selectrow_array($sql, undef, $text, $id);
$dbh->disconnect;
return $candid;
}

sub ballotcand {
my ($id, $text) = @_;
$text = lc $text;
my $dbh = DBConnect::connectr();
my $sql = qq {select candid from cands where lower(cand) = ? and pid = ? and candtype = 1};
my $candid = $dbh->selectrow_array($sql, undef, $text, $id);
$dbh->disconnect;
return $candid;
}

sub distsroles {
my ($did, $rid, $guid) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select gid from greens_roles where distid = ? and roleid = ? and gid = ?};
my $gid = $dbh->selectrow_array($sql, undef, $did, $rid, $guid);
$dbh->disconnect;
if ($gid)
	{return 1;}
else
	{return 0;}
}

sub vote {
my ($vote) = @_;
if (($vote eq "Yes") or ($vote eq "No") or ($vote = "Abstain"))
	{return 1;}
else
	{return 0;}
}

sub voted {
my ($gid, $pid) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq {select vote from votes where pid = ? and gid = ?};
my $vote = $dbh->selectrow_array($sql, undef, $pid, $gid);
if ($vote)
	{return 1;}
else
	{return 0;}
}

sub phase {
my ($id) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select phaseid from phases where phaseid = ?};
my $phaseid = $dbh->selectrow_array($sql, undef, $id);
$dbh->disconnect;
if ($phaseid)
	{return 1;}
else
	{return 0;}
}

sub presens {
my $valid = "0.6666";
my ($val) = @_;
if ($valid =~ /$val/)
	{return 1;}
else
	{return 0;}
}

sub consens {
my $valid = "0.6666 0.5";
my ($val) = @_;
if ($valid =~ /$val/)
	{return 1;}
else
	{return 0;}
}

sub propstat {
my ($id, $valphase, $operator) = @_;
my $sql;
if ($operator eq "lt")
	{$sql = qq {select pid from proposals where pid = ? and phaseid < ?};}
elsif ($operator eq "eq")
	{$sql = qq {select pid from proposals where pid = ? and phaseid = ?};}
elsif ($operator eq "gt")
	{$sql = qq {select pid from proposals where pid = ? and phaseid > ?};}
else
	{return 0;}
my $dbh = DBConnect::connectr();
my $pid = $dbh->selectrow_array($sql, undef, $id, $valphase);
$dbh->disconnect;
if ($pid)
	{return 1;}
else
	{return 0;}
}

sub irvstat {
my ($id, $valphase, $operator) = @_;
my $dbh = DBConnect::connectr();
my $sql;
if ($operator eq "lt")
	{$sql = qq {select pid from irv where pid = ? and phaseid < ?};}
elsif ($operator eq "eq")
	{$sql = qq {select pid from irv where pid = ? and phaseid = ?};}
elsif ($operator eq "gt")
	{$sql = qq {select pid from irv where pid = ? and phaseid > ?};}
else
	{
	$dbh->disconnect;
	return 0;
	}
my $pid = $dbh->selectrow_array($sql, undef, $id, $valphase);
$dbh->disconnect;
if ($pid)
	{return 1;}
else
	{return 0;}
}

sub disttype {
my ($id) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select disttypeid from disttypes where disttypeid = ?};
my $disttypeid = $dbh->selectrow_array($sql, undef, $id);
$dbh->disconnect;
if ($disttypeid)
	{return 1;}
else
	{return 0;}
}

sub distname {
my ($dist) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select district from districts where district = ?};
my $district = $dbh->selectrow_array($sql, undef, $dist);
$dbh->disconnect;
if ($district)
	{return 1;}
else
	{return 0;}
}

sub distid {
my ($id) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select distid from districts where distid = ?};
my $distid = $dbh->selectrow_array($sql, undef, $id);
$dbh->disconnect;
if ($distid)
	{return 1;}
else
	{return 0;}
}

sub commid {
my ($id) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select commid from committees where commid = ?};
my $commid = $dbh->selectrow_array($sql, undef, $id);
$dbh->disconnect;
if ($commid)
	{return 1;}
else
	{return 0;}
}

sub commname {
my ($comm) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select comm from committees where comm = ?};
my $committee = $dbh->selectrow_array($sql, undef, $comm);
$dbh->disconnect;
if ($committee)
	{return 1;}
else
	{return 0;}
}

sub commem {
my ($cid, $gid, $did, $rid) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select gid from greens_roles where gid = ? and commid = ? and roleid = ? and distid = ?};
my $id = $dbh->selectrow_array($sql, undef, $gid, $cid, $rid, $did);
$dbh->disconnect;
if ($id)
	{return 1;}
else
	{return 0;}
}

sub role {
my ($id) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select roleid from roles where roleid = ?};
my $roleid = $dbh->selectrow_array($sql, undef, $id);
$dbh->disconnect;
if ($roleid)
	{return 1;}
else
	{return 0;}
}

sub valrole {
my ($gid, $rid, $cid) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select roleid from greens_roles where gid = ? and roleid = ? and commid = ?};
my $roleid = $dbh->selectrow_array($sql, undef, $gid, $rid, $cid);
$dbh->disconnect;
if ($roleid)
	{return 1;}
else
	{return 0;}
}

sub loginname {
my ($id) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select signon from signons where signon = ?};
my $son = $dbh->selectrow_array($sql, undef, $id);
$dbh->disconnect;
if ($son)
	{return 1;}
else
	{return 0;}
}

sub userid {
my ($id) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select gid from signons where gid = ?};
my $gid = $dbh->selectrow_array($sql, undef, $id);
$dbh->disconnect;
if ($gid)
	{return 1;}
else
	{return 0;}
}

sub gid {
my ($id) = @_;
my $dbh = DBConnect::connectr();
my $sql = qq{select gid from greens where gid = ?};
my $gid = $dbh->selectrow_array($sql, undef, $id);
$dbh->disconnect;
if ($gid)
	{return 1;}
else
	{return 0;}
}

sub maxdels {
my ($id) = @_;
my $dbh = DBConnect::connectr();
my $sql1 = qq{select voters from districts where distid = ?};
my $allowed = $dbh->selectrow_array($sql1, undef, $id);
my $sql2 = qq{select count(gid) from greens_roles where distid = ? and roleid = 1 and actstatus = 1};
my $current = $dbh->selectrow_array($sql2, undef, $id);
$dbh->disconnect;
if ($allowed > $current)
	{return 1;}
else
	{return 0;}
}

sub delexists {
my ($gid, $cid, $rid, $did) = @_;
my $dbh = DBConnect::connectr();
my $sql1 = qq {select gid from greens_roles where gid = ? and commid = ? and roleid = ? and distid = ?};
my $delid = $dbh->selectrow_array($sql1, undef, $gid, $cid, $rid, $did);
$dbh->disconnect;
if ($delid)
	{return 1;}
else
	{return 0;}
}

1;	#return true
