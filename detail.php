<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
include 'dbConnect.php';
include 'functions.php';

$call = sanitize_callsign($_GET["call"] ?? '');
if ($call === null) {
    http_response_code(400);
    exit('Ungültiges Rufzeichen.');
}

        $sql = "SELECT * FROM `qsl` WHERE `callsign` = :call";
        $query = $database->prepare($sql);
        $query->execute([':call' => $call]);

		$result = $query->fetch();
        if ($result === false) {
            http_response_code(404);
            exit('Rufzeichen nicht gefunden.');
        }

        $sql3 = "SELECT * FROM `counter` WHERE `callname` = :call";
        $query3 = $database->prepare($sql3);
        $query3->execute([':call' => $call]);

		$result3 = $query3->fetch();

?>

<!doctype html>
<html>
<head>
    <title>QSL App</title>
    <!-- META -->
    <meta charset="utf-8">
    <meta name="author" content="KDT-Solutions GmbH">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <link rel="apple-touch-icon" href="http://localhost/img/apple-touch-icon.png">
    <!-- send empty favicon fallback to prevent user's browser hitting the server for lots of favicon requests resulting in 404s -->
    <link rel="icon" href="http://localhost/img/favicon.png" type="image/x-icon">


    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <link rel="stylesheet" href="http://localhost/css/ie10-viewport-bug-workaround.css" />
    <link rel="stylesheet" href="http://localhost/css/font-awesome.min.css">
    <link rel="stylesheet" href="http://localhost/css/style.css" />
    <link rel="stylesheet" type="text/css" href="http://localhost/css/bootstrap-combobox.css"/>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js" integrity="sha384-6ePHh72Rl3hKio4HiJ841psfsRJveeS+aLoaEf3BWfS+gTF0XdAqku2ka8VddikM" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js" integrity="sha384-pPttEvTHTuUJ9L2kCoMnNqCRcaMPMVMsWVO+RLaaaYDmfSP5//dP6eKRusbPcqhZ" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.6/moment.min.js" integrity="sha384-v7eExOYhwaHa3+GhP+lHytJsMcidazNdjiaggRhdbvVTVTCjweLpa23t37ZKxaCf" crossorigin="anonymous"></script>


    <script src="http://localhost/js/bootstrap-add-clear.min.js"></script>
	<script src="https://kit.fontawesome.com/e41476fa6b.js" crossorigin="anonymous"></script>


    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->



</head>
<body>
	<!-- Fixed navbar -->
    <nav class="navbar navbar-default navbar-fixed-top navbar-inverse">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="http://localhost">QSL App</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
        	 <ul class="nav navbar-nav">
                                                    <li>
                        <a href="http://localhost">Dashboard</a>
                    </li>



            </ul>

        </div><!--/.nav-collapse -->
      </div>
    </nav>


<div class="container">

        <!-- echo out the system feedback (error and success messages) -->
        	<div class="row">
        	<div class="col-sm-4">
		        <h2>Detail <?php echo e($result['callsign']); ?></h2>
		</div>
		<div class="col-sm-4" style="margin-top: 25px; display:none;">
			<span>(<?php echo e(read_counter($call)); ?> Etiketten gedruckt)</span>
		</div>
		</div>
		<div class="row">
        	<div class="col-sm-4">
				<h3>Adresse</h3>
				<?php if($result['Name'] == "" AND $result['Vorname'] == ""){echo e(utf8_encode($result['Kontakte']));}else{echo e(utf8_encode($result['Vorname']." ".$result['Nachname']));} ?><br>
				<?php if($result['Strasse'] != ""){echo e(utf8_encode($result['Strasse']))."<br>";} ?>
				<?php if($result['Postfach'] != ""){echo e($result['Postfach'])."<br>";} ?>
				<?php echo e(utf8_encode($result['PLZ']." ".$result['Ort'])); ?><br>
        	</div>
			<div class="col-sm-4">
				<h3>Kontakt</h3>
				<?php if(!empty($result['Handy'])){echo "Natel: ".e($result['Handy'])."<br>";}; ?>
				<?php if(!empty($result['ADR_TEG'])){echo "Tel G: ".e($result['ADR_TEG'])."<br>";}; ?>
				<?php if(!empty($result['ADR_TEP'])){echo "Tel P: ".e($result['ADR_TEP'])."<br>";}; ?>
				<?php $emails = str_replace(";",",",$result['Email']); ?>
				<?php echo implode("<br>",array_map('e',explode(",",$emails))); ?><br>
        	</div>
			<div class="col-sm-4">
				<h3>Infos</h3>
				Mitgliedschaft: <?php echo e($result['Mitgliedschaft']); ?><br>
				<?php if(!empty($result['Geburtsdatum'])){echo "Geburtsdatum: ".e(date("d.m.Y", strtotime($result['Geburtsdatum'])))."<br>";}; ?>
				<?php if(!empty($result['Beruf'])){echo "Beruf: ".e(utf8_encode($result['Beruf']))."<br>";}; ?>
                <?php if(!empty($result['USK_EINTR'])){echo "USKA Eintritt: ".e(date("d.m.Y", strtotime($result['Eintritt'])))."<br>";}; ?>
				<?php if(!empty($result['USK_AUSTR'])){echo "USKA Austritt: ".e(date("d.m.Y", strtotime($result['Austritt'])))."<br>";}; ?>
				<?php if(!empty($result['Austrittsgrund'])){echo "Austrittsgrund: ".e($result['Austrittsgrund']);}; ?>


        	</div>
        </div>

		<div class="row">
			<div class="col-sm-4">
				<h3>Via</h3>
				<?php if(!empty($result['vName'])){echo e(utf8_encode($result['vName']))."<br>";}; ?>
				<?php if(!empty($result['vStrasse'])){echo e(utf8_encode($result['vStrasse']))."<br>";}; ?>
				<?php if(!empty($result['vPostfach'])){echo e(utf8_encode($result['vPostfach']))."<br>";}; ?>
				<?php if(!empty($result['vOrt'])){echo e(utf8_encode($result['vOrt']))."<br>";}; ?>
				<?php if(!empty($result['vLand'])){echo e(utf8_encode($result['vLand']))."<br>";}; ?>

				<?php if(!empty($result['vBemerk'])){echo "<br><br>".e(utf8_encode($result['vBemerk']))."<br>";}; ?>

			</div>
		</div>
<?php

$sql2 = "SELECT Code, Bedeutung FROM `T_CODE01` WHERE `Code` = :code";
        $query2 = $database->prepare($sql2);
        $query2->execute([':code' => $result['ADR_GRU'] ?? '']);

		$result2 = $query2->fetch();

	$katarray = array(
				"leer",
				"Verein / Club / HB4",
				"Firma",
				"OM's HB3 / HB9",
				"Vorstand",
				"MitarbeiterIn",
				"Austritt / Streichung",
				"YL's HE9 / HB3 / HB9",
				"OM's HE9",
				"Schule / UNI mit Call",
				"Verstorben",
				"USKA Sektion",
				"Ausland",
				"Liechtenstein HB0 / HE0",
				"Serienbriefe",
				"Kein Call",
				"Reserve",
				"HB-Relais",
				"QSL-Adressen",
				"Reserve"
			);



?>


</div>


    <footer class="footer">
      <div class="container">
      	<div class="row">
        		<div class="col-sm-6">

                </div>
                <div class="col-sm-6 mob-up">
                	<p class="right text-muted ft-r">QSL App created by <a href="https://kdt-solutions.ch" target="_blank">KDT-Solutions GmbH</a></p>
                </div>
            </div>
      </div>
    </footer>



</body>
</html>
