<?php
include 'dbConnect.php';


        //$sql = "SELECT * FROM `qsl` WHERE `callsign` LIKE 'HB9%' AND (`Mitgliedschaft` = 'Aktiv' OR `Mitgliedschaft` = 'Ehren' OR `Mitgliedschaft` = 'Kollektiv' OR `Mitgliedschaft` = 'Jung' OR `Mitgliedschaft` = 'Sektion') ORDER BY `callsign` ASC";

	// Mitgliedschaft-Filter entfernt: API liefert nur aktive Mitglieder
	$sql = "SELECT qsl.*, zusatz.addr FROM `qsl` LEFT JOIN zusatz ON qsl.callsign = zusatz.callsign WHERE qsl.callsign LIKE 'HB9%' ORDER BY qsl.callsign ASC;";
	$query = $database->prepare($sql);
        $query->execute();

		$result = $query->fetchAll();
		/* echo "<pre>";
		print_r($result);
		echo "</pre>"; */

		// Mitgliedschaft-Filter entfernt: API liefert nur aktive Mitglieder
		$sql2 = "SELECT * FROM `qsl` WHERE `callsign` LIKE 'HB3%' ORDER BY `callsign` ASC";
        $query2 = $database->prepare($sql2);
        $query2->execute();

		$result2 = $query2->fetchAll();


		$sql3 = "SELECT * FROM `qsl` WHERE `callsign` != '' ORDER BY `callsign` ASC";
        $query3 = $database->prepare($sql3);
        $query3->execute();

		$result3 = $query3->fetchAll();



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

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.6/moment.min.js"></script>


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
                <li class="active">
                    <a href="http://localhost">Dashboard</a>
                </li>
                <li>
                    <a href="http://localhost/index_old.php">alte DB</a>
                </li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li>
                    <button class="btn btn-warning navbar-btn" onclick="doDbUpdate()" id="btnDbUpdate">
                        <i class="fa fa-refresh"></i> DB Update
                    </button>
                </li>
            </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
<style>
			/*@media only screen and (min-width: 1025px) {*/
				#Take-Picture, .custom-file-upload {display:none;}
			/*}*/
			#picture {display:none;}
			input[type="file"] {
				display: none;
			}
			.custom-file-upload {
				vertical-align: sub;
				cursor: pointer;
				font-size: 24px;
				padding: 0px 6px;
			}
			#picture {display:none;}
			.marker > .form-group {margin-bottom: 0Px;}

			.printBtn i{
            	position: absolute;
            	left: 15px;
            	top: 40px;
            	color: white;
        	}

</style>

