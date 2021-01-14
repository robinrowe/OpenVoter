package elections

class ElectionResult


case class Candidate(name: String, diversityCategories: Set[String] = Set.empty) {
  override val toString = name
}

abstract class Election[BallotT<:Ballot, ElectionResultT<:ElectionResult] {
  val candidates: Set[Candidate]
  def countBallots(ballots: Seq[BallotT]): ElectionResultT
}
