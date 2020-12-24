package Conf;

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

sub org {
my $org = "";	#The name of your organization
return $org;
}

sub acronym {
my $acronym = "";	#Your organization's acronym
return $acronym;
}

sub group {
my $group = "";		#The decision making body, a committee name.
return $group;
}

sub repunit {
my $repunit = "";	#The units represented. States, Counties, Local Chapters for example
return $repunit;
}

sub logo {
my $logo = "../../vote/logo.gif";	#relative path from your script to your logo image file
return $logo;
}

sub progressbar {
my $progressbar = "../../vote/green.gif";	#relative path from your script to your progressbar image file
return $progressbar;
}

sub url {
my $url = "";		#Your organization's home page
return $url;
}

sub scripturl {
my $scripturl = "";	#The base URL for the scripts. No trailing slash or it will return invalid links!
return $scripturl;
}

sub oldresultspage {
my $oldresultspage = "";	#The URL to the page where your old vote results are kept.
return $oldresultspage;
}

sub delinfo {
my $delinfo = "";	#The URL to the page where information for your delegates, representatives or voters
return $delinfo;
}

sub cookiename {
my $cookiename = "";	#The cookie name you want to use for this program
return $cookiename;
}

sub admin {
my $admin = "";		#The admin's email address - the @ must be escaped with a \ or an error will occur
return $admin;
}

sub listserv {
my $listserv = "";	#Listserv where the program may send info. The @ must be escaped with a \ or an error will occur
return $listserv;
}

sub voteendtime {
my $voteendtime = "Midnight Pacific Time";	#Or some other time zone, spelled out.
return $voteendtime;
}

1; #return true