<div class="container">

        <!-- echo out the system feedback (error and success messages) -->
        <div class="row">
        	<div class="col-sm-10">
   			<h2>Dashboard</h2>
        	</div>
		<div class="col-sm-2">
   			<label>Autocut? <input type="checkbox" name="autocut" id="autocut" checked=checked value="1x0" class="form-control" /></label>
        	</div>
	</div>
        <div class="row">
        	<div class="col-sm-1">
                <label>HB9</label>
        	</div>

        	<div class="col-sm-4">
            <form id = "myForm" action = "">

          <select id="addrVal" class="combobox input-large form-control">
            <option value="">Auswählen...</option>

			<?php


				foreach($result as $value) {
					if($value['Nachname'] == "" AND $value['Vorname'] == ""){$anschriftname = utf8_encode($value['Kontakte']);}else{$anschriftname = utf8_encode($value['Vorname']." ".$value['Nachname']);}
					if($value['Strasse'] == ""){$value['Strasse'] = $value['Postfach'];}
					if($value['Strasse'] != "" AND $value['Postfach'] != ""){$value['Strasse'] = $value['Strasse']." / P.O.BOX ".filter_var($value['Postfach'], FILTER_SANITIZE_NUMBER_INT);}
					$addrArr = array(" ", //ADA_KNR
									 $value['callsign'],
									 " ", //$value['ADR_ANR']
									 $anschriftname,
									 utf8_encode($value['Strasse']),
									 utf8_encode(trim($value['PLZ']." ".$value['Ort'])),
									 utf8_encode($value['addr'])
									);
          if($value['via'] == "1") {

            if($value['vStrasse'] == ""){$value['vStrasse'] = $value['vPostfach'];}
  					if($value['vStrasse'] != "" AND $value['vPostfach'] != ""){$value['vStrasse'] = $value['vStrasse']." / P.O.BOX ".filter_var($value['vPostfach'], FILTER_SANITIZE_NUMBER_INT);}
            $addrArr = array(" ", //ADA_KNR
  									 $value['callsign'],
  									 " ", //$value['ADR_ANR']
  									 utf8_encode($value['vName']),
  									 utf8_encode($value['vStrasse']),
  									 utf8_encode(trim($value['vOrt'])),
  									 " "
  									);
          }
					$addrPacket = json_encode($addrArr, JSON_UNESCAPED_UNICODE);


				 ?>
            <option value='<?php echo $addrPacket; ?>'><?php echo substr($value['callsign'],3); ?></option>
			<?php
			}
			  ?>



          </select>
     	  </div>
          <div class="col-sm-1">
			  <div class="printBtn">
			  <input id="btnPrint" class="btn btn-primary form-control" value="druck" onclick="DoPrint('')" />
			  </div>
		  </div>
		  <div class="col-sm-1">
			  <div class="printBtn">
			  <input id="btnPrint" class="btn btn-primary form-control" value="vorschau" onclick="DoPrint('Preview.bmp')" />
			  </div>
		  </div>
		  <div class="col-sm-1"><input id="target-1" class="btn btn-primary form-control" value="Detail" onclick="DoDetail()" /></div>
			<input type="hidden" id="txtWidth" style="size:15;"/>
        </form>
        </div>

			<br>

		<div class="row">
        	<div class="col-sm-1">
                <label>HB3</label>
        	</div>

        	<div class="col-sm-4">
            <form id = "myForm2" action = "">

          <select id="addrVal2" class="combobox input-large form-control">
            <option value="">Auswählen...</option>

			<?php

				foreach($result2 as $value2) {
					if($value2['Name'] == "" AND $value2['Vorname'] == ""){$anschriftname2 = utf8_encode($value2['Kontakte']);}else{$anschriftname2 = utf8_encode($value2['Vorname']." ".$value2['Nachname']);}
					if($value2['Strasse'] == ""){$value2['Strasse'] = $value2['Postfach'];}
					if($value2['Strasse'] != "" AND $value2['Postfach'] != ""){$value2['Strasse'] = $value2['Strasse']." / P.O.BOX ".filter_var($value2['Postfach'], FILTER_SANITIZE_NUMBER_INT);}
					$addrArr2 = array(" ", //ADA_KNR
									 $value2['callsign'],
									 " ", //$value2['ADR_ANR']
									 $anschriftname2,
									 utf8_encode($value2['Strasse']),
									 utf8_encode(trim($value2['PLZ']." ".$value2['Ort']))

									);
	if($value2['via'] == "1") {

            if($value2['vStrasse'] == ""){$value2['vStrasse'] = $value2['vPostfach'];}
  					if($value2['vStrasse'] != "" AND $value2['vPostfach'] != ""){$value2['vStrasse'] = $value2['vStrasse']." / P.O.BOX ".filter_var($value2['vPostfach'], FILTER_SANITIZE_NUMBER_INT);}
            $addrArr2 = array(" ", //ADA_KNR
  									 $value2['callsign'],
  									 " ", //$value2['ADR_ANR']
  									 utf8_encode($value2['vName']),
  									 utf8_encode($value2['vStrasse']),
  									 utf8_encode(trim($value2['vOrt'])),
  									 " "
  									);
          }

					$addrPacket2 = json_encode($addrArr2, JSON_UNESCAPED_UNICODE);


				 ?>
            <option value='<?php echo $addrPacket2; ?>'><?php echo substr($value2['callsign'],3); ?></option>
			<?php
			}
			  ?>



          </select>
     	  </div>
          <div class="col-sm-1">
			  <div class="printBtn2">
			  <input id="btnPrint2" class="btn btn-primary form-control" value="druck" onclick="DoPrint2('')" />
			  </div>
		  </div>
		  <div class="col-sm-1">
			  <div class="printBtn2">
			  <input id="btnPrint2" class="btn btn-primary form-control" value="vorschau" onclick="DoPrint2('Preview.bmp')" />
			  </div>
		  </div>
		<div class="col-sm-1"><input id="target-2" class="btn btn-primary form-control" value="Details" onclick="DoDetail2()" /></div>
			<input type="hidden" id="txtWidth2" style="size:15;"/>
        </form>
        </div>


		<br>

		<div class="row">
        	<div class="col-sm-1">
                <label>ABCXYZ</label>
        	</div>

        	<div class="col-sm-4">
            <form id = "myForm3" action = "">

          <select id="addrVal3" class="combobox input-large form-control">
            <option value="">Auswählen...</option>

			<?php

				foreach($result3 as $value3) {
					if($value3['Name'] == "" AND $value3['Vorname'] == ""){$anschriftname3 = utf8_encode($value3['Kontakte']);}else{$anschriftname3 = utf8_encode($value3['Vorname']." ".$value3['Nachname']);}
					if($value3['Strasse'] == ""){$value3['Strasse'] = $value3['Postfach'];}
					if($value3['Strasse'] != "" AND $value3['Postfach'] != ""){$value['Strasse'] = $value3['Strasse']." / P.O.BOX ".filter_var($value3['Postfach'], FILTER_SANITIZE_NUMBER_INT);}
					$addrArr3 = array(" ", //KNR
									 $value3['callsign'],
									 " ", //$value3['ADR_ANR']
									 $anschriftname3,
									 utf8_encode($value3['Strasse']),

									 utf8_encode(trim($value3['PLZ']." ".$value3['Ort']))
									);
if($value3['via'] == "1") {

            if($value3['vStrasse'] == ""){$value3['vStrasse'] = $value3['vPostfach'];}
  					if($value3['vStrasse'] != "" AND $value3['vPostfach'] != ""){$value3['vStrasse'] = $value3['vStrasse']." / P.O.BOX ".filter_var($value3['vPostfach'], FILTER_SANITIZE_NUMBER_INT);}
            $addrArr3 = array(" ", //ADA_KNR
  									 $value3['callsign'],
  									 " ", //$value3['ADR_ANR']
  									 utf8_encode($value3['vName']),
  									 utf8_encode($value3['vStrasse']),
  									 utf8_encode(trim($value3['vOrt'])),
  									 " "
  									);
          }

					$addrPacket3 = json_encode($addrArr3, JSON_UNESCAPED_UNICODE);


				 ?>
            <option value='<?php echo $addrPacket3; ?>'><?php echo $value3['callsign']; ?></option>
			<?php
			}
			  ?>



          </select>
     	  </div>
          <div class="col-sm-1">
			  <div class="printBtn3">
			  <input id="btnPrint3" class="btn btn-primary form-control" value="druck" onclick="DoPrint3('')" />
			  </div>
		  </div>
		  <div class="col-sm-1">
			  <div class="printBtn3">
			  <input id="btnPrint3" class="btn btn-primary form-control" value="vorschau" onclick="DoPrint3('Preview.bmp')" />
			  </div>
		  </div>
		<div class="col-sm-1"><input id="target-3" class="btn btn-primary form-control" value="Detail" onclick="DoDetail3()" /></div>
			<input type="hidden" id="txtWidth3" style="size:15;"/>
        </form>
        </div>

