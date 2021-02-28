# OpenVoter

Robin Rowe 2020/12/24

## Overview

OpenVoter is an open source voting alternative to systems such as OpaVote. Rather than develop a system from scratch, we are evaluating all open source voting systems available (4 currently). To pick one to extend into a platform that supports all voting methodologies. 

For political party committees, or any group, that would prefer to operate their own voting system. Reasons to prefer OpenVoter include avoiding sharing personal data of your voters with SaaS companies, being able to review the source code, being able to extend the system to support more voting methodologies and features, and that the software is free.

We are also interested in systems used to collect signatures for candidacy or referendum. California doesn't have an online system for that yet, but Arizona does. https://apps.azsos.gov/equal/ Lacking a digital system, California signatures are collected on paper in person and the signatures verified by comparing signatures on file at city hall by the city clerk. The voter signatures to compare come from the California DMV driver's license database. California voters can check their registration or register at https://voterstatus.sos.ca.gov.

Voting system experts, students, researchers and programmers are welcome to join our Slack channel. Email Robin Rowe <robin@robinsrowe.com>. 

## Open Source Voting Systems

### Vote

Perl-based RCV voting system developed by the Green Party. Version in this repo was originally downloaded from https://gp.org/cgi-bin/vote. Per the file date on the tarball, it hadn't been updated since 2006/3/5. License is GPL2.

### CIVS 

Perl-based voting system developed by Cornell University at https://github.com/andrewcmyers/civs.

### Every Election System

Scala-based voting system at https://github.com/sanedragon/every-election-system.

### DemoChoice 

RCV using PHP and C++ at https://demochoice.org.

##  Desired is a system that meets all the following criteria

- Identity - how do you make sure votes are coming from the people who are allowed to vote?
- Privacy - how can you make sure that no one can find out how a particular individual voted?
- Auditable - how can you prove that all the votes counted were the votes that were cast?
- Traceable - how can an individual see that their vote was counted?
- Usability - how easy is it for a user to understand and use the system?
- Reliability - Ballots are ACID and blockchained and the system fault-tolerant, a crash cannot result in a half-cast ballot?
- Security - Fraudulent ballots have a clear process resolution?

## Configurability

In addition, systems need to be configurable to handle all the possible outcomes to the above questions.

- A voter can review his or her vote after it has been cast
- A voter can/cannot refund (change) his or her vote after it has been cast before the election deadline closes
- If voter refunds are allowed, the second overrides the first, but may require permission of admin
- How the system handles two ballots from the same voter is the same as with vote refund, but if vote refunds not allowed the voter may be marked for audit
- Anyone can/cannot see vote totals as they are collected
- An admin may change vote totals visibility midvote from cannot to can, but not from can to cannot
- Spoiled ballots are handled as vote refunds and marked for audit
- Choose voting methodologies, with the support for every type: plurality, approval, rank choice, score, star, black ball...
- A ballot may contain different voting methodologies for different races in the same election

## Voting Methodologies

- FPP https://en.wikipedia.org/wiki/First-past-the-post_voting
- RCV https://en.wikipedia.org/wiki/Ranked-choice_voting_in_the_United_States
- Approval https://electionscience.org/approval-voting-101/

## Contact

Robin Rowe <robin@robinsrowe.com>
323-535-0952 Los Angeles
