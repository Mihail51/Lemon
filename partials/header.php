

<header class="main-header">
  	<div class="header-wrap">
  		<div class="logo_header">
  			<img src="../img/logo_header.png" alt="Lemon">
  		</div>

  		<nav class="main-menu" aria-label="Main navigation">
  			<div class="menu">
  				<label class="menu-Toggle" for="menuCheck" aria-label="Open menu">
  					<img src="../img/Burger.png" alt="">
  				</label>

  				<input id="menuCheck" type="checkbox">

  				<ul class="menuBig">
  					<li><a href="#" target="_blank" rel="noopener noreferrer">HOME</a></li>

  					<li class="submenu">Recipes
  						<ul class="menuLittle">
  							<li><a href="../html/recomended.php" target="_blank" rel="noopener noreferrer">RECOMMENDED</a></li>
  							<li><a href="../html/popular.php" target="_blank" rel="noopener noreferrer">POPULAR</a></li>
  							<li><a href="../html/quick_and_easy.php" target="_blank" rel="noopener noreferrer">QUICK & EASY</a></li>
  							<li><a href="../html/healthy.php" target="_blank" rel="noopener noreferrer">HEALTHY</a></li>
  							<li><a href="../html/newest.php" target="_blank" rel="noopener noreferrer">NEWEST</a></li>
							<li><a href="../html/long.php" target="_blank" rel="noopener noreferrer">LONG</a></li>
							<li><a href="../html/middle.php" target="_blank" rel="noopener noreferrer">MIDDLE</a></li>
  						</ul>
  					</li>

  					<li><a href="../html/fotos.php" target="_blank" rel="noopener noreferrer">Photo Galleries</a></li>
  					<li><a href="../html/video.php" target="_blank" rel="noopener noreferrer">Videos</a></li>
  					<li><a href="../html/recipees.php" target="_blank" rel="noopener noreferrer">All Categories</a></li>
  				</ul>
  			</div>
  		</nav>

  		<div class="header-actions">
  			<!-- Language dropdown -->
  			<div class="lang" id="lang">
  				<button class="lang__btn" type="button" aria-haspopup="true" aria-expanded="false">
  					<span class="lang__current" data-lang-current>RU</span>
  					<span class="lang__chev" aria-hidden="true">▾</span>
  				</button>

  				<div class="lang__menu" role="menu" aria-label="Language">
  					<a class="lang__item" href="#ru" data-lang="ru" role="menuitem">RU</a>
  					<a class="lang__item" href="#en" data-lang="en" role="menuitem">EN</a>
  					<a class="lang__item" href="#he" data-lang="he" role="menuitem" lang="he">HE</a>
  				</div>
  			</div>

			<div class="auth">
			<?php if (!empty($_SESSION['user_email'])): ?>
				<a class="auth__link" href="/logout.php">Выйти</a>
			<?php else: ?>
				<a class="auth__link" href="/login.php">Войти</a>
			<?php endif; ?>
			</div>

  			<!-- Search -->
  			<form class="search" action="search.php">
  				<input type="submit">
  				<input type="search" data-i18n-placeholder="header.search.placeholder" placeholder="FIND A RECIPE">
  			</form>
  		</div>
  	</div>
  </header>