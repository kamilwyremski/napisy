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
				}elseif(preg_match("/^\d\d:\d\d:\d\d,\d\d\d --> \d\d:\d\d:\d\d,\d\d\d/",$line)) {
					$subtitle_type = 'arrow';
				}elseif(preg_match("/^\[(\d+)\]\[(\d+)\](.*)/",$line)) {
					$subtitle_type = 'square_bracket';
				}elseif(preg_match("/^\{(\d+)\}\{(\d+)\}(.*)/",$line)) {
					$subtitle_type = 'brace';
				}
			}
			
			if($subtitle_type=='standard'){
				$time_original = substr($line, 0, 8);
				$seconds = strtotime($time_original);
				$time_new = date('H:i:s',$seconds + round($_POST['przesuniecie']));
				$new_line = str_replace($time_original, $time_new, $line);
				$output .= $new_line;
			}elseif($subtitle_type=='arrow'){
				$match_count = preg_match_all('/(\d\d:\d\d:\d\d,\d\d\d)( --> )(\d\d:\d\d:\d\d,\d\d\d)/i', $line, $matches);
				if($match_count){
					$from = DateTime::createFromFormat('H:i:s,u', $matches[1][0]);
					$from->modify("+".($_POST['przesuniecie']*1000)." ms"); 

					$to = DateTime::createFromFormat('H:i:s,u', $matches[3][0]);
					$to->modify("+".($_POST['przesuniecie']*1000)." ms");

					$output .= $from->format("H:i:s.v").$matches[2][0].$to->format("H:i:s.v")."\n";

				}else{
					$output .= $line;
				}
			}elseif($subtitle_type=='square_bracket'){
				$match_count = preg_match_all('/^\[(\d+)\]\[(\d+)\](.*)/i', $line, $matches);
				if($match_count){
					$output .= '['.($matches[1][0] + round($_POST['przesuniecie'])).']['.($matches[2][0] + round($_POST['przesuniecie'])).']'.$matches[3][0]."\n";
				}else{
					$output .= $line;
				}
			}elseif($subtitle_type=='brace'){
				$match_count = preg_match_all('/^\{(\d+)\}\{(\d+)\}(.*)/i', $line, $matches);
				if($match_count){
					$output .= '{'.($matches[1][0] + round($_POST['przesuniecie'])).'}{'.($matches[2][0] + round($_POST['przesuniecie'])).'}'.$matches[3][0]."\n";
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
	
?><!DOCTYPE html>
<html lang="pl">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Przesuwanie napisów</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css">
</head>
<body>
	<div class="container p-5">
		<h1>Skrypt do przesuwania napisów do filmów w czasie</h1>
		<p>Po prostu załaduj swój plik z napisami i wpisz o ile sekund w czasie (wprzód lub wstecz) chcesz przesunąć napisy</p>
		<?php 
			if($alert_danger){
				echo('<div class="alert alert-danger">'.$alert_danger.'</div>');
			}
		?>
		<div class="row mb-4">
			<form action="" method="post" enctype="multipart/form-data" class="col-sm-4">
				<div class="form-group">
					<label for="plik">Plik z napisami</label>
					<input type="file" class="form-control-file" id="plik" name="plik" required accept=".txt,.srt">
				  </div>
				<div class="form-group">
					<label for="przesuniecie">Przesunięcie (w sekundach, dodatnie jeśli mają się wyświetlać później)</label>
					<input type="number" class="form-control" id="przesuniecie" name="przesuniecie" required step="0.1">
				 </div>
				<button type="submit" class="btn btn-primary">Wyślij</button>
			</form>
		</div>
		<p>Skrypt do pobrania na <a href="https://github.com/kamilwyremski/napisy" title="Pliki źródłowe skryptu przesuwania napisów w czasie" rel="nofollow">https://github.com/kamilwyremski/napisy</a></p>
		<p>Opis skryptu: <a href="https://blog.wyremski.pl/przesuwanie-napisow-z-filmow-w-czasie" title="Opis skryptu przesuwania napisów w czasie">https://blog.wyremski.pl/przesuwanie-napisow-z-filmow-w-czasie</a></p>
		<p><small>Created 2020 - 2021 by <a href="https://wyremski.pl" title="Full Stack Web Developer">Kamil Wyremski</a></small></p>
	</div>
 </body>
</html>
