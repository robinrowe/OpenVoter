package elections
import org.scalactic.TolerantNumerics

class ReweightedRangeVoteElectionSpec extends BaseSpec {
  implicit val closeEnough = TolerantNumerics.tolerantDoubleEquality(0.00000001)

  "A ReweightedRankedVotingElection" should "count correctly for a trivial case" in {
    val candidates = Set(alice, bob)

    val election = new ReweightedRangeVoteElection(candidates, 1)

    val ballots = Seq(new ScoreBallot(Map(alice -> 1, bob -> 0)))

    val result = election.countBallots(ballots)

    result.winners should be (List(alice))
    result.rounds.head.scores should be (Map(alice -> 1, bob -> 0))
  }

  it should "provide proportional representation in a simple case" in {
    val candidates = Set(alice, bob, carol, david)

    val election = new ReweightedRangeVoteElection(candidates, 2)

    val ballots =
        (1 to 10).map(_ => new ScoreBallot(Map(alice -> 10, bob -> 0, carol -> 9, david -> 0))) ++
        (1 to 12).map(_ => new ScoreBallot(Map(alice -> 0, bob -> 10, carol -> 0, david -> 9)))

    val result = election.countBallots(ballots)

    result.rounds.head.scores(alice) should equal (
      (10 * 1.0) + (12 * 0.0)
    ) // 10

    result.rounds.head.scores(bob) should equal (
      (10 * 0.0) + (12 * 1.0)
    ) // 12

    result.rounds.head.scores(carol) should equal (
      (10 * 0.9) + (12 * 0.0)
    ) // 9

    result.rounds.head.scores(david) should equal (
      (10 * 0.0) + (12 * 0.9)
    ) // 10.8 - in pure score, bob & david would win

    result.rounds.head.winner should be (bob)

    // in round 2, Bob's votes get weighted to 1 / (1 + 1) = 1/2

    result.rounds(1).scores(alice) should equal (
      (10 * 1.0) + ((12 * (1.0/2)) * 0.0)
    ) // 10

    result.rounds(1).scores(carol) should equal (
      (10 * 0.9) + ((12 * (1.0/2)) * 0.0)
    ) // 9

    result.rounds(1).scores(david) should equal (
      (10 * 0.0) + ((12 * (1.0/2)) * 0.9)
    ) // 5.4

    result.rounds(1).winner should be (alice)

    result.rounds.size should be (2)

    result.winners should be (List(bob, alice))
  }

}
