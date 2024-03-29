<?php

namespace ProcessMaker\ImportExport;

class Extension
{
    public $extensions = [];

    public function register($exporterClass, $class)
    {
        if (!isset($this->extensions[$exporterClass])) {
            $this->extensions[$exporterClass] = [];
        }
        $this->extensions[$exporterClass][] = $class;
    }

    public function runExtensions($exporter, $method, $logger)
    {
        $exporterClass = get_class($exporter);

        if (!isset($this->extensions[$exporterClass])) {
            return;
        }

        foreach ($this->extensions[$exporterClass] as $class) {
            $logger->log("Running extension {$method} in {$class}");
            $extension = new $class();
            if (method_exists($extension, $method)) {
                $extension->$method()->call($exporter);
            }
        }
    }
}
