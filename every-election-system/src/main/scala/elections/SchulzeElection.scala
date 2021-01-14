package elections

import scalax.collection.Graph

case class SchulzeElectionResult(preferenceMatrix: PreferenceMatrix, rounds: List[SchulzeEliminationRound]) extends ElectionResult

class RankedSchulzeElection(val candidates: Set[Candidate]) extends SchulzeElection[RankedBallot] {
  def calculatePreferenceMatrix(ballots: Seq[RankedBallot]) = PreferenceMatrix.fromRankedBallots(candidates, ballots)
}

class ScoreSchulzeElection(val candidates: Set[Candidate]) extends SchulzeElection[ScoreBallot] {
  def calculatePreferenceMatrix(ballots: Seq[ScoreBallot]) = PreferenceMatrix.fromScoreBallots(candidates, ballots)
}

trait SchulzeEliminationRound {
  val schwartzSet: Set[Candidate]
  val preferences: Set[Preference[Candidate]]
}
case class CandidateEliminationRound(
                                      schwartzSet: Set[Candidate],
                                      preferences: Set[Preference[Candidate]],
                                      eliminatedCandidates: Set[Candidate]
                                    ) extends SchulzeEliminationRound {
  override def toString: String = "CandidateEliminationRound("+eliminatedCandidates+")"
}

case class PreferenceEliminationRound(
                                       schwartzSet: Set[Candidate],
                                       preferences: Set[Preference[Candidate]],
                                       eliminatedPreferences: Set[Preference[Candidate]]
                                     ) extends SchulzeEliminationRound {
  override def toString: String = "PreferenceEliminationRound("+ eliminatedPreferences +")"
}

abstract class SchulzeElection[BallotT<:Ballot] extends Election[BallotT, SchulzeElectionResult] {
  def countBallots(ballots: Seq[BallotT]): SchulzeElectionResult = {
    val preferenceMatrix = calculatePreferenceMatrix(ballots)
    val preferences = preferenceMatrix.directionalPreferences.toSet
    SchulzeElectionResult(preferenceMatrix, recurseRound(candidates, preferences))
  }

  def recurseRound(remainingCandidates: Set[Candidate], preferences: Set[Preference[Candidate]]): List[SchulzeEliminationRound] = {
    if(preferences.isEmpty) Nil
    else {
      val preferenceGraph: Graph[Candidate, Preference] = Graph.from(remainingCandidates, preferences)
      val schwartzSet = PreferenceMatrix.calculateSchwartzSet(preferenceGraph)

      if(schwartzSet != remainingCandidates) {
        val schwartzFilteredPreferences = preferences.filter(
          p => schwartzSet.contains(p.yes) && schwartzSet.contains(p.no)
        )
        CandidateEliminationRound(
          schwartzSet,
          schwartzFilteredPreferences,
          remainingCandidates -- schwartzSet
        ) :: recurseRound(schwartzSet, schwartzFilteredPreferences)
      } else {
        val minWeight = preferences.map(_.weight).min
        val (newPreferences, eliminatedPreferences) = preferences.partition(_.weight > minWeight)
        PreferenceEliminationRound(schwartzSet, newPreferences, eliminatedPreferences) :: recurseRound(schwartzSet, newPreferences)
      }


    }
  }


  def calculatePreferenceMatrix(ballots: Seq[BallotT]): PreferenceMatrix

}
