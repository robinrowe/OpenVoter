package DateFormats;

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

sub display {
#format dates to dd-mm-ccyy instead of ccyy-mm-dd
my ($dt) = @_;
my $year = substr($dt, 0, 4);
my $month = substr($dt, 5, 2);
my $day = substr($dt, 8, 2);
my $date = $month . "/" . $day  . "/" . $year;
return $date;
}

sub today {
my $year = (localtime)[5] + 1900;
my $month = (localtime)[4] + 1;
if (length($month) == 1)
	{
	$month = "0" . $month;
	}
my $day = (localtime)[3];
if (length($day) == 1)
	{
	$day = "0" . $day;
	}
my $date = "$year$month$day";
return $date;
}

sub todayfmt {
my $year = (localtime)[5] + 1900;
my $month = (localtime)[4] + 1;
if (length($month) == 1)
	{
	$month = "0" . $month;
	}
my $day = (localtime)[3];
if (length($day) == 1)
	{
	$day = "0" . $day;
	}
my $date = "$year" . "-" . "$month" . "-" . "$day";
return $date;
}

sub future {
my ($add) = @_;
my $now = time;
my $duedate = $now + ($add * 86400);
my $dueyear = (localtime($duedate))[5] + 1900;
my $duemonth = (localtime($duedate))[4] + 1;
if (length($duemonth) == 1)
	{
	$duemonth = "0" . $duemonth;
	}
my $dueday = (localtime($duedate))[3];
if (length($dueday) == 1)
	{
	$dueday = "0" . $dueday;
	}
my $futuredate = "$dueyear$duemonth$dueday";	
return $futuredate;
}

sub futurefmt {
my ($add) = @_;
my $now = time;
my $duedate = $now + ($add * 86400);
my $dueyear = (localtime($duedate))[5] + 1900;
my $duemonth = (localtime($duedate))[4] + 1;
if (length($duemonth) == 1)
	{
	$duemonth = "0" . $duemonth;
	}
my $dueday = (localtime($duedate))[3];
if (length($dueday) == 1)
	{
	$dueday = "0" . $dueday;
	}
my $date = "$dueyear" . "-" . "$duemonth" . "-" . "$dueday";
return ($date);	
}

sub past {
my ($subtract) = @_;
my $now = time;
my $date = $now - ($subtract * 86400);
my $pastyear = (localtime($date))[5] + 1900;
my $pastmonth = (localtime($date))[4] + 1;
if (length($pastmonth) == 1)
	{
	$pastmonth = "0" . $pastmonth;
	}
my $pastday = (localtime($date))[3];
if (length($pastday) == 1)
	{
	$pastday = "0" . $pastday;
	}
my $pastdate = "$pastyear$pastmonth$pastday";	
return $pastdate;
}

sub thisyear {
my $year = (localtime)[5] + 1900;
return $year;
}

sub thismonth {
my $month = (localtime)[4] + 1;
if (length($month) == 1)
	{
	$month = "0" . $month;
	}
return $month;
}

sub thisday {
my $day = (localtime)[3];
if (length($day) == 1)
	{
	$day = "0" . $day;
	}
return $day;
}

sub monthlist {
my @monthlist = qw(01 02 03 04 05 06 07 08 09 10 11 12);
return @monthlist;
}

sub daylist {
my @daylist = qw(01 02 03 04 05 06 07 08 09);
push (@daylist, (10 .. 31));
return @daylist;
}

sub tsfmt {
#format timestamp fields for printing with dashes between the date elements
my ($ts) = @_;
my $year = substr($ts, 0, 4);
my $month = substr($ts, 4, 2);
my $day = substr($ts, 6, 2);
my $date = $year . "-" . $month  . "-" . $day;
return $date;
}

sub tsdisplay {
#format timestamp fields for printing with slashes between the date elements
my ($ts) = @_;
my $year = substr($ts, 0, 4);
my $month = substr($ts, 4, 2);
my $day = substr($ts, 6, 2);
my $date = $month . "/" . $day  . "/" . $year;
return $date;
}

return 1;
