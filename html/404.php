<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<title>Lemon_404</title>
	<meta name="description" content="#" />
	<meta name="keywords" content="#" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link type="text/css" rel="stylesheet" href="../css/404.css">
	<link type="text/css" rel="stylesheet" href="../css/header.css">
	<link type="text/css" rel="stylesheet" href="../css/footer.css">
</head>

<body>
	<!-- HEADER -->
	<?php include __DIR__ . '/../partials/header.php'; ?>
	<!-- 404 -->
	<div class="error_wrap">
		<div class="error_title">
			<p>404</p>
		</div>
		<div class="error_content">
			<p>Sorry</p>
			<p>We coudn't <br /> find the page</p>
			<p>:(</p>
		</div>
		<form action="../index.html">
			<button>Back</button>
		</form>
	</div>
	<!-- FOOTER -->
	<?php include __DIR__ . '/../partials/footer.php'; ?>

	

</body>