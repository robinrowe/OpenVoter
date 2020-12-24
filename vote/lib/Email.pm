package Email;

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

sub email {
my ($to, $replyto, $subject, $message) = @_;
my $from = Conf::admin();
$ENV{PATH} = "";
delete @ENV{'IFS', 'CDPATH', 'ENV', 'BASH_ENV'};

#Invoke qmail-inject, set a custom envelope.
my $mailprog = 'MAILPROG';
open (MAIL, "|$mailprog -f$from");
#Set message headers.
print MAIL "From: $from\n";
print MAIL "Reply-To: $replyto\n";
#address for undeliverable messages.
print MAIL "Errors-To: $from\n";
print MAIL "To: $to\n";
print MAIL "Subject: $subject\n\n";
#Body of message.
print MAIL "$message\n\n";
close(MAIL);
return 1;
}

return 1;
