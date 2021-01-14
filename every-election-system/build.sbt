val scalagraph = "org.scala-graph" %% "graph-core" % "1.12.4"
val scalatest = "org.scalatest" %% "scalatest" % "3.0.5" % "test"
val playjson = "com.typesafe.play" %% "play-json" % "2.7.2"
val akkahttp = "com.typesafe.akka" %% "akka-http" % "10.1.8"
val akkastream = "com.typesafe.akka" %% "akka-stream" % "2.5.19"
val akkahttpjson = "de.heikoseeberger" %% "akka-http-play-json" % "1.25.2"

mainClass in (Compile, run) := Some("elections.Application")

lazy val root = (project in file("."))
  .settings(
     name := "every-election-system",
     scalaVersion := "2.12.8",
     libraryDependencies += scalagraph,
     libraryDependencies += scalatest,
     libraryDependencies += playjson,
     libraryDependencies += akkahttp,
     libraryDependencies += akkastream,
     libraryDependencies += akkahttpjson,
     logBuffered in Test := false
  )
