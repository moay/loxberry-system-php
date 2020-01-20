<?php

namespace LoxBerry\Logging\Writer;

use LoxBerry\Logging\Event\LogEvent;
use LoxBerry\Logging\Logger;

/**
 * Class LogFileWriter.
 */
class LogFileWriter
{
    const LOG_LABELS = [
        Logger::LOGLEVEL_DEBUG => 'DEBUG',
        Logger::LOGLEVEL_INFO => 'INFO',
        Logger::LOGLEVEL_OK => 'OK',
        Logger::LOGLEVEL_WARNING => 'WARNING',
        Logger::LOGLEVEL_ERROR => 'ERROR',
        Logger::LOGLEVEL_CRITICAL_ERROR => 'CRITICAL',
        Logger::LOGLEVEL_ALERT => 'ALERT',
        Logger::LOGLEVEL_FATAL_ERROR => 'EMERG',
    ];

    /** @var string */
    private $fileName;

    /** @var bool */
    private $appendToExisting;

    /** @var bool */
    private $initialized = false;

    /** @var bool */
    private $started = false;

    /** @var string */
    private $outputFileName;

    /**
     * LogFileWriter constructor.
     *
     * @param string $fileName
     * @param bool   $appendToExisting
     */
    public function __construct(string $fileName, bool $appendToExisting = false)
    {
        $this->fileName = $fileName;
        $this->outputFileName = $fileName;
        $this->appendToExisting = $appendToExisting;
    }

    public function initialize()
    {
        if (!$this->appendToExisting) {
            do {
                $fileName = date('Ymd-His-').$this->outputFileName;
            } while (file_exists($fileName));
            $this->outputFileName = $fileName;
        }
        $this->initialized = true;
    }

    public function logStart()
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        $this->writeLine(str_repeat('=', 80));
        $this->writeLine(sprintf(
            '%s <LOGSTART> TASK STARTED',
            (new \DateTime())->format('Y-m-d H:i:s')
        ));

        $this->started = true;
        register_shutdown_function([$this, 'logEnd'], null, true);
    }

    /**
     * @param LogEvent $event
     */
    public function logEvent(LogEvent $event)
    {
        if (!$this->started) {
            $this->logStart();
        }

        $this->writeLine(sprintf(
            '%s <%s> %s',
            $event->getEventTime()->format('Y-m-d H:i:s.u'),
            self::LOG_LABELS[$event->getLevel()],
            $this->getLogEventMessage($event)
        ));

        $this->started = false;
    }

    /**
     * @param string|null $message
     * @param bool        $shutdown
     *
     * @throws \Exception
     */
    public function logEnd(?string $message = 'TASK FINISHED', $shutdown = false)
    {
        if (!$this->started) {
            return;
        }

        if ($shutdown) {
            $this->writeLine(sprintf(
                '%s <LOGEND> END OF SCRIPT EXECUTION',
                (new \DateTime())->format('Y-m-d H:i:s')
            ));

            return;
        }

        $this->writeLine(sprintf(
            '%s <LOGEND> %s',
            (new \DateTime())->format('Y-m-d H:i:s'),
            $message
        ));

        $this->started = false;
    }

    /**
     * @param string $content
     */
    private function writeLine(string $content)
    {
        file_put_contents($this->outputFileName, $content.PHP_EOL, FILE_APPEND);
    }

    /**
     * @param LogEvent $event
     *
     * @return string
     */
    private function getLogEventMessage(LogEvent $event): string
    {
        if (null !== $event->getFileName() && null !== $event->getLineNumber()) {
            return sprintf(
                '%s (%s, L%s)',
                $event->getMessage(),
                $event->getFileName(),
                $event->getLineNumber()
            );
        }

        return sprintf(
            '%s',
            $event->getMessage()
        );
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return string
     */
    public function getOutputFileName(): string
    {
        return $this->outputFileName;
    }
}
