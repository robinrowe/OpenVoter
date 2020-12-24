#!/usr/bin/perl -w

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

#IMPORTANT:
#Please read the comments at the top and bottom of this program - you must fill in the requested info or the program will not work.
#You need to enter the path to the Perl modules that came with votingpages.
#You also need to enter the name of your database and the username and password for the user that has CREATE and DROP TABLE privileges.
#You will need to enter a votingpages user with admin priviledges at the bottom of this script.
#The program will not work unless you make these modifications!

use strict;
use DBI;
use DBI;
use strict;
use warnings;
#Put the path to the Perl modules that came with votingpages in the use lib statement below. Don't include a trailing slash.
use lib qw(LIB);
use Conf;

#Create tables
#Run this once when you are setting  up.

my $dbh = DBI->connect (
	'DBI:mysql:DBNAME',	#add your database name after the colon
	'DBUSER',		#add the user name with all priviledges in the quotes.
	'DBPW'		#add the password for that user in the quotes.
	);

$dbh->do('DROP TABLE IF EXISTS greenid');
$dbh->do('DROP TABLE IF EXISTS propid');
$dbh->do('DROP TABLE IF EXISTS candid');
$dbh->do('DROP TABLE IF EXISTS signons');
$dbh->do('DROP TABLE IF EXISTS greens');
$dbh->do('DROP TABLE IF EXISTS roles');
$dbh->do('DROP TABLE IF EXISTS greens_roles');
$dbh->do('DROP TABLE IF EXISTS greens_bios');
$dbh->do('DROP TABLE IF EXISTS districts');
$dbh->do('DROP TABLE IF EXISTS disttypes');
$dbh->do('DROP TABLE IF EXISTS proposals');
$dbh->do('DROP TABLE IF EXISTS phases');
$dbh->do('DROP TABLE IF EXISTS votes');
$dbh->do('DROP TABLE IF EXISTS irv');
$dbh->do('DROP TABLE IF EXISTS irvvotes');
$dbh->do('DROP TABLE IF EXISTS cands');
$dbh->do('DROP TABLE IF EXISTS committees');
$dbh->do('DROP TABLE IF EXISTS states');


$dbh->do('CREATE TABLE greenid (greenid int unsigned NOT NULL, PRIMARY KEY(greenid)) MAX_ROWS = 1 TYPE=InnoDB');

$dbh->do('CREATE TABLE propid (propid int unsigned NOT NULL, PRIMARY KEY(propid)) MAX_ROWS = 1 TYPE=InnoDB');

$dbh->do('CREATE TABLE candid (candid int unsigned NOT NULL, PRIMARY KEY(candid)) MAX_ROWS = 1 TYPE=InnoDB');

$dbh->do('CREATE TABLE signons (gid int unsigned NOT NULL, signon varchar(20) NOT NULL, pw varchar(15) NOT NULL, lastlogin datetime, sentreminder tinyint unsigned, PRIMARY KEY(gid), UNIQUE KEY signon(signon), KEY sentreminder(sentreminder)) TYPE=InnoDB');

$dbh->do('CREATE TABLE greens (gid int unsigned NOT NULL, fname varchar(20) NOT NULL, lname varchar(20) NOT NULL, add1 varchar(25), add2 varchar(20), city varchar(25), stateabbr varchar(2), zip varchar(10), email varchar(80), phd varchar(20), phe varchar(20), phc varchar(20), phf varchar(20), moddate timestamp(8) NOT NULL, PRIMARY KEY(gid), KEY lname (lname), KEY fname (fname), KEY moddate(moddate)) TYPE=InnoDB');

$dbh->do('CREATE TABLE roles (roleid smallint unsigned NOT NULL auto_increment, role varchar(25) NOT NULL, PRIMARY KEY(roleid)) TYPE=InnoDB');

$dbh->do('CREATE TABLE greens_roles (gid int unsigned NOT NULL, commid smallint unsigned NOT NULL, roleid smallint unsigned NOT NULL, distid smallint unsigned NOT NULL, actstatus tinyint unsigned NOT NULL default \'1\', moddate timestamp(8) NOT NULL, PRIMARY KEY(gid,commid,roleid,distid), KEY actstatus (actstatus), KEY moddate(moddate)) TYPE=InnoDB');

