package ChoiceCounter;

#   Copyright (c) 2004 Jonathan Lundell; All Rights Reserved.
#
#   IMPORTANT: This software is supplied to you by Jonathan
#   Lundell ("Jonathan") in consideration of your agreement to
#   the following terms, and your use, installation,
#   modification or redistribution of this software
#   constitutes acceptance of these terms. If you do not
#   agree with these terms, please do not use, install,
#   modify or redistribute this software.
#
#   In consideration of your agreement to abide by the
#   following terms, and subject to these terms, Jonathan grants
#   you a personal, non-exclusive license to use, reproduce,
#   modify and redistribute this software, with or without
#   modifications, in source and/or binary forms; provided
#   that if you redistribute the software with
#   modifications, you must remove this copyright notice and
#   the following text and disclaimers in all such
#   redistributions. The name Jonathan Lundell and/or this
#   product may not be used to endorse or promote products
#   derived from this software without specific prior
#   written permission from Jonathan. Except as expressly stated
#   in this notice, no other rights or licenses, express or
#   implied, are granted by Jonathan herein, including but not
#   limited to any patent rights that may be infringed by
#   your derivative works or by other works in which the
#   software may be incorporated.
#
#   This software is provided by Jonathan on an "AS IS" basis. 
#   JONATHAN MAKES NO WARRANTIES, EXPRESS OR IMPLIED, INCLUDING
#   WITHOUT LIMITATION THE IMPLIED WARRANTIES OF
#   NON-INFRINGEMENT, MERCHANTABILITY AND FITNESS FOR A
#   PARTICULAR PURPOSE, REGARDING THE SOFTWARE OR ITS USE
#   AND OPERATION ALONE OR IN COMBINATION WITH YOUR
#   PRODUCTS.
#
#   IN NO EVENT SHALL JONATHAN BE LIABLE FOR ANY SPECIAL,
#   INDIRECT, INCIDENTAL OR CONSEQUENTIAL DAMAGES
#   (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
#   SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
#   PROFITS; OR BUSINESS INTERRUPTION) ARISING IN ANY WAY
#   OUT OF THE USE, REPRODUCTION, MODIFICATION AND/OR
#   DISTRIBUTION OF THE SOFTWARE, HOWEVER CAUSED AND WHETHER
#   UNDER THEORY OF CONTRACT, TORT (INCLUDING NEGLIGENCE),
#   STRICT LIABILITY OR OTHERWISE, EVEN IF JONATHAN HAS BEEN
#   ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

use strict;

#  choice-counter.pl
#
#  v1.0b5 2004-06-29  comments, rename variables, reorganize
#  v1.0b4 2003-11-17  quota=strict option
#  v1.0b3 2003-11-13  remove NOC logic
#                     flexible ballots= ordering
#                     allow numeric quota
#  v1.0b2 2003-11-12  change "NOTA" to "NOC"
#  v1.0b1 2003-11-11
#
#  TODO: prior-round tiebreaking option
#  TODO: calculate approval from hopeful piles only
#
#  Count a choice-voting STV election,
#    with fractional transfers and fractional quota.
#
#  The input file looks like this:
#
#  title=Centerville City Council Election 2005-04-03
#  # comments
#  seats=3
#  quota=droop	# droop: (ballots / (seats+1))
#                   # hare: (ballots / seats)
#  quota=103	# specify numeric quota
#  quota=mandatory # all winners must pass quota
#  approval=yes		# yes or no (for tie breaking)
#  random=37863		# seed for coin-toss tiebreaker
#  candidate=gb George Brown
#  candidate=mg Mary Green
#  candidate=ht Hermione Tan
#  candidate=hb Harvey Black
#  candidate=vs Violet Smith
#  ballots=34
#  gb ht hb
#  vs mg
#  ... (for a total of 34 ballot lines in this example)
#  end
#
#  Alternate ballot format
#  (same as above through candidate=)
#  order=gb ht mg hb vs		# order of candidates on ballot
#  ballots=34
#  1 2 - 3 - -
#  - - 2 1 - -
#  ...
#  end

