<?php
require("include/config.php");
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="favicon.ico">

    <title>Layers MSIST - Train</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/bootstrap-slider.min.css">
    <link rel="stylesheet" type="text/css" href="css/codemirror.css">

    <!-- Custom styles for this template -->
    <link rel="stylesheet" type="text/css" href="css/chart.css">

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<script>window.jQuery || document.write('<script src="js/jquery.min.js"><\/script>')</script>

	<script type="text/javascript">
    function RunExperiment(id)
    {
      $.post("services/run-experiment.php", {
        experiment_id: id
      },
      function(result) {
        console.log(result);
        if (result == "ok")
          window.location.href = 'index.php?experiment_id=' + id;
        else
          alert("Error " + result);
      });
    }
    function DeleteExperiment(id)
    {
      $.post("services/delete-experiment.php", {
        experiment_id: id
      },
      function(result) {
        if (result == "ok")
          window.location.href = 'index.php';
        else
          alert("Error " + result);
      });
    }
    function ForkExperiment(id)
    {
      window.location.href = 'new.php?experiment_id=' + id;
    } 
	</script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body style="overflow:auto">

    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">LayersBoard</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="#">Train</a></li>
            <li><a href="#">Test</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>

    <div class="container">

    <div style="height:80px"></div>  