$dbh->do('CREATE TABLE greens_bios (gid int unsigned NOT NULL, bio text NOT NULL, pic tinyint unsigned NOT NULL, PRIMARY KEY(gid), KEY pic(pic)) TYPE=InnoDB');

$dbh->do('CREATE TABLE districts (distid smallint unsigned NOT NULL auto_increment, abbr varchar(5), district varchar(30) NOT NULL, disttypeid tinyint unsigned NOT NULL, voters smallint unsigned NOT NULL, maxvoters smallint unsigned NOT NULL, accreddate date, website varchar(100), actstatus tinyint unsigned NOT NULL default \'1\', moddate timestamp(8) NOT NULL, PRIMARY KEY(distid), UNIQUE KEY district(district), KEY disttypeid (disttypeid), KEY voters (voters), KEY maxvoters (maxvoters), KEY accreddate(accreddate), KEY actstatus (actstatus), KEY moddate (moddate)) TYPE=InnoDB');

$dbh->do('CREATE TABLE disttypes (disttypeid smallint unsigned NOT NULL auto_increment, disttype varchar(10) NOT NULL, PRIMARY KEY(disttypeid), KEY disttype (disttype)) TYPE=InnoDB');

$dbh->do('CREATE TABLE committees (commid smallint unsigned NOT NULL auto_increment, comm varchar(50) NOT NULL, webpage varchar(100), listserv varchar(100), actstatus tinyint unsigned NOT NULL default \'1\', moddate timestamp(8) NOT NULL, PRIMARY KEY(commid), KEY actstatus(actstatus), KEY moddate(moddate)) TYPE=InnoDB');

$dbh->do('CREATE TABLE states (abbr varchar(2) NOT NULL, state varchar(30) NOT NULL, PRIMARY KEY(abbr)) TYPE=InnoDB');

$dbh->do('CREATE TABLE proposals (pid smallint unsigned NOT NULL, title varchar(100) NOT NULL, gid int unsigned NOT NULL, presenter varchar(100) NOT NULL, background text NOT NULL, proposal text NOT NULL, resources text, reference text, discussbegin date NOT NULL, discussend date NOT NULL, votebegin date NOT NULL, voteend date NOT NULL, presens double unsigned NOT NULL, presensval smallint unsigned, consens double unsigned NOT NULL, consensval smallint unsigned, phaseid tinyint unsigned NOT NULL, distnum smallint unsigned, result varchar(30), moddate timestamp NOT NULL, PRIMARY KEY(pid), KEY gid(gid), KEY discussbegin (discussbegin), KEY discussend (discussend), KEY votebegin (votebegin), KEY voteend (voteend), KEY presens (presens), KEY consens (consens), KEY phaseid (phaseid), key result (result), key moddate (moddate)) TYPE=InnoDB');

$dbh->do('CREATE TABLE phases (phaseid tinyint unsigned NOT NULL auto_increment, phase varchar(25) NOT NULL, PRIMARY KEY(phaseid)) TYPE=InnoDB');

$dbh->do('CREATE TABLE votes (pid smallint unsigned NOT NULL, gid int unsigned NOT NULL, distid smallint unsigned NOT NULL, roleid smallint unsigned NOT NULL, vote varchar(10) NOT NULL, moddate timestamp NOT NULL, PRIMARY KEY(pid,gid,distid), KEY roleid(roleid), KEY vote(vote), KEY moddate(moddate)) TYPE=InnoDB');

$dbh->do('CREATE TABLE irv (pid smallint unsigned NOT NULL, title varchar(100) NOT NULL, gid int unsigned NOT NULL, numseats tinyint unsigned NOT NULL, background text, discussbegin date NOT NULL, discussend date NOT NULL, votebegin date NOT NULL, voteend date NOT NULL, presens double unsigned NOT NULL, presensval smallint unsigned, phaseid tinyint unsigned NOT NULL, distnum smallint unsigned, moddate timestamp NOT NULL, PRIMARY KEY(pid), KEY gid (gid), KEY numseats(numseats), KEY discussbegin (discussbegin), KEY discussend (discussend), KEY votebegin (votebegin), KEY voteend (voteend), KEY phaseid (phaseid), key moddate (moddate)) TYPE=InnoDB');

