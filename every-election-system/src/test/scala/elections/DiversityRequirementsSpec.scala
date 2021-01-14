package elections

class DiversityRequirementsSpec extends BaseSpec {
  val allison = Candidate("Allison", Set("woman"))
  val bill = Candidate("Bill")
  val cynthia = Candidate("Cynthia", Set("woman"))
  val dan = Candidate("Dan")

  "DiversityRequirements" should "filter candidates with a minimum" in {
    val diversityRequirements = new DiversityRequirements(
      minimums = Map("woman" -> 1),
      maximums = Map.empty
    )

    val excludedCandidates = diversityRequirements.excludedCandidates(
      numRemainingPositions = 1,
      alreadyElectedCandidates = Set(bill),
      remainingCandidates = Set(allison, cynthia, dan)
    )

    excludedCandidates should be (Set(dan))
  }

  it should "not filter candidates if not necessary to meet the minimum" in {
    val diversityRequirements = new DiversityRequirements(
      minimums = Map("woman" -> 1),
      maximums = Map.empty
    )

    val excludedCandidates = diversityRequirements.excludedCandidates(
      numRemainingPositions = 2,
      alreadyElectedCandidates = Set(bill),
      remainingCandidates = Set(allison, cynthia, dan)
    )

    excludedCandidates should be (Set.empty)

  }

  it should "filter candidates with a maximum" in {
    val diversityRequirements = new DiversityRequirements(
      minimums = Map.empty,
      maximums = Map("woman" -> 1)
    )

    val excludedCandidates = diversityRequirements.excludedCandidates(
      numRemainingPositions = 1,
      alreadyElectedCandidates = Set(allison),
      remainingCandidates = Set(bill, cynthia, dan)
    )

    excludedCandidates should be (Set(cynthia))
  }

  it should "filter not filter candidates if not necessary for a maximum" in {
    val diversityRequirements = new DiversityRequirements(
      minimums = Map.empty,
      maximums = Map("woman" -> 2)
    )

    val excludedCandidates = diversityRequirements.excludedCandidates(
      numRemainingPositions = 1,
      alreadyElectedCandidates = Set(allison),
      remainingCandidates = Set(bill, cynthia, dan)
    )

    excludedCandidates should be (Set.empty)
  }

  it should "filter candidates with both minimum and maximum" in {
    val andre = Candidate("Andre 3000", Set("man", "poc"))
    val beyonce = Candidate("Beyonce", Set("woman", "poc"))
    val cher = Candidate("Cher", Set("woman"))
    val dylan = Candidate("Bob Dylan", Set("man"))

    val diversityRequirements = new DiversityRequirements(
      minimums = Map("poc" -> 1),
      maximums = Map("man" -> 1)
    )

    diversityRequirements.excludedCandidates(
      numRemainingPositions = 1,
      alreadyElectedCandidates = Set(cher),
      remainingCandidates = Set(andre, beyonce, dylan)
    ) should be ( Set(dylan) )

    diversityRequirements.excludedCandidates(
      numRemainingPositions = 1,
      alreadyElectedCandidates = Set(cher, dylan),
      remainingCandidates = Set(andre, beyonce)
    ) should be ( Set(andre) )

    diversityRequirements.excludedCandidates(
      numRemainingPositions = 3,
      alreadyElectedCandidates = Set(andre),
      remainingCandidates = Set(beyonce, cher, dylan)
    ) should be ( Set(dylan) )

    diversityRequirements.excludedCandidates(
      numRemainingPositions = 1,
      alreadyElectedCandidates = Set(beyonce),
      remainingCandidates = Set(andre, cher, dylan)
    ) should be ( Set.empty )
  }

  it should "filter candidates that don't immediately break requirements but prevent them from being filled" in {
    val andre = Candidate("Andre 3000", Set("man", "poc"))
    val beyonce = Candidate("Beyonce", Set("poc"))
    val cher = Candidate("Cher", Set())
    val dylan = Candidate("Bob Dylan", Set("man"))
    val elton = Candidate("Elton John", Set("man"))
    val fiona = Candidate("Fiona Apple", Set())

    val diversityRequirements = new DiversityRequirements(
      minimums = Map("poc" -> 2),
      maximums = Map("man" -> 2)
    )

    diversityRequirements.excludedCandidates(
      3,
      Set(elton),
      Set(andre, beyonce, cher, dylan, fiona)
    ) should be ( Set(dylan) )

  }
}