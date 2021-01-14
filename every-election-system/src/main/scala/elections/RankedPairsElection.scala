package elections

import scalax.collection.Graph

import scala.collection.mutable
import scala.util.Random

class RankedPairsElectionResult(
                                    val preferenceMatrix: PreferenceMatrix,
                                    val strongestPreferences: Seq[Preference[Candidate]],
                                    val lockedPreferences: Graph[Candidate, Preference],
                                    val candidatesByWinDistance: Map[Candidate, Double],
                                    val orderedCandidates: Seq[Candidate],
                                    val tiesByPlace: Map[Int, Set[Candidate]]
                                  ) extends ElectionResult {
  val winner = orderedCandidates.head
}

class RankedBallotRankedPairsElection(
                                       val candidates: Set[Candidate],
                                       reverse: Boolean = false,
                                     ) extends RankedPairsElection[RankedBallot] {
  def calculatePreferenceMatrix(ballots: Seq[RankedBallot]) = PreferenceMatrix.fromRankedBallots(candidates, ballots, reverse)
}

class ScoreBallotRankedPairsElection(
                                      val candidates: Set[Candidate]
                                    ) extends RankedPairsElection[ScoreBallot] {
  def calculatePreferenceMatrix(ballots: Seq[ScoreBallot]) = PreferenceMatrix.fromScoreBallots(candidates, ballots)
}

trait RankedPairsElection[BallotT <: Ballot] extends Election[BallotT, RankedPairsElectionResult] {
  def countBallots(ballots: Seq[BallotT]): RankedPairsElectionResult = {
    val preferenceMatrix = calculatePreferenceMatrix(ballots)
    val strongestPreferences: Seq[Preference[Candidate]] = preferenceMatrix.preferencesLargestFirst

    val acyclicPreferences: List[Preference[Candidate]] =
      strongestPreferences.foldLeft(List.empty[Preference[Candidate]])(
        (soFar: List[Preference[Candidate]], p: Preference[Candidate]) => {
          if(Graph.from(candidates, p :: soFar).isCyclic) {
            soFar
          } else {
            p :: soFar
          }
      })

    val acyclicPreferenceGraph: Graph[Candidate, Preference] = Graph.from[Candidate, Preference](candidates, acyclicPreferences)

    val preliminaryWinner = acyclicPreferenceGraph.get(candidates.head)
      .pathUntil(candidate => !candidate.hasSuccessors).map(_.endNode.value) // Follow the preference graph
      .getOrElse(candidates.head) // Or we started at the winner

    val distanceFromWinningByCandidate = {
      // Candidates are ordered by the distance from the winner on the full preference graph, including ties
      val winnerNode = preferenceMatrix.graphIncludingTies.get(preliminaryWinner)
      candidates.map(candidate => {
        val shortestPathWeight = preferenceMatrix.graphIncludingTies.get(candidate)
          .shortestPathTo(winnerNode).map(_.weight).getOrElse(Double.PositiveInfinity)
        candidate -> shortestPathWeight
      })
    }.toMap

    val candidatesByDistance = {
      val accum: mutable.Map[Double, Set[Candidate]] = mutable.Map.empty.withDefaultValue(Set.empty)
      distanceFromWinningByCandidate.foreach { case (candidate: Candidate, distance: Double) =>
        accum(distance) += candidate
      }
      accum.toMap
    }

    val ties: mutable.Map[Int, Set[Candidate]] = mutable.Map.empty
    var place = 1

    val orderedCandidates = candidatesByDistance.toSeq.sortBy(_._1).flatMap {
      case (distance: Double, candidates: Set[Candidate]) =>
        if(candidates.size > 1) {
          ties(place) = candidates
          place += candidates.size
          Random.shuffle(candidates.toSeq)
        } else {
          place += 1
          candidates
        }
    }

    new RankedPairsElectionResult(
      preferenceMatrix,
      strongestPreferences,
      acyclicPreferenceGraph,
      distanceFromWinningByCandidate,
      orderedCandidates,
      ties.toMap
    )
  }

  def calculatePreferenceMatrix(ballots: Seq[BallotT]): PreferenceMatrix
}