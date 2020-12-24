package CgiErrors;

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
use CGI;
use CGI::Carp qw(fatalsToBrowser set_message);
use lib qw(LIB);
use Conf;

BEGIN {
	sub handle_errors {
	my $q = new CGI;
	my $script = $q->url;
	my $admin = Conf::admin();
	print "<h1>Software Error</h1>";
	print "<p>There appears to be a bug in the software.<p>Please write to the <a href=\"mailto:$admin\">Voting Admin</a> to report this error with the date, time and page:<ul>$script</ul>along with what you tried to do when the error occured."
	}
	set_message(\&handle_errors);
}

1;	#return true
