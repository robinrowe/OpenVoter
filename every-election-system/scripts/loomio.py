#! /usr/bin/python
import sys
import json

file = open(sys.argv[1])

blob = json.load(file)

candidatevals = []

print "import elections._"

for pollOption in blob['poll_options']:
    name = "option"+str(pollOption['id'])
    print "val "+name+" = Candidate(\"" + pollOption['name'][:14] + "\")"
    candidatevals.append(name)

print "val candidates = Set("+', '.join(candidatevals)+")"

stancesById = {}
for stanceChoice in blob['stance_choices']:
    stanceId = stanceChoice['stance_id']
    stance = stancesById.get(stanceId, {})
    stance[stanceChoice['rank']] = stanceChoice['poll_option_id']
    stancesById[stanceId] = stance

print "val election = new RankedBallotRankedPairsElection(candidates)"

print "val ballots = Set("

for stance in stancesById.values():
    order = [ stance[1], stance[2], stance[3] ]
    print "  new RankedBallot(election, List(" + ', '.join(["option"+str(x) for x in order]) + ")),"

print ")"

print "val result = election.countBallots(ballots)"
print "result.preferenceMatrix.description"
