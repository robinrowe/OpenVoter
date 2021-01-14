package elections

import play.api.libs.json._
import play.api.libs.json.Reads._
import play.api.libs.functional.syntax._

import scala.math.BigDecimal.RoundingMode
  /*
   election:
     type: String/Enum
     candidates: [ {name: String, diversityCategories: [cat1,...] }, ... ]
     diversityRequirements: { minimums: { cat1: Int, ... }, maximums: {cat2: Int} } (defaults to none)
     numPositions: Int(defaults to 1)
     quota: "Droop"|"Hare" (Only for STV, defaults to Droop)
   ballots:
     (Ranked: [candidateName1,...])
     (Score: {candidateId1: value, ...})
   */
object Serialization {
    implicit val candidateReader: Reads[Candidate] = (
      (__ \ "name").read[String] and
      (__ \ "diversityCategories").readWithDefault[Set[String]](Set.empty)
    )((name, diversityCategories) => Candidate(name, diversityCategories))

    implicit val diversityRequirementsReader: Reads[DiversityRequirements] = (
      (__ \ "minimums").readWithDefault[Map[String, Int]](Map.empty) and
      (__ \ "maximums").readWithDefault[Map[String, Int]](Map.empty)
    )((minimums, maximums) => DiversityRequirements(minimums, maximums))

    implicit val stvElectionReader: Reads[SingleTransferableVoteElection] = (
        (__ \ "candidates").read[Set[Candidate]] and
        (__ \ "quota").readWithDefault("droop") and
        (__ \ "numPositions").readWithDefault(1) and
        (__ \ "diversityRequirements").readWithDefault(DiversityRequirements.none)
      )((candidates: Set[Candidate], quotaStr: String, numPositions: Int, diversityRequirements: DiversityRequirements) => {
      val quota = quotaStr.toLowerCase match {
        case "hare" => SingleTransferableVoteElection.HareQuota
        case "droop" => SingleTransferableVoteElection.DroopQuota
        case _ => ???
      }
      new SingleTransferableVoteElection(candidates, numPositions, quota, diversityRequirements)
    })

    implicit val rrvElectionReader: Reads[ReweightedRangeVoteElection] = (
      (__ \ "candidates").read[Set[Candidate]] and
      (__ \ "numPositions").readWithDefault(1) and
      (__ \ "weightConstant").readWithDefault(ReweightedRangeVoteElection.defaultWeightConstant) and
      (__ \ "diversityRequirements").readWithDefault[DiversityRequirements](DiversityRequirements.none)
    )((candidates: Set[Candidate], numPositions: Int, weightConstant: BigDecimal, diversityRequirements: DiversityRequirements) => {
      new ReweightedRangeVoteElection(
        candidates = candidates,
        numPositions = numPositions,
        weightConstant = weightConstant,
        diversityRequirements = diversityRequirements
      )
    })

    trait RunElection {
      val election: Election[_, _]
      val ballots: Seq[Ballot]
    }

    case class RunSTVElection(election: SingleTransferableVoteElection, ballots: Seq[RankedBallot]) extends RunElection
    case class RunRRVElection(election: ReweightedRangeVoteElection, ballots: Seq[ScoreBallot]) extends RunElection

    implicit val runElectionFormat: Format[RunElection] = new Format[RunElection] {
      override def writes(o: RunElection): JsValue = ???

      override def reads(json: JsValue): JsResult[RunElection] = {
        val maybeElection = (json \ "election").toOption.map(readElection)
        maybeElection match {
          case Some(election:SingleTransferableVoteElection) => {
            (json \ "ballots").toOption
              .map(ballotsJson => readRankedBallots(election.candidates, ballotsJson))
              .map(ballots => JsSuccess(RunSTVElection(election, ballots)))
              .getOrElse(JsError("ballots"))
          }
          case Some(election:ReweightedRangeVoteElection) => {
            (json \ "ballots").toOption
              .map(ballotsJson => readScoreBallots(election.candidates, ballotsJson))
              .map(ballots => JsSuccess(RunRRVElection(election, ballots)))
              .getOrElse(JsError("ballots"))
          }
          case _ => JsError("election")
        }
      }
    }

    def readElection(jsonStr: String): Election[_ <: Ballot, _ <: ElectionResult] = readElection(Json.parse(jsonStr))

    def readElection(json: JsValue): Election[_ <: Ballot, _ <: ElectionResult] = {
      (json \ "type").get match {
        case JsString("SingleTransferableVote") => {
          json.validate[SingleTransferableVoteElection] match {
            case JsSuccess(value, _) => value
            case JsError(errors) => throw new Exception(errors.toString())
          }
        }
        case JsString("ReweightedRangeVote") => json.validate[ReweightedRangeVoteElection] match {
          case JsSuccess(value, _) => value
          case JsError(errors) => throw new Exception(errors.toString())
        }
        case _ => throw new Exception("election type")
      }
    }

    def readRankedBallots(candidates: Set[Candidate], value: JsValue): Seq[RankedBallot] = {
      val candidatesByName = candidates.map(c => c.name -> c).toMap
      value match {
        case JsArray(ballots) => ballots.map {
          case JsArray(candidateNames) =>
            val candidateList = candidateNames.map {
              case JsString(name) => candidatesByName(name)
              case _ => throw new Exception("malformed ballot entry")
            }
            new RankedBallot(candidateList)
          case _ => throw new Exception("malformed ballot")
        }
        case _ => throw new Exception("malformed ballot list")
      }
    }

