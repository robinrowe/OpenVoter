package elections

class PluralityElectionSpec extends BaseSpec {

  "A PluralityElection" should "have the correct result for a trivial case" in {
    val election = new PluralityElection(Set(alice, bob))

    val ballots = Seq(new SingleVoteBallot(alice))

    val result = election.countBallots(ballots)

    result.numVotesByCandidate(alice) should be (1)
    result.numVotesByCandidate(bob) should be (0)

    result.winner should be (alice)
  }

  it should "count correctly for a slightly less trivial case" in {
    val election = new PluralityElection(Set(alice, bob, carol))

    val ballots = (1 to 5).map(_ => new SingleVoteBallot(alice)) ++
      (1 to 7).map(_ => new SingleVoteBallot(bob)) ++
      (1 to 11).map(_ => new SingleVoteBallot(carol))

    ballots.size should be (5+7+11)

    val result = election.countBallots(ballots)

    result.numVotesByCandidate should be (Map(alice -> 5, bob -> 7, carol -> 11))

    result.numVotesByCandidate(alice) should be (5)
    result.numVotesByCandidate(bob) should be (7)
    result.numVotesByCandidate(carol) should be (11)

    result.winner should be (carol)
  }
}