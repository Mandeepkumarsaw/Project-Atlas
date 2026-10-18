<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;display:none;">
<h4>An uncaught Exception was encountered</h4>
<p>Type: <?php echo get_class($exception); ?></p>
<p>Message: <?php echo $message; ?></p>
<p>Filename: <?php echo $exception->getFile(); ?></p>
<p>Line Number: <?php echo $exception->getLine(); ?></p>
</div>