<br>

		<div class="row">
        	<div class="col-sm-1">
                <label>Freitext</label>
        	</div>

        	<div class="col-sm-4">
            <form id = "myForm4" action = "">
	  <input type="text" id="calls4" name="calls4" class="form-control" placeholder="CallSign" />
          <textarea name="addr4" id="addr4" class="form-control" rows="4" cols"40" placeholder="Anschrift"></textarea>

     	  </div>
          <div class="col-sm-1">
			  <div class="printBtn4">
			  <input id="btnPrint4" class="btn btn-primary form-control" value="druck" onclick="DoPrint4('')" />
			  </div>
		  </div>
		  <div class="col-sm-1">
			  <div class="printBtn4">
			  <input id="btnPrint4" class="btn btn-primary form-control" value="vorschau" onclick="DoPrint4('Preview.bmp')" />
			  </div>
		  </div>
		<input type="hidden" id="txtWidth4" style="size:15;"/>
        </form>
        </div>



	<br>
		<div class="row">
        	<div class="col-sm-12">
				<img id='previewArea' style="border: 1px solid grey;" />
			</div>
		</div>
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

   <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    	<script src="http://localhost/js/bootstrap-combobox.js"></script>
	<script type="text/javascript">
      //<![CDATA[
        $(document).ready(function(){
          $('.combobox').combobox()
        });
      //]]>
    </script>
    <script type="text/javascript">
	  $(".deletable").addClear({
			symbolClass: "fa fa-times-circle",
			top: 10
		});
	 </script>

