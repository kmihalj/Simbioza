<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $title
 * @var string $content
 */

?>

<div class="jumbotron">
    <h1 class="display-4"><?= $this->escape($title) ?></h1>
    <p class="lead"><?= $this->escape($content) ?></p>
    <hr class="my-4">
    <p>This is a lightweight PHP framework that implements PSR standards.</p>
    <p class="lead">
        <a class="btn btn-primary btn-lg" href="/about" role="button">Learn more</a>
    </p>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <h2>PSR Compliant</h2>
        <p>This framework adheres to PHP-FIG PSR standards, making it interoperable with other libraries.</p>
    </div>
    <div class="col-md-4">
        <h2>Simple Design</h2>
        <p>Lightweight and minimalistic design that's easy to understand and extend.</p>
    </div>
    <div class="col-md-4">
        <h2>Modern PHP</h2>
        <p>Built for PHP 8.2+ with modern features and best practices.</p>
    </div>
</div>
