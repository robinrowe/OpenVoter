#!/usr/bin/perl

#Copyright (C) 2005 Susan J. Dridi sdridi@greens.org
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


#This script will:

#1. Change paths
# a. Add the path to your library directory in all of the scripts on the line that reads: use lib qw(LIB);
# b. Add the path to your temp directory in all of the scripts on the line that reads: my $temp = qw(TEMP);
# c. Add the path to your ballots directory on line 272 of certifyirv.
# d. Add the path to your library directory in the Email.pm, HeadFoot.pm, DataCheck.pm, CgiErrors.pm, voteend, and phases files.
# e. Add the path to your temp directory in the phases file, on line 143.
# f. Add the path to your email program to the Email.pm file.
# g. Add the path to your library directory in create.pl
# h. Add the path to your ballots directory on lines 152 and 153 of ChoiceCounter.pm.

#2. Edit the create.pl and DBConnect.pm files

use strict;
use warnings;
use Fcntl;
use Tie::File;

my ($lib, $temp, $ballot, $email, $dirloc, $scriptsdir, $middle, $db, $dba, $dbap, $dbw, $dbwp, $dbr, $dbrp, $almost, $fname, $lname, $addy, $login, $password, $finish);

start();

sub start {

print "\n\nFirst we need some path information.\n\n";

print "Enter the full path for the modules directory (NOT including a trailing slash):";
$lib = <STDIN>;

print "Enter the full path for the temp directory for this program (NOT including a trailing slash):";
$temp = <STDIN>;

print "Enter the full path for the ballots directory (NOT including a trailing slash):";
$ballot = <STDIN>;

print "Enter the full path for your email program (NOT including a trailing slash):";
$email = <STDIN>;

print "Have you moved the files to their appropriate directories (m) or are they still in the original directories (o)? (m/o)\n";
$dirloc = <STDIN>;
chomp ($dirloc);

if ($dirloc eq "m")
	{
	print "What is the full path to your scripts?\n";
	$scriptsdir = <STDIN>;
	}
	
print "\nPath Info:\n\n";
print "Modules Directory: $lib";
print "Program Temp Directory: $temp";
print "Program Ballots Directory: $ballot";
print "Email Program Path: $email";

if ($dirloc eq "m")
	{
	print "Files are in their new location.\n";
	print "Your scripts are located at $scriptsdir";
	}
elsif ($dirloc eq "o")
	{print "Files are in their original location.\n";}
else
	{
	print "Have you moved the files to their appropriate directories (m) or are they still in their original directories (o)? (m/o)\n";
	$dirloc = <STDIN>;
	}

print "\nIs this correct? (type q to quit) - (y/n/q):";
chomp ($middle = <STDIN>);
if ($middle eq "y")
	{middle();}
elsif ($middle eq "q")
	{exit();}
else
	{start();}

}

sub middle {

print "\n\nNext we need some information about your database.\n\n";

print "Enter the name of your database:";
$db = <STDIN>;

print "Enter the DB user name with CREATE and DROP TABLE privileges:";
$dba = <STDIN>;

print "Enter the password:";
$dbap = <STDIN>;

print "Enter the DB user name with INSERT, UPDATE, DELETE and SELECT privileges:";
$dbw = <STDIN>;

print "Enter the password:";
$dbwp = <STDIN>;

print "Enter the DB user name with SELECT privileges:";
$dbr = <STDIN>;

print "Enter the password:";
$dbrp = <STDIN>;

print "\nDatabase Info:\n\n";
print "Database Name: $db";
print "Powerful User: $dba";
print "Password: $dbap";
print "Write User: $dbw";
print "Password: $dbwp";
print "Read User: $dbr";
print "Password: $dbrp";

print "\nIs this correct? (type q to quit) - (y/n/q):";
chomp ($almost = <STDIN>);
if ($almost eq "y")
	{almost();}
elsif ($almost eq "q")
	{exit();}
else
	{middle();}
}