<script type="module">
    import * as bpac from './bpac.js';
	const DATA_FOLDER = "C:\\QSLTemplates\\";
	// const DATA_FOLDER = "http://localhost/";
    //------------------------------------------------------------------------------
    //   Function name   :   DoPrint
    //   Description     :   Print, Preview Module
    //------------------------------------------------------------------------------
	window.DoPrint = async function DoPrint(strExport)
    {
		if(bpac.IsExtensionInstalled() == false)
		{
			const agent = window.navigator.userAgent.toLowerCase();
			const ischrome = (agent.indexOf('chrome') !== -1) && (agent.indexOf('edge') === -1)  && (agent.indexOf('opr') === -1)
			if(ischrome)
				window.open('https://chrome.google.com/webstore/detail/ilpghlfadkjifilabejhhijpfphfcfhb', '_blank');
			return;
		}

		try{
			const theForm = document.getElementById("myForm");
			const strPath = DATA_FOLDER + "QSL.lbx";
			const objDoc = bpac.IDocument;
			const ret = await objDoc.Open(strPath);
			if(ret == true)
			{


				var addrArr = JSON.parse(theForm.jArray.value);


				const objSex = await objDoc.GetObject("objSex");
				objSex.Text = addrArr[2];
				const objName = await objDoc.GetObject("objName");
				objName.Text = addrArr[3];
				const objAddress = await objDoc.GetObject("objAddress");
				objAddress.Text = addrArr[4];
				const objCity = await objDoc.GetObject("objCity");
				objCity.Text = addrArr[5];
				const objNr = await objDoc.GetObject("objNr");
				objNr.Text = addrArr[0];
				const objCallsign = await objDoc.GetObject("objCallsign");
				objCallsign.Text = addrArr[1];
				theForm.txtWidth.value = await objDoc.Width;

				if(strExport == "")
				{
					const autoCutOpt = document.getElementById('autocut').checked ? 0x1 : 0x0;
					objDoc.StartPrint("", autoCutOpt);
					objDoc.PrintOut(1, 0x10000000);
					objDoc.EndPrint();

                    $('.input-group-addon.dropdown-toggle:eq( 0 )').trigger( "click" );


				}
				else
				{
					const image = await objDoc.GetImageData(4, 0, 100);
					const img = document.getElementById("previewArea");
					img.src = image;


				}

				objDoc.Close();
			}
		}
		catch(e)
		{
            console.log(e);
			alert("nicht gefunden!");
		}
	}
	window.DoPrint2 = async function DoPrint(strExport)
    {
		if(bpac.IsExtensionInstalled() == false)
		{
			const agent = window.navigator.userAgent.toLowerCase();
			const ischrome = (agent.indexOf('chrome') !== -1) && (agent.indexOf('edge') === -1)  && (agent.indexOf('opr') === -1)
			if(ischrome)
				window.open('https://chrome.google.com/webstore/detail/ilpghlfadkjifilabejhhijpfphfcfhb', '_blank');
			return;
		}

		try{
			const theForm2 = document.getElementById("myForm2");
			const strPath2 = DATA_FOLDER + "QSL.lbx";
			const objDoc2 = bpac.IDocument;
			const ret2 = await objDoc2.Open(strPath2);
			if(ret2 == true)
			{


				var addrArr = JSON.parse(theForm2.jArray.value);


				const objSex2 = await objDoc2.GetObject("objSex");
				objSex2.Text = addrArr[2];
				const objName2 = await objDoc2.GetObject("objName");
				objName2.Text = addrArr[3];
				const objAddress2 = await objDoc2.GetObject("objAddress");
				objAddress2.Text = addrArr[4];
				const objCity2 = await objDoc2.GetObject("objCity");
				objCity2.Text = addrArr[5];
				const objNr2 = await objDoc2.GetObject("objNr");
				objNr2.Text = addrArr[0];
				const objCallsign2 = await objDoc2.GetObject("objCallsign");
				objCallsign2.Text = addrArr[1];
				theForm2.txtWidth2.value = await objDoc2.Width;

				if(strExport == "")
				{
					const autoCutOpt2 = document.getElementById('autocut').checked ? 0x1 : 0x0;
					objDoc2.StartPrint("", autoCutOpt2);
					objDoc2.PrintOut(1, 0x10000000);
					objDoc2.EndPrint();
                    $('.input-group-addon.dropdown-toggle:eq( 1 )').trigger( "click" );


				}
				else
				{
					const image = await objDoc2.GetImageData(4, 0, 100);
					const img = document.getElementById("previewArea");
					img.src = image;


				}

				objDoc2.Close();
			}
		}
		catch(e)
		{
            console.log(e);
			alert("nicht gefunden! (2)");
		}
	}

	window.DoPrint3 = async function DoPrint(strExport)
    {
		if(bpac.IsExtensionInstalled() == false)
		{
			const agent = window.navigator.userAgent.toLowerCase();
			const ischrome = (agent.indexOf('chrome') !== -1) && (agent.indexOf('edge') === -1)  && (agent.indexOf('opr') === -1)
			if(ischrome)
				window.open('https://chrome.google.com/webstore/detail/ilpghlfadkjifilabejhhijpfphfcfhb', '_blank');
			return;
		}

		try{
			const theForm3 = document.getElementById("myForm3");
			const strPath3 = DATA_FOLDER + "QSL.lbx";
			const objDoc3 = bpac.IDocument;
			const ret3 = await objDoc3.Open(strPath3);
			if(ret3 == true)
			{


				var addrArr = JSON.parse(theForm3.jArray.value);


				const objSex3 = await objDoc3.GetObject("objSex");
				objSex3.Text = addrArr[2];
				const objName3 = await objDoc3.GetObject("objName");
				objName3.Text = addrArr[3];
				const objAddress3 = await objDoc3.GetObject("objAddress");
				objAddress3.Text = addrArr[4];
				const objCity3 = await objDoc3.GetObject("objCity");
				objCity3.Text = addrArr[5];
				const objNr3 = await objDoc3.GetObject("objNr");
				objNr3.Text = addrArr[0];
				const objCallsign3 = await objDoc3.GetObject("objCallsign");
				objCallsign3.Text = addrArr[1];
				theForm3.txtWidth3.value = await objDoc3.Width;

				if(strExport == "")
				{
					const autoCutOpt3 = document.getElementById('autocut').checked ? 0x1 : 0x0;
					objDoc3.StartPrint("", autoCutOpt3); // 0x1 = autocut, 0x0 = kein cut
					objDoc3.PrintOut(1, 0x10000000);
					objDoc3.EndPrint();
                    $('.input-group-addon.dropdown-toggle:eq( 2 )').trigger( "click" );


				}
				else
				{
					const image = await objDoc3.GetImageData(4, 0, 100);
					const img = document.getElementById("previewArea");
					img.src = image;


				}

				objDoc3.Close();
			}
		}
		catch(e)
		{
            console.log(e);
			alert("nicht gefunden! (3)");
		}
	}

	window.DoPrint4 = async function DoPrint(strExport)
    {
		if(bpac.IsExtensionInstalled() == false)
		{
			const agent = window.navigator.userAgent.toLowerCase();
			const ischrome = (agent.indexOf('chrome') !== -1) && (agent.indexOf('edge') === -1)  && (agent.indexOf('opr') === -1)
			if(ischrome)
				window.open('https://chrome.google.com/webstore/detail/ilpghlfadkjifilabejhhijpfphfcfhb', '_blank');
			return;
		}

		try{
			const theForm4 = document.getElementById("myForm4");
			const strPath4 = DATA_FOLDER + "QSLblank.lbx";
			const objDoc4 = bpac.IDocument;
			const ret4 = await objDoc4.Open(strPath4);
			if(ret4 == true)
			{


				const objQSL4 = await objDoc4.GetObject("objCallsign");
				objQSL4.Text = document.getElementById("calls4").value;
				const objAnschrift4 = await objDoc4.GetObject("objAnschrift");
				objAnschrift4.Text = document.getElementById("addr4").value;
				theForm4.txtWidth4.value = await objDoc4.Width;

				if(strExport == "")
				{
					const autoCutOpt4 = document.getElementById('autocut').checked ? 0x1 : 0x0;
					objDoc4.StartPrint("", autoCutOpt4);
					objDoc4.PrintOut(1, 0x10000000);
					objDoc4.EndPrint();


				}
				else
				{
					const image = await objDoc4.GetImageData(4, 0, 100);
					const img = document.getElementById("previewArea");
					img.src = image;


				}

				objDoc4.Close();
			}
		}
		catch(e)
		{
            console.log(e);
			alert("nicht gefunden! (4)");
		}
	}
	</script>
	<script>
		// Get the input field


