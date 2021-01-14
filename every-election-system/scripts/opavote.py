#! /usr/bin/python
# Parse OpaVote's BLT format.

import sys
import json

file = open(sys.argv[1])

# parse out the meaningful portion of the next line
# Blank lines, extra white space, and any comments (text after a #) are ignored.
def getLine():
    line = ""
    while(not line):
        rawline = file.readline()
        parts = rawline.split("#", 1)
        line = parts[0].strip()
    return line

# The first line has two numbers indicating the number of candidates and the number of seats.
meta = getLine().split()
num_candidates = int(meta[0])
num_seats = int(meta[1])

rawBallots = []
# The second line is the first ballot, and each following line is another ballot until you reach the end of ballots marker. Each ballot is a separate line.

# A line with only a 0 is an end of ballots marker and indicates that the previous line was the last ballot.
def atEndOfBallots():
    return line == "0"

line = getLine()
while(not atEndOfBallots()):
    ballot_parts = line.split()
    # The first number on a ballot line indicates a ballot weight, and for most elections, this will always be 1. The last number on a ballot line is always 0 to indicate the end of a ballot.
    weight = float(ballot_parts[0])

    # The other numbers on a ballot line indicate the rankings. The second number on a ballot line is the candidate number of the first ranked candidate, the third number on a ballot line is the candidate number of the second ranked candidate, and so forth.
    # A ballot line of "1 0" is an empty ballot that did not rank any candidates. If a ballot ranks 1 candidate, then the ballot line will have 3 numbers. If a ballot ranks 4 candidates, then the ballot line will have 6 numbers.
    candidateOrder = [int(x) for x in ballot_parts[1:-1]]
    rawBallots.append({"weight": weight, "candidateOrder": candidateOrder})
    line = getLine()

# The lines after the end of ballots marker indicate the candidate names in double quotes. The number of candidate names must match the number indicated on the first line.
candidateNames = []
for i in range(num_candidates):
    candidateNames.append(getLine().strip('"'))

# The line after the candidate names is the title in double quotes.
electionTitle = getLine().strip('"')

def makeBallot(raw):
    # FIXME: weights currently busted    
     return [ candidateNames[n-1] for n in raw["candidateOrder"] ]
#    return {
#                "weight": raw["weight"],
#                "vote": [ candidateNames[n-1] for n in raw["candidateOrder"] ]
#           }


out = {
        "election": {
            "type": "SingleTransferableVote",
            "numPositions": num_seats,
            "candidates": [{"name": name} for name in candidateNames]
         },
        "ballots": [ makeBallot(b) for b in rawBallots ]
      }

print json.dumps(out, indent = 2)