sub almost {

print "\n\nFinally, we need to create an initial user with admin privileges for the program.\n\n";

print "First Name:";
$fname = <STDIN>;

print "Last Name:";
$lname = <STDIN>;

print "Email Address:";
$addy = <STDIN>;

print "Login Name (no spaces!):";
$login = <STDIN>;

print "Password (no spaces!):";
$password = <STDIN>;

print "\nInitial Program User Info:\n\n";
print "First Name: $fname";
print "Last Name: $lname";
print "Email Address: $addy";
print "Login: $login";
print "Password: $password";

print "\nIs this correct? (type q to quit) - (y/n/q):";
chomp ($finish = <STDIN>);
if ($finish eq "y")
	{finish();}
elsif ($finish eq "q")
	{exit();}
else
	{almost();}
}


sub finish {

#Replace the path info in the scripts and modules:

chomp ($lib);
chomp ($temp);
chomp ($ballot);
chomp ($email);
chomp ($dirloc);
if ($scriptsdir)
	{chomp ($scriptsdir);}

#Remove any trailing slashes from the directories:

chomp ($db);
chomp ($dba);
chomp ($dbap);
chomp ($dbw);
chomp ($dbwp);
chomp ($dbr);
chomp ($dbrp);

chomp ($fname);
chomp ($lname);
chomp ($addy);
chomp ($login);
chomp ($password);

my $file;

#Edit the create.pl file:
my @create;
tie @create, 'Tie::File', "create.pl" or die $!;
for (@create) {
	s+LIB+$lib+g;
	s+DBNAME+$db+g;
	s+DBUSER+$dba+g;
	s+DBPW+$dbap+g;
	s+FNAME+$fname+g;
	s+LNAME+$lname+g;
	s+ADDY+$addy+g;
	s+LOGIN+$login+g;
	s+PASSWORD+$password+g;
	}

#Edit the use lib qw(LIB) line in the following files:
if ($dirloc eq "o")
	{
	my $libdir = qw(../lib);
	chdir($libdir) or die $!;
	}
else
	{chdir($lib) or die $!;}

my @libfiles = qw(voteend phases HeadFoot.pm Email.pm DataCheck.pm crontab.txt CgiErrors.pm);

foreach $file(@libfiles)
	{
	my @lib;
	tie @lib, 'Tie::File', $file or die $!;
	for (@lib) {
		s+LIB+$lib+g;
		}
	}

#Edit the $temp = ""; lines in the phases file:
my @phases;
tie @phases, 'Tie::File', "phases" or die $!;
for (@phases) {
	s+TEMP+$temp+g;
	}

#Edit the Email program line in the Email.pm file:
my @emails;
tie @emails, 'Tie::File', "Email.pm" or die $!;
for (@emails) {
	s+MAILPROG+$email+g;
	}

#Edit the ballots path line in the ChoiceCounter.pm file:
my @ballots1;
tie @ballots1, 'Tie::File', "ChoiceCounter.pm" or die $!;
for (@ballots1) {
	s+BALLOTS+$ballot+g;
	}

#Add the database info to DBConnect:
my @dbfile;
tie @dbfile, 'Tie::File', "DBConnect.pm" or die $!;
for (@dbfile) {
	s+DBNAME+$db+g;
	s+DBW+$dbw+g;
	s+DBPW+$dbwp+g;
	s+DBR+$dbr+g;
	s+DBPR+$dbrp+g;
	}


$scriptsdir = qw(../scripts) unless $scriptsdir;

chdir($scriptsdir) or die $!;

my @scripts = qw(admin admindel admindelegates admindistricts admindist admincommittees admincom adminirv adminprop certifyirv contacts delegates index login logout irvdetail irvresult propdetail propresult viewballot vote voteresults votinghistory);

foreach $file(@scripts)
	{
	my @script;
	tie @script, 'Tie::File', $file or die $!;
	for (@script) {
		s+LIB+$lib+g;
		s+TEMP+$temp+g;
		}
	}

#Edit the ballots path line in the certifyirv file:
my @ballots2;
tie @ballots2, 'Tie::File', "certifyirv" or die $!;
for (@ballots2) {
	s+BALLOTS+$ballot+g;
	}


print "\n\nNow just edit the Conf.pm file.\n";
print "Conf.pm contains examples of the organization specific items to edit.\n";
print "Then run create.pl and you should be up and running!\n\n";

exit();
}