// Execute a function when the user releases a key on the keyboard
document.getElementById('myForm').addEventListener("keyup", function(event) {
  // Number 13 is the "Enter" key on the keyboard
  if (event.keyCode === 13) {
    DoPrint('');
  }
});

document.getElementById('myForm2').addEventListener("keyup", function(event) {
  // Number 13 is the "Enter" key on the keyboard
  if (event.keyCode === 13) {
    DoPrint2('');
  }
});

document.getElementById('myForm3').addEventListener("keyup", function(event) {
  // Number 13 is the "Enter" key on the keyboard
  if (event.keyCode === 13) {
    DoPrint3('');
  }
});
	</script>
	<script>
		function DoDetail() {
		  var addrArr = JSON.parse(document.getElementById("myForm").jArray.value);
		  window.open("http://localhost/detail.php?call="+addrArr[1], "_self");
		}
		function DoDetail2() {
		  var addrArr = JSON.parse(document.getElementById("myForm2").jArray.value);
		  window.open("http://localhost/detail.php?call="+addrArr[1], "_self");
		}
		function DoDetail3() {
		  var addrArr = JSON.parse(document.getElementById("myForm3").jArray.value);
		  window.open("http://localhost/detail.php?call="+addrArr[1], "_self");
		}


	</script>

<!-- DB Update Modal -->
<div class="modal fade" id="updateModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"><i class="fa fa-database"></i> DB Update via USKA API</h4>
      </div>
      <div class="modal-body" id="updateResult">
        <i class="fa fa-spinner fa-spin"></i> Update läuft...
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Schliessen</button>
        <button type="button" class="btn btn-default" onclick="location.reload()">Seite neu laden</button>
      </div>
    </div>
  </div>
</div>

<script>
async function doDbUpdate() {
    const btn = document.getElementById('btnDbUpdate');
    btn.disabled = true;
    document.getElementById('updateResult').innerHTML = '<i class="fa fa-spinner fa-spin"></i> Update läuft, bitte warten...';
    $('#updateModal').modal('show');
    try {
        const r = await fetch('update/api_update.php');
        const text = await r.text();
        const ok = r.ok && text.indexOf('erfolgreich') !== -1;
        document.getElementById('updateResult').innerHTML =
            '<pre class="' + (ok ? 'text-success' : 'text-danger') + '">' +
            text.replace(/</g, '&lt;') + '</pre>';
    } catch(e) {
        document.getElementById('updateResult').innerHTML =
            '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i> Fehler: ' + e + '</span>';
    } finally {
        btn.disabled = false;
    }
}
</script>

</body>
</html>