#  Two ballot formats are suported.
#
#  Format 1: The ballot has one blank line per candidate, labelled 1, 2, 3, etc.
#            The voter fills in the name of candidates in preference order.
#            The ballots are very general, and no candidate benefits 
#            from being first on the list. It allows write-ins, if desired.
#
#  Format 2: The ballot lists the candidates, one per line.
#            The voter writes a rank number 1, 2, 3, etc next to candidate
#            names. candidates. Write-ins can be accommodated with blanks lines.
#            Use the "order=" command with this format.
#
#  Nicknames (initials or first names or any unique designation) are used to make the
#    format-1 ballot entries easier, and are used for the order= command in format 2.
#
#  The program will object if a ballot has an unkonwn nickname, or the same nickname appears twice,
#    or (in format 2) if the same rank number appears twice, or if numbers are skipped.
#
#  quota=mandatory (optional) in addition to the quota type specifies that passing
#    the quota is strictly required to be elected. Otherwise all seats are filled
#    (if possible) from the remaining active candidates even if they have not passed the
#    quota.
#
#  approval=yes (yes is the default) instructs the program to use candidate approval ratings
#    to break ties. A candidate's approval rating is their total number of mentions, ignoring rank order.
#    If approval=no or if two tied candidates have the same approval rating, the program tosses a virtual
#    coin to break ties. random= seeds the random number generator used as the coin tosser.
#    If random= is not specified, the result of the election can differ from run to run
#    if tiebreaking is required, so always specify a seed if you want repeatable results.
#
#  ballots= lets the program check the number of ballots actually entered.
#    ballots= and the ballots themselves must be the last lines in the file, terminated by "end".
#
#  title= specifies an arbitrary title for the output of the program.
#         Generally, it should have the name and date of the election.
#
#  Ideally, two files should be created, by different teams, and the results compared;
#    the final results should be identical, though the finishing order might vary if the
#    ballots are not in the same order.

#  Contact jlundell at greens dot org with comments, suggestions, or bug reports.
#  For bug reports, please include a copy of the program used, plus the election file(s).


my ($dumpflag, %cName, %cElected, $quota, %cHopeful, %cPiles, %cApproval, $verbose, $exhausted, $nballots);
my $version = "1.0b5";

