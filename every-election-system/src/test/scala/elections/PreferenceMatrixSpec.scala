package elections

class PreferenceMatrixSpec extends BaseSpec {
  "A PreferenceMatrix" should "tabulate a single ranked ballot correctly" in {
    val candidates = Set(alice, bob, carol)
    val election = new RankedBallotRankedPairsElection(candidates)

    val ballots = Seq(new RankedBallot(List(alice, bob, carol)))

    val preferenceMatrix = PreferenceMatrix.fromRankedBallots(candidates, ballots)

    preferenceMatrix.pairwisePreferences((alice, bob)) should be (1)
    preferenceMatrix.pairwisePreferences((alice, carol)) should be (1)
    preferenceMatrix.pairwisePreferences((bob, carol)) should be (1)
    preferenceMatrix.pairwisePreferences((carol, alice)) should be (0)
    preferenceMatrix.pairwisePreferences((carol, bob)) should be (0)
    preferenceMatrix.pairwisePreferences((bob, alice)) should be (0)

    preferenceMatrix.directionalPreferences.toSet should be (Set(
        Preference(alice, bob, 1),
        Preference(alice, carol, 1),
        Preference(bob, carol, 1)
      ))
  }

  it should "record a candidate that is ranked as preferred to candidates that are not ranked" in {
    val candidates = Set(alice, bob, carol)

    val ballots = Seq(
      new RankedBallot(List(alice))
    )

    val preferenceMatrix = PreferenceMatrix.fromRankedBallots(candidates, ballots)

    preferenceMatrix.pairwisePreferences((alice, bob)) should be (1)
    preferenceMatrix.pairwisePreferences((alice, carol)) should be (1)
    preferenceMatrix.pairwisePreferences((bob, carol)) should be (0)
    preferenceMatrix.pairwisePreferences((carol, alice)) should be (0)
    preferenceMatrix.pairwisePreferences((carol, bob)) should be (0)
    preferenceMatrix.pairwisePreferences((bob, alice)) should be (0)
  }

  it should "tabulate a small set of ranked ballots correctly" in {
    val candidates = Set(alice, bob, carol)

    val ballots = Seq(
      new RankedBallot(List(alice, bob, carol)),
      new RankedBallot(List(bob, alice, carol)),
      new RankedBallot(List(alice, carol, bob)),
      new RankedBallot(List(alice, carol, bob))
    )

    val preferenceMatrix = PreferenceMatrix.fromRankedBallots(candidates, ballots)

    preferenceMatrix.pairwisePreferences((alice, bob)) should be (3)
    preferenceMatrix.pairwisePreferences((alice, carol)) should be (4)
    preferenceMatrix.pairwisePreferences((bob, carol)) should be (2)
    preferenceMatrix.pairwisePreferences((carol, alice)) should be (0)
    preferenceMatrix.pairwisePreferences((carol, bob)) should be (2)
    preferenceMatrix.pairwisePreferences((bob, alice)) should be (1)

    preferenceMatrix.preferencesLargestFirst.toList should be (List(
        Preference(alice, carol, 4),
        Preference(alice, bob, 2)
      ))
  }

  it should "tabulate a single score ballot correctly" in {
    val candidates = Set(alice, bob, carol, david)
    val election = new ScoreBallotRankedPairsElection(candidates)

    val ballots = Seq(
      new ScoreBallot(Map(alice -> 0, bob -> 2, carol -> 2))
    )

    val preferenceMatrix = PreferenceMatrix.fromScoreBallots(candidates, ballots)

    preferenceMatrix.pairwisePreferences((alice, bob)) should be (0)
    preferenceMatrix.pairwisePreferences((alice, carol)) should be (0)
    preferenceMatrix.pairwisePreferences((alice, david)) should be (0)
    preferenceMatrix.pairwisePreferences((bob, alice)) should be (1)
    preferenceMatrix.pairwisePreferences((bob, carol)) should be (0)
    preferenceMatrix.pairwisePreferences((bob, david)) should be (0)
    preferenceMatrix.pairwisePreferences((carol, alice)) should be (1)
    preferenceMatrix.pairwisePreferences((carol, bob)) should be (0)
    preferenceMatrix.pairwisePreferences((carol, david)) should be (0)
    preferenceMatrix.pairwisePreferences((david, alice)) should be (0)
    preferenceMatrix.pairwisePreferences((david, bob)) should be (0)
    preferenceMatrix.pairwisePreferences((david, carol)) should be (0)

    preferenceMatrix.directionalPreferences.toSet should be (Set(
      Preference(bob, alice, 1),
      Preference(carol, alice, 1)
    ))
  }

  it should "tabulate a small set of score ballots correctly" in {
    val candidates = Set(alice, bob, carol, david)
    val election = new ScoreBallotRankedPairsElection(candidates)

    val ballots = Seq(
      new ScoreBallot(Map(alice -> 0, bob -> 2, carol -> 2)),
      new ScoreBallot(Map(alice -> 1, bob -> 2, carol -> 10, david -> 5))
    )

    val preferenceMatrix = PreferenceMatrix.fromScoreBallots(candidates, ballots)

    preferenceMatrix.pairwisePreferences((alice, bob)) should be (0)
    preferenceMatrix.pairwisePreferences((alice, carol)) should be (0)
    preferenceMatrix.pairwisePreferences((alice, david)) should be (0)
    preferenceMatrix.pairwisePreferences((bob, alice)) should be (1 + 0.1)
    preferenceMatrix.pairwisePreferences((bob, carol)) should be (0)
    preferenceMatrix.pairwisePreferences((bob, david)) should be (0)
    preferenceMatrix.pairwisePreferences((carol, alice)) should be (1 + 0.9)
    preferenceMatrix.pairwisePreferences((carol, bob)) should be (0.8)
    preferenceMatrix.pairwisePreferences((carol, david)) should be (0.5)
    preferenceMatrix.pairwisePreferences((david, alice)) should be (0.4)
    preferenceMatrix.pairwisePreferences((david, bob)) should be (0.3)
    preferenceMatrix.pairwisePreferences((david, carol)) should be (0)

    preferenceMatrix.preferencesSmallestFirst.toList should be (List(
      Preference(david, bob, 0.3),
      Preference(david, alice, 0.4),
      Preference(carol, david, 0.5),
      Preference(carol, bob, 0.8),
      Preference(bob, alice, 1.1),
      Preference(carol, alice, 1.9)
    ))
  }

}
