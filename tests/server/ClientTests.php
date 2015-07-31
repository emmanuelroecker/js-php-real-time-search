<?php
use Symfony\Component\Process\Process;

$command = 'start firefox ' . "file://" . __DIR__ . "/../client/test.html";
$process = new Process($command);

$process->setTimeout(3600);
$process->run();
if (!$process->isSuccessful()) {
    throw new \RuntimeException($process->getErrorOutput());
}

sleep(5);

