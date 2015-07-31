<?php

use Symfony\Component\Process\Process;

// Command that starts the built-in web server
$command = sprintf(
    'php -S %s:%d -t %s',
    WEB_SERVER_HOST,
    WEB_SERVER_PORT,
    WEB_SERVER_DOCROOT
);

$process = new Process($command);
$process->start();

$output->writeln(date('r') . "- Web server started on " . WEB_SERVER_HOST . ":" . WEB_SERVER_PORT);

//wait server start
sleep(1);

// Kill the web server when the process ends
register_shutdown_function(
    function () use ($process,$output) {
        $output->writeln('Web server shutdown');
        $process->stop();
    }
);