    def readScoreBallots(candidates: Set[Candidate], value: JsValue): Seq[ScoreBallot] = {
      val candidatesByName = candidates.map(c => c.name -> c).toMap
      value match {
        case JsArray(ballots) => ballots.map {
          case JsObject(scores) => new ScoreBallot(scores.map {
            case (name: String, JsNumber(score)) => candidatesByName(name) -> score
            case _ => throw new Exception("malformed ballot entry")
          }.toMap)
          case _ => throw new Exception("malformed ballot")
        }
        case _ => throw new Exception("malformed ballot list")
      }
    }

    private implicit val writesCandidate: Writes[Candidate] = Writes[Candidate]((c: Candidate) => JsString(c.name))
    private implicit val writesCandidateBigDecimalMap: Writes[Map[Candidate, BigDecimal]] =
      Writes[Map[Candidate, BigDecimal]](m =>
        JsObject(m.toSeq.sortBy(_._1.name).map { case (c: Candidate, n: BigDecimal) =>
            c.name -> JsNumber(n.setScale(3, RoundingMode.HALF_UP))
        })
      )

    private implicit val writesTieBreakResult: Writes[TieBreakResult] = Writes[TieBreakResult] {
      case r: RankedPairsTieBreakResult => JsObject(Seq(
        "loser" -> Json.toJson(r.loser),
        "rankedPairsResult" -> Json.toJson(r.electionResult)
      ))
      case r => JsObject(Seq(
        "loser" -> Json.toJson(r.loser),
        "details" -> JsString(r.toString)
      ))
    }

    implicit val stvRoundResultWriter: Writes[STVRoundResult] = (
      (__ \ "firstPlaceVotes").write[Map[Candidate, BigDecimal]] and
      (__ \ "winners").write[Seq[Candidate]] and
      (__ \ "losers").write[Seq[Candidate]] and
      (__ \ "diversityProtected").write[Seq[Candidate]] and
      (__ \ "diversityExcluded").write[Seq[Candidate]] and
      (__ \ "exhaustedBallotWeight").write[BigDecimal] and
        (__ \ "tieBreaker").writeNullable[TieBreakResult]
    )(round => {
      val winners = round.winners.toSeq.sortBy(_.name)
      val losers = round.loser.toSeq.sortBy(_.name)
      val diversityProtected = round.diversityProtected.toSeq
      val diversityExcluded = round.diversityExcluded.toSeq
      (round.firstPlaceVotes, winners, losers, diversityProtected, diversityExcluded,
        round.exhaustedBallotWeight.setScale(3, RoundingMode.HALF_UP),
        round.tieBreakResult
      )
    })

    implicit val stvElectionResultWriter: Writes[SingleTransferableVoteElectionResult] = (
      (__ \ "winners").write[Seq[Candidate]] and
      (__ \ "quota").write[BigDecimal] and
      (__ \ "rounds").write[Seq[STVRoundResult]]
    )(result => (result.winners, result.quota, result.rounds))

    implicit val rrvElectionRoundResultWriter: Writes[RRVElectionRoundResult] = (
      (__ \ "winner").write[Candidate] and
      (__ \ "scores").write[Map[String, BigDecimal]] and
      (__ \ "diversityExcluded").write[Seq[Candidate]]
    )(round => {
      val scores = round.scores.map { case (c, v) => c.name -> v.setScale(3, RoundingMode.HALF_UP) }
      val diversityExcluded = round.diversityExcluded.toSeq
      (round.winner, scores, diversityExcluded)
    })

    implicit val rrvElectionResultWriter: Writes[RRVElectionResult] = (
      (__ \ "winners").write[Seq[Candidate]] and
      (__ \ "rounds").write[Seq[RRVElectionRoundResult]]
    )(result => (result.winners, result.rounds))

    implicit val preferenceWriter: Writes[Preference[Candidate]] = (
      (__ \ "over").write[Candidate] and
        (__ \ "under").write[Candidate] and
        (__ \ "strength").write[BigDecimal]
    )(pref => (pref.yes, pref.no, BigDecimal(pref.strength).setScale(3, RoundingMode.HALF_UP)))

    implicit val preferenceMatrixWriter: Writes[PreferenceMatrix] = (
        (__ \ "summed").write[Seq[Preference[Candidate]]] and
          (__ \ "all").write[Seq[Preference[Candidate]]]
    )(pm => {
      (
        pm.nonNegativePreferences.toSeq.sortBy(p => (p.yes.name, p.no.name)),
        pm.allPreferences.toSeq.sortBy(p => (p.yes.name, p.no.name))
      )
    })

    private case class RankedPairsTie(place: Int, candidates: Set[Candidate])
    private implicit val writesRankedPairsTie: Writes[RankedPairsTie] = (
      (__ \ "place").write[Int] and
        (__ \ "candidates").write[Seq[Candidate]]
    )(rpt => (rpt.place, rpt.candidates.toSeq.sortBy(_.name)))

    implicit val rankedPairsElectionResultWriter: Writes[RankedPairsElectionResult] = (
      (__ \ "candidateOrder").write[Seq[Candidate]] and
        (__ \ "winDistance").write[Map[Candidate, BigDecimal]] and
        (__ \ "ties").write[Seq[RankedPairsTie]] and
        (__ \ "preferences").write[PreferenceMatrix]
    )(result => (
      result.orderedCandidates,
      result.candidatesByWinDistance.mapValues(BigDecimal(_)),
      result.tiesByPlace.toSeq.map { case (place, candidates) => RankedPairsTie(place, candidates) },
      result.preferenceMatrix
    ))
}
