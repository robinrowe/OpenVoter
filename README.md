# OpenVoter

Robin Rowe 2020/12/24

## Open Source Voting Systems

### Vote

Perl-based RCV voting system developed by the Green Party. Version in this repo was originally downloaded from https://gp.org/cgi-bin/vote. Per the file date on the tarball, it hadn't been updated since 2006/3/5. License is GPL2.

### CIVS 

Perl-based voting system developed by Cornell University at https://github.com/andrewcmyers/civs.

### Every Election System

Scala-based voting system at https://github.com/sanedragon/every-election-system.

##  Desired is a system that meets all the following criteria

- Identity - how do you make sure votes are coming from the people who are allowed to vote?
- Privacy - how can you make sure that no one can find out how a particular individual voted?
- Auditable - how can you prove that all the votes counted were the votes that were cast?
- Traceable - how can an individual see that their vote was counted?
- Usability - how easy is it for a user to understand and use the system?

## Configurability

In addition, systems either need to be configurable to handle all the possible outcomes to these questions.

- A voter see their vote after it has been cast
- A voter can/cannot refund (change) his or her vote after it has been cast before the election deadline closes
- Anyone can/cannot see vote totals as they are collected, and the administrator may change that to midvote to can, but not to cannot
- Ballots are ACID and the system fault-tolerant, so crash cannot result in an half-cast ballot
- Spoiled ballots, if they can exist, are rejected
- How the system handles two ballots for the same voter is the same as vote refund, that the second overrides the first
- Choose voting methodologies, with the support for every type: plurality, approval, rank choice, score, star, black ball...
- A ballot may contain different voting methodologies for different races
