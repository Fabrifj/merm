<?php

class Logger {
    private $loopName;
    private $logFile;

    public function __construct($loopName) {
        $this->loopName = $loopName;
        $logFileName = __DIR__ . '/log.txt'; // Ruta al archivo de registro en la misma raíz del script
        $this->logFile = fopen($logFileName, 'a'); // 'a' abre el archivo para escritura, y situa el puntero al final del archivo. Si el archivo no existe, intenta crearlo.
    }

    public function __destruct() {
        fclose($this->logFile);
    }

    public function log($level, $message) {
        $timestamp = date('[Y-m-d H:i:s]');
        $formattedMessage = sprintf("%s [%s] %s: %s\n", $timestamp, strtoupper($level), $this->loopName, $message);
        fwrite($this->logFile, $formattedMessage);
    }

    public function logDebug($message) {
        $this->log('debug', $message);
    }

    public function logInfo($message) {
        $this->log('Info', $message);
    }

    public function logError($message) {
        $this->log('Error', $message);

    }
}

?>