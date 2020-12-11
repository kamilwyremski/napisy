<?php

$alert_danger = '';

if(!empty($_FILES['plik']) and isset($_POST['przesuniecie'])){
	try {
		if (
			!isset($_FILES['plik']['error']) ||
			is_array($_FILES['plik']['error'])
		) {
			throw new RuntimeException('Nieznany parametr');
		}

		switch ($_FILES['plik']['error']) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				throw new RuntimeException('Nie wysłano pliku');
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				throw new RuntimeException('Przekroczono limit wielkości pliku');
			default:
				throw new RuntimeException('Nieznane błędy');
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		if ($finfo->file($_FILES['plik']['tmp_name']) != 'text/plain'){
			throw new RuntimeException('Nieprawidłowy format pliku');
		}

		$output = '';

		$subtitle_type = '';
		
		$handle = fopen($_FILES['plik']['tmp_name'], "r");
		while (($line = fgets($handle)) !== false) {
			
			if(!$subtitle_type){
				if(preg_match("/^\d\d:\d\d:\d\d:/",$line)) {
					$subtitle_type = 'standard';
				}elseif(preg_match("/^\d\d:\d\d:\d\d,\d\d\d --> \d\d:\d\d:\d\d,\d\d\d$/",$line)) {
					$subtitle_type = 'arrow';
				}
			}
			
			if($subtitle_type=='standard'){
				$time_original = substr($line, 0, 8);
				$seconds = strtotime($time_original);
				$time_new = date('H:i:s',$seconds + $_POST['przesuniecie']);
				$new_line = str_replace($time_original, $time_new, $line);
				$output .= $new_line;
			}elseif($subtitle_type=='arrow'){
				$match_count = preg_match_all('/(\d\d:\d\d:\d\d),\d\d\d --> (\d\d:\d\d:\d\d),\d\d\d/i', $line, $matches);
				if($match_count){
					$time_original = strtotime($matches[1][0]);
					$time_new = date('H:i:s',$time_original + $_POST['przesuniecie']);
					$new_line = str_replace($matches[1][0], $time_new, $line);
					$time_original = strtotime($matches[2][0]);
					$time_new = date('H:i:s',$time_original + $_POST['przesuniecie']);
					$new_line = str_replace($matches[2][0], $time_new, $new_line);
					$output .= $new_line;
				}else{
					$output .= $line;
				}
			}else{
				$output .= $line;
			}
		}
		fclose($handle);

		if(!$subtitle_type){
			throw new RuntimeException('Nieznany format napisów');
		}
		
		header('Content-Type: application/octet-stream');
		header("Content-Transfer-Encoding: Binary"); 
		header("Content-disposition: attachment; filename=\"" . basename($_FILES['plik']['name']) . "\""); 
		echo($output);
		exit();

	} catch (RuntimeException $e) {

		$alert_danger = $e->getMessage();

	}
}
	
?><!doctype html>
<html>
<head>
	<meta charset="UTF-8" />
	<title>Przesuwanie napisów</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css">
</head>
<body>
	<div class="container">
		<h1>Skrypt do przesuwania napisów do filmów w czasie</h1>
		<p>Po prostu załaduj swój plik z napisami i wpisz o ile sekund w czasie (wprzód lub wstecz) chcesz przesunąć napisy</p>
		<?php 
			if($alert_danger){
				echo('<div class="alert alert-danger">'.$alert_danger.'</div>');
			}
		?>
		<div class="row">
			<form action="" method="post" enctype="multipart/form-data" class="col-sm-4">
				<div class="form-group">
					<label for="plik">Plik z napisami</label>
					<input type="file" class="form-control-file" id="plik" name="plik" required accept=".txt,.srt">
				  </div>
				<div class="form-group">
					<label for="przesuniecie">Przesunięcie (w sekundach)</label>
					<input type="number" class="form-control" id="przesuniecie" name="przesuniecie" required step="1">
				 </div>
				<button type="submit" class="btn btn-primary">Wyślij</button>
			</form>
		</div>
	</div>
 </body>
</html>