sub choice {
my ($pid) = @_;

my $ballotfile =  "BALLOTS/b" . $pid;
my $resultsfile = "BALLOTS/r" . $pid;

#  variables that hold information from the input data file
#
my $title;				# title of election
my $seats;				# number of seats to be filled
my $quota;			# vote-count quota needed to be elected
my $mandatoryQuota = 0; # all winners must pass quota
my $ballots = 0;		# number of ballots specified in ballots= line
my $nballots = 0;		# number of ballots actually read
my $approval = 1;		# default to using approval for tie-breaking
my $dumpflag = 1;		# flag: set to 2 for full dump
my $verbose = 1;		# flag: 0 for winners only; 1 default; 2 very wordy

#  candidate-keyed hashes (key is candidate nickname)
#
my %cName = ();			# full name of candidate
my %cRandom = ();		# random number, per candidate, for tie-breaking
my %cApproval = ();		# approval rank, per candidate, for tie-breaking (optional)
my %cElected = ();		# elected candidates are entered here
my %cHopeful = ();		# hopeful candidates (neither elected or eliminated)
my %cWithdrawn = ();	# withdrawn candidates (legal on ballots, but no counted)
my %cPiles = ();		# pile of ballots for each candidate (references to anonymous arrays of ballots)
						# cPiles is the only data structure of non-trivial complexity
						# cPiles is a hash of ballot piles keyed by candidate nickname
						# each pile is an array of ballots
						# each ballot is a hash with members PREFS, INDEX, WEIGHT
						# PREFS is an array of voter preferences (candidate nicknames)
						# INDEX is the current index into PREFS
						# WEIGHT is the current weight of the ballot, initially 1
						# WEIGHT is reduced when the ballot is transferred from an elected candidate

my %order = ();			# candidate order on format-2 ballot

my $doorder = 0;		# using format-2 ballots
my $lineno = 0;			# input line number for reporting purposes
my $exhausted = 0;		# total voting weight of exhausted ballots

#
#  Read election file(s).
#  file name(s) are on command line
#  recommendation: everythiing in one file
#  but they can be split if desired
#  In this loop we read up to the ballots= line.
#

open (BALLOT, "$ballotfile");

while (<BALLOT>) {
	++$lineno;			# count lines
	chomp();			# strip trailing newline
	s/^\s+//;			# strip leading whitespace
	s/#.*//;			# strip trailing comment
	s/\s+$//;			# strip trailing whitespace
	s/\s*=\s*/=/;		# strip whitespace around =
	
	next if ( /^$/ );		# skip empty lines & comments
	last if ( /^end/i );	# explicit end-of-file

	#
	#  Parse the lines of the input file
	#  (except for ballots and "end").
	#
	if ( /^dump=(.*)/i ) { $dumpflag = $1; next }		# how much to dump
	if ( /^verbose=(.*)/i ) { $verbose = $1; next }		# verbosity
	if ( /^title=(.*)/i ) { $title = $1; next; }		# title
	if ( /^seats=(\d+)/i ) { $seats = $1; next; }		# number of seats
	if ( /^random=(\d+)/i ) { srand( $1 ); next; }		# random seed
	#
	#  Quota type. We'll evaluate it later.
	#
	s/^threshold=/quota=/i;		# backward compatibility
	if ( /^quota=mandatory$/i ) { $mandatoryQuota = $1; next; }
	if ( /^quota=strict$/i ) { $mandatoryQuota = $1; next; }	# backward compatibility
	if ( /^quota=(.*)/i ) { $quota = $1; next; }
	#
	#  order= says we're using format-2 ballots,
	#  where the ballot has a list of rankings.
	#  This line defines the order of candidates on the ballot.
	#
	if ( /^order=(.*)/i ) {
		my @cands = split /\s+/, $1;
		my $index = 0;
		my $cand;
		my $candx;
		foreach $cand ( @cands ) {
			foreach $candx ( keys %order ) {
				#
				#  A little sanity checking: complaint about duplicates.
				#
				if ( $cand eq $order{$candx} ) {
					#print "line ". $lineno . ": duplicate candidate in order=: " . $cand . "\n";
					exit(1);
				}
				#
				#  Make sure it's a candidate we know about.
				#
				if (not defined( $cName{$cand})) {
					#print "line ". $lineno . ": unknown candidate in order=: " . $cand . "\n";
					exit(1);
				}
			}
			++$index;	# 1, 2, 3...
			$order{$index} = $cand;	# record candidate's line on ballot
		}
		$doorder = 1;	# remember that we're format-2
		next;
	}
	#
	#  approval= can be yes or no only.
	#  If yes (the default), we try to break ties with approval rankings.
	#
	if ( /^approval=(.*)/i ) {
		if ( $1 =~ /^yes$/i ) { 
			$approval = 1;
		} elsif ( $1 =~ /^no$/i ) {
			$approval = 0;
		} else {
			#print "ERROR: approval must be yes or no\n";
			exit(1);
		}
		next;
	}
	#
	#  candidate= identifies a nickname and full name of a candidate.
	#
	if ( /^candidate=(\S+)\s+(.*)/i ) {
		if ( $cName{$1} ) {
			#print "ERROR: duplicate nickname: $1\n";
			exit(1);
		}
		$cName{$1} = $2;		# remember the candidate's full name
		$cHopeful{$1} = 1;		# hopeful until elected or eliminated
		$cApproval{$1} = 0;		# optional tie-breaker
		$cRandom{$1} = rand;	# final tie-breaker
		$cPiles{$1} = [ ];		# pile of ballots for this candidate
		next;
	}
	#
	#  withdrawn= identifies a nickname and full name of a withdrawn candidate.
	#
	if ( /^withdrawn=(\S+)\s+(.*)/i ) {
		if ( $cName{$1} ) {
			#print "ERROR: duplicate nickname: $1\n";
			exit(1);
		}
		$cName{$1} = $2;		# remember the candidate's full name
		$cWithdrawn{$1} = 1;	# flag as withdrawn
		$cApproval{$1} = 0;		# optional tie-breaker
		next;
	}
	#
	#  ballots= introduces the ballots, and must come last.
	#
	if ( /^ballots=(\d+)/i ) {
		$ballots = $1;			# ballot count for cross-checking
		last;
	}

	#
	#   We didn't recognize this line.
	#
	#print "Unknown input on line " . $lineno . ": " . $_ . "\n";
	exit(1);
}


if ( $ballots == 0) {
	#print "No ballots\n";
	exit(1);
}

#  Now read the ballots.
#

while (<BALLOT>) {
	++$lineno;			# count lines
	chomp();			# strip trailing newline
	s/^\s+//;			# strip leading whitespace
	s/#.*//;			# strip trailing comment
	s/\s+$//;			# strip trailing whitespace
	s/\s*=\s*/=/;		# strip whitespace around =

	next if ( /^$/ );		# skip empty lines & comments
	last if ( /^end/i );	# explicit end-of-file

	#
	# %ballot is a hash, with a list of choices,
	#   the current choice index, and the current weight.
	#
	# We build %ballot and then push it onto the pile
	# of its first-choice candidate.
	#
	my %ballot = ();			# an empty ballot hash
	my @prefs = split /\s+/;	# split the ballot line
								# into individual votes
	my @votes;
	my $vote;
	my %checkdupes = ();
	$ballot{ INDEX } = 0;		# this ballot is voting for its first choice
	$ballot{ WEIGHT } = 1;		# each ballot counts one vote to start with
								# the weight can go down if a winner's
								# excess votes are redistributed
	#
	#  If we're using format-2 ballots (order=)
	#  convert the ballot to format 1.
	#
	if ( $doorder ) {
	
		# All we allow are numeric ranks or '-' for no rank
		#
		if ( !/^[1-9- ]+(#.*)$/i ) {
			#print "Unknown input on line " . $lineno . ": " . $_ . "\n";
			exit(1);
		}

		my $c = 0;
		my %temp;
		#
		#  scan over rankings
		#
		foreach $vote ( @prefs ) {
			++$c;						# next candidate on ballot
			next if $vote eq "-";		# no ranking for this candidate
			$temp{$vote} = $order{$c};	# this candidate's rank on ballot
		}
		#
		#  Sort by rank and rebuild @prefs in rank order.
		#
		@prefs = ();
		my $lastvote = 0;
		foreach $vote (sort keys %temp) {
			#
			# Detect duplicate ranking (eg 1 2 2 3 4).
			# We'll report it, and let it go.
			# The ranking will be arbitrary.
			# Counters should check the ballot.
			#
			if ( $vote == $lastvote ) {
				#print "line ". $lineno . ": duplicate rank " . $vote . "\n";
			#
			# Detect skipped ranking (eg 1 2 3 5).
			# Report it and accept it.
			# Counters should check the ballot.
			#
			} elsif ( $vote != ($lastvote+1) ) {
				#print "line ". $lineno . ": skipped rank " . $vote . "\n";
			}
			$lastvote = $vote;
			push @prefs, $temp{$vote};
		}
	}
	#
	#  Now either we have a format-1 ballot,
	#  or a format-2 ballot that's been converted
	#  to format 1.
	#
	foreach $vote ( @prefs ) {
		#
		#  For each candidate, make sure s/he exists in the list.
		#  Counters should check the ballot and resolve the problem
		#  if possible.
		#
		#  The ballot will not be counted!
		#
		if (!defined( $cName{$vote} )) {
			#print "line ". $lineno . ": vote for unknown candidate: " . $vote . "\n";
		#
		#  Check whether this candidate appears twice on this ballot.
		#  Counters should check the ballot and resolve the problem
		#  if possible.
		#
		#  The ballot will not be counted!
		#
		} elsif ( $checkdupes{$vote} ) {
			#print "line ". $lineno . ": duplicate vote for: " . $vote . "\n";
		#
		#  If the candidate preference looks good,
		#  put it in the preference array,
		#  Bump the approval count for this candidate.
		#  
		} else {
			$checkdupes{$vote} = 1;
			push @votes, $vote;
			++$cApproval{$vote};
		}
	}
	#
	#  %ballot contains INDEX, WEIGHT and PREFS
	#  Save the prefs in the ballot, and put it on the ballot pile
	#  for its first-choice candidate.
	#
	#  If the ballot has no hopeful choices, we consider it valid,
	#  but immediately exhausted; it goes to no candidate.

	#
	#  First skip any withdrawn (non-hopeful) candidates.
	#
	while (1) {
		last if ( !defined($votes[$ballot{ INDEX }]) );
		last if ( $cHopeful{$votes[$ballot{ INDEX }]} );
		$ballot{ INDEX } += 1;
	}
	#
	#  Then push the ballot onto the pile for its first-choice hopeful candidate.
	#
	if ( defined($votes[$ballot{ INDEX }]) ) {
		$ballot{ PREFS } = [ @votes ];
		push @{$cPiles{$votes[$ballot{ INDEX }]}}, { %ballot };
	} else {
		++$exhausted;
	}
	++$nballots;			# count the ballots
	next;
}

close (BALLOT);

#
#  Having read all the parameters, candidates and ballots,
#  now a little more value checking.
#

open (RESULTS, ">>$resultsfile");

#print $title . "\n" if defined($title);
#print "Version: " . $version . "\n";
#print "Seats: " . $seats . "\n";
#print "Ballots: " . $nballots . "\n";
if ($ballots != $nballots) {
	#print "WARNING: ballot count does not match ballots=". $ballots . "\n";
	exit(1);
}

#
#  Determine the numeric quota as a function of
#    * the quota type
#    * the number of open seats
#    * the number of ballots cast
#
#  "droop" is the default, and the method specified by the GPUS & GPCA bylaws.
#
my $threshname;
if ($quota =~ /^droop$/i) {
	$quota = $nballots / ( $seats + 1);
	$threshname = "Droop";
} elsif ( $quota =~ /^hare$/i ) {
	$quota = $nballots / $seats;
	$threshname = "Hare";
} elsif ( $quota =~ /^\d+\.?\d*/ ) {
	$threshname = "Explicit";
} else {
	#print "FATAL: unknown quota: " . $quota . "\n";
	exit(1);
}
#print "Quota: " . $quota . " (" . $threshname . ")";
#print " (mandatory)" if ( $mandatoryQuota );
#print "\n";

dumpem("Start the rounds.");	# dumpem() is for debugging only

#  Now do the rounds.
#
#  1. Find the active candidate with the highest number of votes.
#     tie breakers: approval (optional), random
#     1a. If no candidates, we're done.
#
#  2. If result of #1 > quota, mark candidate elected
#     and distribute (pro rata) excess votes to next-place choices.
#     2a. If all seats filled, we're done.
#     2b. Otherwise go back to step 1.
#
#  3. Find active candidate with fewest votes.
#     tie breakers as in step 1
#     3a. Eliminate candidate
#     3b. Distribute votes to next-place choices on each ballot
#     3c. Go to step 1
#  
#  Notes.
#     Count exhausted ballots for use in check-total.
#     Check-total after each round is:
#        weight of votes for active candidates
#          (active means not elected and not eliminated)
#        plus weight of exhausted ballots
#        plus number of elected candidates times quota
#     Check-total should be equal to total votes cast
#        (within precision of our arithmetic)
#
my $rounds = 0;
while (1) {
	++$rounds;
	#print "\nRound " . $rounds . "\n" if ($verbose >= 1);
	#
	#  Find remaining candidate with highest vote.
	#
	my $highnick;
	my $highvote = 0;
	my $nick;
	foreach $nick ( keys %cName ) {		# for each candidate
		next if ( !$cHopeful{$nick} );	# hopeful candidates only
		my $vote = 0;
		my $ballot;
		my $pile = $cPiles{$nick};		# ballots choosing this candidate
		foreach $ballot ( @$pile ) {
			$vote += $ballot->{ WEIGHT };	# vote weight
		}
		#
		#  Update high-vote candidate
		#
		if ( !$highnick ) {
			$highnick = $nick;
			$highvote = $vote;
		} elsif ( $vote > $highvote ) {
			$highnick = $nick;
			$highvote = $vote;
		} elsif ( $vote == $highvote ) {
			#
			#  optionally breack ties with approval
			#
			if ($approval && ($cApproval{$nick} > $cApproval{$highnick}) ) {
				$highnick = $nick;
				$highvote = $vote;
			#
			#  break ties with coin toss
			#
			} elsif ( (!$approval || $cApproval{$nick} == $cApproval{$highnick}) && $cRandom{$nick} > $cRandom{$highnick} ) {
				$highnick = $nick;
				$highvote = $vote;
			}
		}
	}

	#
	#  If there are no more hopeful candidates, we're done.
	#
	#print "Hopeful candidates: " . scalar(keys(%cHopeful)) . "\n" if ($verbose >= 1);
	last if ( keys(%cHopeful) == 0 );

	#
	#  Otherwise print the high-vote candidate and indicate whether s/he crossedt the quota.
	#
	#print "High: " . $cName{$highnick} . " (" . $highvote . ")" if ($verbose >= 1);
	if ( $highvote > $quota ) {
		#print " (elected)\n" if ($verbose >= 1);
	} else {
		#print " (not elected)\n" if ($verbose >= 1);
	}

	#
	#  If the high-vote cnadidate crossed the quota, we've filled a seat.
	#
	if ( $highvote > $quota ) {
		$cElected{$highnick} = 1;		# mark as elected
		delete($cHopeful{$highnick});	# no longer hopeful
		#
		#  Distribute excess votes
		#  Each ballot goes to the next candidate on the ballot,
		#  with the ballot weight reduced according to the excess vote.
		#
		my $excess = $highvote - $quota;
		if ( $excess > 0 ) {
			my $pile = $cPiles{$highnick};
			my $ballot;
			#
			#  Distribute each ballot in the elected candidate's pile.
			#
			foreach $ballot ( @$pile ) {
				next if ( $ballot->{ WEIGHT } <= 0 );	# possible? maybe not.
				$ballot->{ WEIGHT } *= $excess / $highvote;	# reduce ballot weight
				my $prefs = $ballot->{ PREFS };
				#
				#  Find the next-place choice for this ballot.
				#  If no more choices, add to exhausted count for bookkeeping.
				#
				while (1) {
					$ballot->{ INDEX } += 1;
					if (defined( $prefs->[ $ballot->{ INDEX } ]) ) {
						# transfer ballot to next choice
						my $nextcand = $prefs->[ $ballot->{ INDEX } ];
						next if ( !$cHopeful{$nextcand} );
						push @{$cPiles{$nextcand}}, { %$ballot };
					} else {
						# ballot is exhausted
						$exhausted += $ballot->{ WEIGHT };
					}
					last;
				}
			}
		}
		dumpem( "round" );	# debug prints
		#
		#  Done if we've filled all the seats.
		#
		last if ( keys(%cElected) >= $seats );
		#
		#  Otherwise do another round.
		#
		next;	# next round
	}
	#
	#  No winner in this round.
	#    See if we're finished.
	#    Find & eliminate remaining hopeful candidate with lowest vote.
	#
	if ( $mandatoryQuota ) {
		#
		#  If we're using a mandatory quota, there's only one candidate left,
		#  and that candidate hasn't crossed the quota, we're done.
		#
		last if ( keys(%cHopeful) == 1 && $highvote <= $quota );
	} else {
		#
		#  Otherwise we can elect all the remaining hopefuls if the number of seats permits.
		#
		last if ( (keys(%cHopeful) + keys(%cElected)) <= $seats );
	}

	#
	#  Find a candidate to eliminate.
	#
	my $lowvote = $nballots + 1;
	my $lowcand;
	my $lownick;
	foreach $nick ( keys %cName ) {			# for each candidate
		next if ( !$cHopeful{$nick} );		# hopeful candidates only
		my $vote = 0;
		my $ballot;
		my $pile = $cPiles{$nick};
		foreach $ballot ( @$pile ) {
			$vote += $ballot->{ WEIGHT };	# vote weight for this candidate
		}
		#
		#  Find low vote of remaining candidates.
		#
		if ( !$lownick ) {
			$lownick = $nick;
			$lowvote = $vote;
		} elsif ( $vote < $lowvote ) {
			$lownick = $nick;
			$lowvote = $vote;
		} elsif ( $vote == $lowvote ) {
			#
			#  breack ties with approval
			#
			if ($approval && $cApproval{$nick} < $cApproval{$lownick}) {
				$lownick = $nick;
				$lowvote = $vote;
			#
			#  break ties with coin toss
			#
			} elsif ( (!$approval || $cApproval{$nick} == $cApproval{$lownick}) && $cRandom{$nick} < $cRandom{$lownick} ) {
				$lownick = $nick;
				$lowvote = $vote;
			}
		}
	}
	#
	#  Eliminate lowest candidate
	#  Distribute that candidate's votes
	#
	#print "Low: " . $cName{$lownick} . " (" . $lowvote . ") (eliminated)\n" if ($verbose >= 1);
	delete($cHopeful{$lownick});	# abandon hope
	my $pile = $cPiles{$lownick};
	my $ballot;
	foreach $ballot ( @$pile ) {
		next if ( $ballot->{ WEIGHT } == 0 );
		my $prefs = $ballot->{ PREFS };
		while (1) {
			$ballot->{ INDEX } += 1;
			if (defined( $prefs->[ $ballot->{ INDEX } ]) ) {
				# transfer ballot to next choice
				my $nextcand = $prefs->[ $ballot->{ INDEX } ];
				next if ( !$cHopeful{$nextcand} );	# distribute only to hopeful candidates
				push @{$cPiles{$nextcand}}, { %$ballot };
			} else {
				# ballot is exhausted
				$exhausted += $ballot->{ WEIGHT };
			}
			last;
		}
	}
	dumpem( "round" );
	# implicit next; round
}

#
#  If the quota isn't mandatory and we haven't filled all the seats,
#  fill them from the remaining active candidates.
#
my $nick;
if ( !$mandatoryQuota && keys(%cElected) < $seats ) {
	foreach $nick ( keys %cHopeful ) {
		$cElected{$nick} = 1;		# mark as elected
		delete($cHopeful{$nick});	# no longer active
	}
}

#
#  We've either filled all the available seats,
#  or eliminated all the candidates.
#
#  Summarize results.
#
#print "\nResults:\n";
foreach $nick ( keys %cName ) {	# for each candidate
	next if ( !$cElected{$nick} );
	print RESULTS "$nick,Elected\n";
}
foreach $nick ( keys %cName ) {	# for each candidate
	next if ( !$cHopeful{$nick} );
	print RESULTS "$nick,Hopeful\n";
}
foreach $nick ( keys %cName ) {	# for each candidate
	next if ( $cHopeful{$nick} || $cElected{$nick} || $cWithdrawn{$nick} );
	print RESULTS "$nick,Eliminated\n";
}
foreach $nick ( keys %cName ) {	# for each candidate
	next if ( !$cWithdrawn{$nick} );
	print RESULTS "$nick,Withdrawn\n";
}

close (RESULTS);
return 1;
}

#
# DUMP data structures
#
sub dumpem {
	return if ( !$dumpflag );
	my ($msg) = @_;		# label for printing
	my $nick;
	my $pile;
	my $ballot;
	my $prefs;
	my $vote;
	my $left = 0;
	my $total = 0;
	#print "\nDUMP " . $msg . "\n" if ($dumpflag > 1);
	foreach $nick ( keys %cName ) {
		#print "CAND: " . $nick . ": " . $cName{$nick} if ($dumpflag > 1);
		if ( $cElected{$nick} ) {
			$total += $quota;
			#print " elected\n" if ($dumpflag > 1);
			next;
		} elsif ( !$cHopeful{$nick} ) {
			#print " eliminated\n" if ($dumpflag > 1);
			next;
		}
		#print "\n" if ($dumpflag > 1);
		$pile = $cPiles{$nick};
		foreach $ballot ( @$pile ) {
			$left += $ballot->{ WEIGHT };
			#print " ballot index=" . $ballot->{ INDEX } if ($dumpflag > 1);
			#print " weight=" . $ballot->{ WEIGHT } if ($dumpflag > 1);
			$prefs = $ballot->{ PREFS };
			foreach $vote ( @$prefs ) {
				#print " " . $vote if ($dumpflag > 1);
			}
			#print "\n" if ($dumpflag > 1);
		}
		#print " approval: " . $cApproval{$nick} . "\n" if ($dumpflag > 1);
	}
	if ($verbose >= 1) {
		#print "Check votes: elected=" . $total . " + exhausted=" . $exhausted . " + left=" . $left . " = " . ($total + $left + $exhausted) . " / " . $nballots . " ballots\n";
	}
}

return 1;