$dbh->do('CREATE TABLE cands (candid smallint unsigned NOT NULL, pid smallint unsigned NOT NULL, cand varchar(50) NOT NULL, info text, resources text, references text, candtype tinyint unsigned NOT NULL, equiv tinyint unsigned, result varchar(30), actstatus tinyint unsigned NOT NULL default \'1\', moddate timestamp NOT NULL, PRIMARY KEY(candid,pid), KEY cand (cand), KEY candtype(candtype), KEY equiv(equiv), KEY result(result), KEY actstatus(actstatus), KEY moddate(moddate)) TYPE=InnoDB');

$dbh->do('CREATE TABLE irvvotes (pid smallint unsigned NOT NULL, voterkey varchar(60) NOT NULL, candid smallint unsigned NOT NULL, candrank tinyint unsigned NOT NULL, PRIMARY KEY(pid,voterkey,candid), KEY candrank(candrank)) TYPE=InnoDB');

#Add values for supporting tables.

my $comm = Conf::group();
$dbh->do('INSERT INTO committees (comm, actstatus) VALUES ("$comm", 1), ("Accreditation", 1)');

$dbh->do('INSERT INTO greenid (greenid) VALUES(2)');

$dbh->do('INSERT INTO propid (propid) VALUES(1)');

$dbh->do('INSERT INTO candid (candid) VALUES(1)');

$dbh->do('INSERT INTO roles (role) VALUES("Delegate"), ("Alternate"), ("Observer"), ("Admin"), ("Steering Committee"), ("Staff"), ("Co-Chair"), ("Member"), ("Advisor")');

$dbh->do('INSERT INTO states (state, abbr) VALUES("Alabama", "AL"), ("Alaska", "AK"), ("Arizona", "AZ"), ("Arkansas", "AR"), ("California", "CA"), ("Colorado", "CO"), ("Connecticut", "CT"), ("Delaware", "DE"), ("District of Columbia", "DC"), ("Florida", "FL"), ("Georgia", "GA"), ("Hawaii", "HI"), ("Idaho", "ID"), ("Illinois", "IL"), ("Indiana", "IN"), ("Iowa", "IA"), ("Kansas", "KS"), ("Kentucky", "KY"), ("Louisiana", "LA"), ("Maine", "ME"), ("Maryland", "MD"), ("Massachusetts", "MA"), ("Michigan", "MI"), ("Minnesota", "MN"), ("Mississippi", "MS"), ("Missouri", "MO"), ("Montana", "MT"), ("Nebraska", "NE"), ("Nevada", "NV"), ("New Hampshire", "NH"), ("New Jersey", "NJ"), ("New Mexico", "NM"), ("New York", "NY"), ("North Carolina", "NC"), ("North Dakota", "ND"), ("Ohio", "OH"), ("Oklahoma", "OK"), ("Oregon", "OR"), ("Pennsylvania", "PA"), ("Rhode Island", "RI"), ("South Carolina", "SC"), ("South Dakota", "SD"), ("Tennessee", "TN"), ("Texas", "TX"), ("Utah", "UT"), ("Vermont", "VT"), ("Virginia", "VA"), ("Washington", "WA"), ("West Virginia", "WV"), ("Wisconsin", "WI"), ("Wyoming", "WY")');

$dbh->do('INSERT INTO phases (phase) VALUES("New"), ("Discussion"), ("Voting"), ("Certification"), ("Closed"), ("Cancelled")');

#First User - with admin priviledges for the program - enter the first and last names and email for the program admin in the quotes below:

$dbh->do('INSERT INTO greens (gid, fname, lname, email) VALUES(1, "FNAME", "LNAME", "ADDY")');

#First User roles:

$dbh->do('INSERT INTO greens_roles (gid, commid, roleid, distid) VALUES(1, 1, 4, 0)');

#First User login - enter a login name and a password in the quotes below:

$dbh->do('INSERT INTO signons (gid, signon, pw) VALUES(1, "LOGIN", "PASSWORD")');

$dbh->disconnect;
exit();

