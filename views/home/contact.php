<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $title
 * @var string $content
 */

?>
<div class="card mb-4">
    <div class="card-header">
        <h1><?= $this->escape($title) ?></h1>
    </div>
    <div class="card-body">
        <p class="lead"><?= $this->escape($content) ?></p>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <h3>Contact Information</h3>
                <address>
                    <strong>HeartPhrame, Inc.</strong><br>
                    123 Framework Street<br>
                    Web City, PHP 12345<br>
                    <abbr title="Phone">P:</abbr> (123) 456-7890
                </address>
                <address>
                    <strong>Support:</strong> <a href="mailto:support@mvc-framework.com">
                        support@mvc-framework.com
                    </a>
                    <br>
                    <strong>Marketing:</strong>
                    <a href="mailto:marketing@mvc-framework.com">
                        marketing@mvc-framework.com
                    </a>
                </address>
            </div>
            <div class="col-md-6">
                <h3>Send Us a Message</h3>
                <form action="<?= $this->urlGenerator->getPathFor('contact.submitContact') ?>" method="post">
                    <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                    <div class="mb-3">
                        <label for="name" class="form-label">Your Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-12">
        <h3>Our Location</h3>
        <div class="ratio ratio-16x9">
            // phpcs:ignore
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d387193.3059353029!2d-74.25986548248684
            !3d40.69714941774136!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c24fa5d33f083b%3A0xc80b8f06e17
            7fe62!2sNew%20York%2C%20NY%2C%20USA!5e0!3m2!1sen!2s!4v1619139549546!5m2!1sen!2s"
                 style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>
</div>
