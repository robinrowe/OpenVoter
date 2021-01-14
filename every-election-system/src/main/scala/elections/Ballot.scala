package elections

import java.util.UUID

abstract class Ballot {
  final val id = UUID.randomUUID()
  def validate(candidates: Set[Candidate]): Boolean

  final override def equals(other: Any) = other.isInstanceOf[Ballot] && other.asInstanceOf[Ballot].id == id
  final override lazy val hashCode = id.hashCode()
}

class ScoreBallot( val scores: Map[Candidate, BigDecimal] ) extends Ballot {
  def validate(candidates: Set[Candidate]): Boolean = {
    scores.keySet.forall(candidate => candidates.contains(candidate)) &&
    scores.values.forall(score => score >= 0)
  }

  lazy val normalizedScores: Map[Candidate, BigDecimal] = {
    val maxScore = scores.values.max
    scores.mapValues(score => score / maxScore)
  }
}

class RankedBallot(val ranking: Seq[Candidate]) extends Ballot {
  def validate(candidates: Set[Candidate]): Boolean = {
    ranking.toSet.size == ranking.size &&
      ranking.nonEmpty &&
      ranking.forall(candidate => candidates.contains(candidate))
  }
}

class ApprovalBallot( val approvals: Map[Candidate, Boolean] ) extends Ballot {
  def validate(candidates: Set[Candidate]): Boolean = {
    approvals.keySet.forall(candidate => candidates.contains(candidate))
  }
}

class SingleVoteBallot( val vote: Candidate ) extends Ballot {
  def validate(candidates: Set[Candidate]): Boolean = {
    candidates.contains(vote)
  }
}

case class WeightedRankedBallot(originalBallot: RankedBallot, weight: BigDecimal, remainingVote: Seq[Candidate])
case class WeightedScoreBallot(ballot: ScoreBallot, weight: BigDecimal)

