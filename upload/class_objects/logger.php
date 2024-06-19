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

    public function logInfo($message) {
        $timestamp = date('[Y-m-d H:i:s]'); // Obtén la fecha y hora actual en el formato deseado
        $formattedMessage = sprintf("%s %s: %s\n", $timestamp, $this->loopName, $message);
        fwrite($this->logFile, $formattedMessage);
    }
}

?>