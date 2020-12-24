package HeadFoot;

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
use Conf;

#Standard headers and footers.

sub head {
my ($guid, $page) = @_;
my $login;
if ($page eq "login")
	{($login = "");}
elsif ($guid)
	{$login = "<h3><a href=\"logout\">Logout</a></h3>";}
else
	{$login = "<h3><a href=\"login\">Login</a></h3>";}

my $org = Conf::org();

my $group = Conf::group();

my $logo = Conf::logo();

my %titles = (
	admin => "Administration",
	admindel => "Delegates Administration",
	admindistricts => "Delegation Administration",
	certifyirv => "Certify Ranked Choice Vote",
	delegates => "Delegates",
	irvdetail => "Ranked Choice Vote Details",
	propresult => "Proposal Result",
	viewballot => "View Ballot",
	votinghistory => "Voting History",
	admincom => "Committee Administration",
	admindelegates => "Delegate Administration",
	adminirv => "Ranked Choice Vote Administration",
	contacts => "Contacts",
	irvresult => "Ranked Choice Vote Results",
	vote => "Vote",
	admincommittees => "Committee Administration",
	admindist => "Delegation Administration",
	adminprop => "Proposal Administration",
	index => "Home",
	login => "Login",
	propdetail => "Proposal Details",
	voteresults => "Results of Past Votes");

print "Content-type: text/html\n\n";
print<<EOF;

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head>
<meta name="MSSmartTagsPreventParsing" content="TRUE">
<title>$org - $group Voting - $titles{$page}</title>
<style>
<!--
body {font-family:  Verdana, Geneva, Arial, Helvetica, sans-serif; font-size : 13px;}
th {font-family:  Verdana, Geneva, Arial, Helvetica, sans-serif; font-size : 13px; color: white; background: #004400; text-align: left; vertical-align: top;}
td {font-family:  Verdana, Geneva, Arial, Helvetica, serif; font-size : 13px; vertical-align: top;}
a:link {color: #003300; text-decoration: none; font-weight: bold;}
a:visited {color: #003300; text-decoration: none;}
a:hover {color: Green; text-decoration: none; font-weight: bold;}
a:active {color: Green; text-decoration: none; font-weight: bold;}
li {list-style-type : square;}
b,strong { font-weight : bold; }
h2 { font-size: 20px; font-weight : bold;}
h3 { font-size: 16px; font-weight : bold;}
h4 { font-size: 14px; font-weight : bold;}
-->
</style>
</head>

<body>

<image src="$logo" border=0 alt="$org">

<table width=750 border=0 cellpadding=0 cellspacing=0 cols=6>
<tr>
<td bgcolor="#004400" width="16.66%" align="center"><a href="index"><font color="white"><b>Home</b></font></a></th>
<td bgcolor="#004400" width="16.66%" align="center"><a href="vote"><font color="white"><b>Vote</b></font></a></th>
<td bgcolor="#004400" width="16.66%" align="center"><a href="voteresults"><font color="white"><b>Results</b></font></a></td>
<td bgcolor="#004400" width="16.66%" align="center"><a href="votinghistory"><font color="white"><b>History</b></font></a></td>
<td bgcolor="#004400" width="16.66%" align="center"><a href="contacts"><font color="white"><b>Contacts</b></font></a></td>
<td bgcolor="#004400" width="16.66%" align="center"><a href="admin"><font color="white"><b>Admin</b></font></a></td>
</tr>
<tr>
<td>&nbsp;</td><td colspan="5"><font face="Times New Roman, serif" color="#004400" size="+2">$group Voting</font></td></tr>
<tr>
</table>
$login
EOF

{PrintError => 1, RaiseError => 1};
}

sub foot {
my $org = Conf::org();
my $url = Conf::url();
my $admin = Conf::admin();
print<<EOF;
<br>
Questions about this system?<br>
Contact the <a href="mailto:$admin"><b>Voting Admin</b></a>.<br>
The $org voting system is <a href="http://www.fsf.org/"><b>free software</b></a>, licensed under the <a href="http://www.gnu.org/"><b>GNU</b></a> General Public License <a href="http://greens.org/cwn/GPL"><b>(GPL)</b></a>.<br>
You can download a copy <a href="http://greens.org/cwn/projects/"><b>here</b></a>.<br>
To independently verify a <i>ranked choice vote</i>, or for information about how that works, go to <a href="http://lobitos.net/voting/"><b>Jonathan Lundell's Voting Page</b></a> and upload the ballot file from the ranked choice vote result page. JL's ranked choice module is licensed under an alternate free software license. 
<hr width=235 align=left>
<b><a href="$url">$org</a></b><br><br>
</html>

EOF

{PrintError => 1, RaiseError => 1};
}

1; #return true

